<?php
namespace App\Http\Controllers\Pickup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pickup\PickupCreationRequest;
use App\Http\Requests\Pickup\SetPickupDateRequest;
use App\Http\Requests\Pickup\SetPickupPriceRequest;
use App\Http\Requests\Pickup\UpdatePickupRequest;
use App\Models\BulkWasteRequest;
use App\Models\Client;
use App\Models\Pickup;
use App\Models\PickupScanEvent;
use App\Models\RoutePlannerBinAssignment;
use App\Support\Geo\Haversine;
use App\Traits\HasClientMapPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PickupController extends Controller
{
    use HasClientMapPayload;

    public function providerPickupCreation(PickupCreationRequest $request)
    {
        $user                  = request()->user();
        $code                  = Str::random(5);
        $data                  = $request->validated();
        $data['driver_slug']   = Str::uuid();
        $providerSlug = $this->resolveProviderScopeSlug($user);
        $data['provider_slug'] = $providerSlug;
        $data['code']          = $code;

        if (empty($data['client_slug'])) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "client_slug is required for provider pickup creation",
                status_code: self::API_FAIL,
                data: []
            );
        }

        // Tenant isolation: provider can only create pickups for their own clients.
        $client = Client::where('client_slug', $data['client_slug'])
            ->where('provider_slug', $providerSlug)
            ->first();

        if (! $client) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to create pickup for this client",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $image_fields = [
            'images',
        ];

        $data = static::processImage($image_fields, $data);

        $driver = Pickup::create($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider pick up requested created successfully",
            status_code: self::API_SUCCESS,
            data: $driver->toArray()
        );
    }

    public function getCompletedPickups()
    {
        $user = request()->user();

        // Eager load the provider relationship in the initial query
        $pickups = Pickup::where(["client_slug" => $user->client_slug, "provider_slug" => $user->provider_slug])
            ->where('status', 'completed')
            ->with('provider') // Eager load here - reduces to 2 queries total
            ->latest()         // Optional: order by most recent
            ->get();

        if ($pickups->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "No completed pickups found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client completed pickups retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $pickups->load('client', 'provider')->toArray()
        );
    }

    public function bulkWasteRequest(PickupCreationRequest $request)
    {
        $code                  = Str::random(5);
        $data                  = $request->validated();
        $user                  = request()->user();
        $providerSlug = $this->resolveProviderScopeSlug($user);
        // Scopes to authenticated client/provider.
        $data['client_slug'] = $user->client_slug;
        $data['provider_slug'] = $providerSlug;

        $image_fields = [
            'images',
        ];

        $data = static::processImage($image_fields, $data);

        $bulkRequest = BulkWasteRequest::create([
            'request_code' => $code,
            'client_slug' => $data['client_slug'],
            'provider_slug' => $data['provider_slug'],
            'title' => $data['title'],
            'category' => $data['category'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'images' => $data['images'] ?? null,
            'requested_pickup_date' => $data['pickup_date'] ?? null,
            'status' => 'pending_approval',
            'approval_status' => 'pending',
        ]);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bulk waste request submitted and pending provider approval",
            status_code: self::API_SUCCESS,
            data: $bulkRequest->load('client', 'provider')->toArray()
        );
    }

    public function updateBulkWasteRequest(UpdatePickupRequest $request, string $requestCode)
    {
        $data = $request->validated();
        $user = request()->user();
        $bulkRequest = BulkWasteRequest::query()
            ->where('request_code', $requestCode)
            ->where('client_slug', $user->client_slug)
            ->first();
        if (! $bulkRequest) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Pickup not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $image_fields = [
            'images',
        ];

        $data = static::processImage($image_fields, $data);

        if ($bulkRequest->status !== 'pending_approval') {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Only pending approval requests can be edited",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $bulkRequest->update([
            'title' => $data['title'] ?? $bulkRequest->title,
            'category' => $data['category'] ?? $bulkRequest->category,
            'description' => $data['description'] ?? $bulkRequest->description,
            'location' => $data['location'] ?? $bulkRequest->location,
            'images' => $data['images'] ?? $bulkRequest->images,
            'requested_pickup_date' => $data['pickup_date'] ?? $bulkRequest->requested_pickup_date,
        ]);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bulk waste request updated successfully",
            status_code: self::API_SUCCESS,
            data: $bulkRequest->fresh()->load('client', 'provider')->toArray()
        );
    }

    public function clientBulkWasteRequests(Request $request)
    {
        $clientSlug = (string) $request->user()->client_slug;
        $items = BulkWasteRequest::query()
            ->where('client_slug', $clientSlug)
            ->latest()
            ->get();

        return self::apiResponse(false, "Action Successful", "Bulk waste requests retrieved successfully", self::API_SUCCESS, $items->toArray());
    }

    public function clientBulkWasteRequestShow(Request $request, string $requestCode)
    {
        $clientSlug = (string) $request->user()->client_slug;
        $item = BulkWasteRequest::query()
            ->where('client_slug', $clientSlug)
            ->where('request_code', $requestCode)
            ->first();

        if (! $item) {
            return self::apiResponse(true, "Action Failed", "Bulk request not found", self::API_NOT_FOUND, []);
        }

        return self::apiResponse(false, "Action Successful", "Bulk waste request retrieved successfully", self::API_SUCCESS, $item->toArray());
    }

    public function deleteBulkWasteRequest(Request $request, string $requestCode)
    {
        $clientSlug = (string) $request->user()->client_slug;
        $deleted = BulkWasteRequest::query()
            ->where('client_slug', $clientSlug)
            ->where('request_code', $requestCode)
            ->delete();

        if ($deleted === 0) {
            return self::apiResponse(true, "Action Failed", "Bulk request not found", self::API_NOT_FOUND, []);
        }

        return self::apiResponse(false, "Action Successful", "Bulk waste request deleted successfully", self::API_SUCCESS, []);
    }

    public function providerBulkWasteRequests(Request $request)
    {
        $providerSlug = $this->resolveProviderScopeSlug($request->user());
        $query = BulkWasteRequest::query()
            ->with(['client.group'])
            ->where('provider_slug', $providerSlug);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bulk waste requests retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $query->latest()->get()->toArray()
        );
    }

    public function updateBulkWasteRequestStatus(Request $request, string $requestCode)
    {
        $data = $request->validate([
            'status' => 'required|string|in:approved,rejected',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $providerSlug = $this->resolveProviderScopeSlug($request->user());
        $bulkRequest = BulkWasteRequest::query()
            ->where('request_code', $requestCode)
            ->where('provider_slug', $providerSlug)
            ->first();

        if (! $bulkRequest) {
            return self::apiResponse(true, "Action Failed", "Bulk request not found", self::API_NOT_FOUND, []);
        }

        if ($bulkRequest->status !== 'pending_approval') {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "This request is already processed",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $approved = $data['status'] === 'approved';
        $bulkRequest->status = $approved ? 'approved' : 'rejected';
        $bulkRequest->approval_status = $approved ? 'approved' : 'rejected';
        $bulkRequest->approved_at = $approved ? now() : null;
        $bulkRequest->rejected_at = $approved ? null : now();
        $bulkRequest->rejection_reason = $data['rejection_reason'] ?? null;
        $bulkRequest->save();

        return self::apiResponse(false, "Action Successful", "Bulk request status updated successfully", self::API_SUCCESS, $bulkRequest->toArray());
    }

    public function providerBulkWasteRequestShow(Request $request, string $requestCode)
    {
        $providerSlug = $this->resolveProviderScopeSlug($request->user());
        $bulkRequest = BulkWasteRequest::query()
            ->with(['client.group'])
            ->where('provider_slug', $providerSlug)
            ->where('request_code', $requestCode)
            ->first();

        if (! $bulkRequest) {
            return self::apiResponse(true, "Action Failed", "Bulk request not found", self::API_NOT_FOUND, []);
        }

        return self::apiResponse(false, "Action Successful", "Bulk waste request retrieved successfully", self::API_SUCCESS, $bulkRequest->toArray());
    }

    // public function updatePickupStatus(PickupStatusChangeRequest $request)
    // {
    //     $data   = $request->validated();
    //     $user   = request()->user();
    //     $pickup = Pickup::where([
    //         'id'          => $data['id'],
    //         'client_slug' => $user->client_slug,
    //     ])->first();

    //     if (! $pickup) {
    //         return self::apiResponse(
    //             in_error: true,
    //             message: "Action Failed",
    //             reason: "Pickup not found",
    //             status_code: self::API_NOT_FOUND
    //         );
    //     }

    //     $pickup->status = $data['status'];
    //     $pickup->save();

    //     return self::apiResponse(
    //         in_error: false,
    //         message: "Action Successful",
    //         reason: "Pickup status updated successfully",
    //         status_code: self::API_SUCCESS,
    //         data: $pickup->load('client', 'provider')->toArray()
    //     );
    // }

    public function deletePickup(Pickup $pickup)
    {
        $user = request()->user();
        if ($pickup->client_slug !== $user->client_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Pickup not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $pickup->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Pickup deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    public function providerUpdatePickup(UpdatePickupRequest $request, string $pickupCode)
    {
        $providerSlug = $this->resolveProviderScopeSlug($request->user());
        $pickup = Pickup::query()
            ->where('code', $pickupCode)
            ->where('provider_slug', $providerSlug)
            ->first();

        if (! $pickup) {
            return self::apiResponse(true, "Action Failed", "Pickup not found", self::API_NOT_FOUND, []);
        }

        $data = $request->validated();
        $image_fields = ['images'];
        $data = static::processImage($image_fields, $data);
        $pickup->update($data);

        return self::apiResponse(false, "Action Successful", "Pickup updated successfully", self::API_SUCCESS, $pickup->fresh()->toArray());
    }

    public function providerDeletePickup(Request $request, string $pickupCode)
    {
        $providerSlug = $this->resolveProviderScopeSlug($request->user());
        $deleted = Pickup::query()
            ->where('code', $pickupCode)
            ->where('provider_slug', $providerSlug)
            ->delete();

        if ($deleted === 0) {
            return self::apiResponse(true, "Action Failed", "Pickup not found", self::API_NOT_FOUND, []);
        }

        return self::apiResponse(false, "Action Successful", "Pickup deleted successfully", self::API_SUCCESS, []);
    }

    public function reschedulePickup(SetPickupDateRequest $request)
    {
        $data = $request->validated();

        $pickup = Pickup::where('code', $data['code'])->first();
        if (! $pickup) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Pickup not found",
                status_code: self::API_NOT_FOUND
            );
        }

        $user = request()->user();
        if ($pickup->client_slug !== $user->client_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to reschedule this pickup",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $pickup->pickup_date = $data['pickup_date'];
        $pickup->status      = 'rescheduled';
        $pickup->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Pickup rescheduled successfully",
            status_code: self::API_SUCCESS,
            data: self::enrichPickupForPickupUi($pickup)
        );
    }

    public function getAllPickups()
    {
        $user    = request()->user();
        $providerSlug = $this->resolveProviderScopeSlug($user);
        $pickups = Pickup::with(['client.group'])
            ->where(['provider_slug' => $providerSlug])
            ->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bulk waste requests retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $pickups->map(fn (Pickup $p) => self::enrichPickupForPickupUi($p))->values()->all()
        );
    }

    public function getSinglePickup($pickup)
    {
        $user = request()->user();

        $pick_up = Pickup::with(['client.group'])->where('code', $pickup)->first();
        if (! $pick_up) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Pickup not found",
                status_code: self::API_NOT_FOUND
            );
        }

        // Prevent cross-tenant leakage when fetching by `code`.
        if (isset($user->client_slug)) {
            if ((string) $pick_up->client_slug !== (string) $user->client_slug || (string) $pick_up->provider_slug !== (string) $user->provider_slug) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Unauthorized to view this pickup",
                    status_code: self::API_FAIL,
                    data: []
                );
            }
        } elseif (isset($user->provider_slug)) {
            if ((string) $pick_up->provider_slug !== (string) $this->resolveProviderScopeSlug($user)) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Unauthorized to view this pickup",
                    status_code: self::API_FAIL,
                    data: []
                );
            }
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Pickup retrieved successfully",
            status_code: self::API_SUCCESS,
            data: self::enrichPickupForPickupUi($pick_up)
        );
    }

    public function getClientPickups()
    {
        $user    = request()->user();
        $pickups = Pickup::with(['client.group'])
            ->where(["client_slug" => $user->client_slug, "provider_slug" => $user->provider_slug])
            ->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client pickups retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $pickups->map(fn (Pickup $p) => self::enrichPickupForPickupUi($p))->values()->all()
        );
    }

    public function setPickupPrice(SetPickupPriceRequest $request)
    {
        $data = $request->validated();
        $user = request()->user();

        $pickup = Pickup::where('code', $data['code'])->first();
        if (! $pickup) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Pickup not found",
                status_code: self::API_NOT_FOUND
            );
        }

        // Provider-scoped update.
        $providerSlug = $this->resolveProviderScopeSlug($user);
        if ($providerSlug && (string) $pickup->provider_slug !== (string) $providerSlug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to update this pickup price",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $pickup->amount = $data['amount'];
        $pickup->status = 'priced';
        $pickup->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Pickup price set successfully",
            status_code: self::API_SUCCESS,
            data: $pickup->toArray()
        );
    }

    public function setPickupDate(SetPickupDateRequest $request)
    {
        $data = $request->validated();
        $user = request()->user();

        $pickup = Pickup::where('code', $data['code'])->first();
        if (! $pickup) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Pickup not found",
                status_code: self::API_NOT_FOUND
            );
        }

        // Provider-scoped update.
        $providerSlug = $this->resolveProviderScopeSlug($user);
        if ($providerSlug && (string) $pickup->provider_slug !== (string) $providerSlug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to update this pickup date",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $pickup->pickup_date = $data['pickup_date'];
        $pickup->status      = 'scheduled';
        $pickup->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Pickup date and time set successfully",
            status_code: self::API_SUCCESS,
            data: $pickup->toArray()
        );
    }

    public function setScanStatus()
    {
        // Scan status is used by the map UI: scanned => green, unscanned => red.
        $data = request()->validate([
            'code'   => 'required|string|exists:pickups,code',
            'status' => 'required|string|in:scanned,not_scanned,unscanned',
            'driver_latitude' => 'nullable|numeric|between:-90,90',
            'driver_longitude' => 'nullable|numeric|between:-180,180',
            'idempotency_key' => 'nullable|string|max:128',
            'device_scanned_at' => 'nullable|date',
        ]);

        $user = request()->user();

        if (! empty($data['idempotency_key'])) {
            $existing = PickupScanEvent::query()
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();
            if ($existing) {
                $pickup = Pickup::where('code', $existing->pickup_code)->first();
                if ($pickup) {
                    $pickup->loadMissing(['client.group']);

                    return self::apiResponse(
                        in_error: false,
                        message: 'Action Successful',
                        reason: 'Duplicate idempotency key — previously recorded scan',
                        status_code: self::API_SUCCESS,
                        data: self::enrichPickupForPickupUi($pickup)
                    );
                }
            }
        }

        if (! empty($data['device_scanned_at'])) {
            $deviceAt = \Carbon\Carbon::parse($data['device_scanned_at']);
            if ($deviceAt->isFuture()) {
                return self::apiResponse(
                    in_error: true,
                    message: 'Action Failed',
                    reason: 'device_scanned_at cannot be in the future',
                    status_code: self::API_FAIL,
                    data: []
                );
            }
            if ($deviceAt->lt(now()->subDays(7))) {
                return self::apiResponse(
                    in_error: true,
                    message: 'Action Failed',
                    reason: 'device_scanned_at is outside the allowed sync window (7 days)',
                    status_code: self::API_FAIL,
                    data: []
                );
            }
        }

        $pickup = Pickup::where('code', $data['code'])->first();
        if (! $pickup) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Pickup not found",
                status_code: self::API_NOT_FOUND
            );
        }

        // Provider-scoped scan updates.
        $providerSlug = $this->resolveProviderScopeSlug($user);
        if ($providerSlug && (string) $pickup->provider_slug !== (string) $providerSlug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to update scan status for this pickup",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $canonicalStatus = match ($data['status']) {
            'scanned' => 'scanned',
            'not_scanned' => 'not_scanned',
            'unscanned' => 'not_scanned',
            default => $data['status'],
        };

        if ($canonicalStatus === 'scanned'
            && isset($data['driver_latitude'], $data['driver_longitude'])
        ) {
            $pickup->loadMissing('client');
            $client = $pickup->client;
            if ($client && $client->latitude !== null && $client->longitude !== null) {
                $meters = Haversine::meters(
                    (float) $data['driver_latitude'],
                    (float) $data['driver_longitude'],
                    (float) $client->latitude,
                    (float) $client->longitude
                );
                if ($meters > 100) {
                    return self::apiResponse(
                        in_error: true,
                        message: 'Action Failed',
                        reason: 'Scan rejected: driver is farther than 100m from the client location',
                        status_code: self::API_FAIL,
                        data: ['distance_meters' => round($meters, 2)]
                    );
                }
            }
        }

        $pickup->scan_status = $canonicalStatus;

        // End-to-end flow: once a bin is scanned successfully, treat the pickup as completed.
        if ($canonicalStatus === 'scanned') {
            $pickup->status = 'completed';
        } elseif ($canonicalStatus === 'not_scanned') {
            $pickup->status = 'pending';
        }
        $pickup->save();

        // Keep route planner assignment rows in sync (used for map coloring).
        $assignment = RoutePlannerBinAssignment::query()
            ->where('provider_slug', $providerSlug)
            ->where('pickup_code', $pickup->code)
            ->first();
        if ($assignment) {
            $assignment->scan_status = $canonicalStatus;
            // Keep timestamps mutually exclusive for cleaner map UI.
            $assignment->scanned_at = $canonicalStatus === 'scanned' ? now() : null;
            $assignment->unscanned_at = $canonicalStatus === 'not_scanned' ? now() : null;
            $assignment->save();
        }

        // Support "scan-first" flows: remember the last scanned client for this provider.
        if ($canonicalStatus === 'scanned' && $pickup->provider_slug) {
            Cache::put(
                key: 'wms:last_scanned_client_slug:' . $pickup->provider_slug,
                value: $pickup->client_slug,
                seconds: 15 * 60 // 15 minutes TTL
            );
        }

        if (! empty($data['idempotency_key']) && $pickup->provider_slug) {
            PickupScanEvent::query()->updateOrCreate(
                ['idempotency_key' => $data['idempotency_key']],
                [
                    'provider_slug' => $pickup->provider_slug,
                    'pickup_code' => $pickup->code,
                    'device_scanned_at' => isset($data['device_scanned_at'])
                        ? \Carbon\Carbon::parse($data['device_scanned_at'])
                        : null,
                ]
            );
        }

        $pickup->refresh();
        $pickup->loadMissing(['client.group']);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Pickup scan status updated successfully",
            status_code: self::API_SUCCESS,
            data: self::enrichPickupForPickupUi($pickup)
        );
    }

    public function manualCodeScan()
    {
        $data = request()->validate([
            'bin_code' => 'required|string|exists:clients,bin_code',
        ]);

        $user = request()->user();

        $bin = Client::where('bin_code', $data['bin_code'])->first();

        if (! $bin) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Bin not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        // Provider-scoped manual scan: ensure this bin belongs to the current provider.
        $providerSlug = $this->resolveProviderScopeSlug($user);
        if ($providerSlug && (string) $bin->provider_slug !== (string) $providerSlug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to scan this bin",
                status_code: self::API_FAIL,
                data: []
            );
        }

        // Return pending pickups that are tied to an active route-plan scan assignment.
        $pendingAssignments = RoutePlannerBinAssignment::query()
            ->where('client_slug', $bin->client_slug)
            ->where('provider_slug', $bin->provider_slug)
            ->where('scan_status', 'pending')
            ->orderByDesc('created_at')
            ->get(['pickup_code']);

        if ($pendingAssignments->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "No pending pickups found for this bin",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $pickupCodes = $pendingAssignments->pluck('pickup_code')->toArray();

        $pickups = Pickup::with(['provider', 'client.group'])
            ->whereIn('code', $pickupCodes)
            ->where('status', 'pending')
            ->where('scan_status', 'pending')
            ->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bin details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $pickups->map(fn (Pickup $p) => self::enrichPickupForPickupUi($p))->values()->all()
        );
    }

    public function getPickupDates()
    {
        $user         = request()->user();
        $pickup_dates = Pickup::where(function ($query) use ($user) {
            $query->where('client_slug', $user->client_slug)
                ->where('provider_slug', $user->provider_slug);
        })
            ->whereNotNull('pickup_date')
            ->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Pickup dates retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $pickup_dates->toArray()
        );
    }

    private function resolveProviderScopeSlug(object $user): ?string
    {
        if (! isset($user->provider_slug)) {
            return null;
        }

        return (bool) ($user->is_main ?? true)
            ? (string) $user->provider_slug
            : (string) ($user->parent_slug ?: $user->provider_slug);
    }
}
