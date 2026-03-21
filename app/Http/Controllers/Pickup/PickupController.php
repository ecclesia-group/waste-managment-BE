<?php
namespace App\Http\Controllers\Pickup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pickup\PickupCreationRequest;
use App\Http\Requests\Pickup\PickupStatusChangeRequest;
use App\Http\Requests\Pickup\SetPickupDateRequest;
use App\Http\Requests\Pickup\SetPickupPriceRequest;
use App\Http\Requests\Pickup\UpdatePickupRequest;
use App\Models\Client;
use App\Models\Pickup;
use App\Models\RoutePlannerBinAssignment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PickupController extends Controller
{
    public function providerPickupCreation(PickupCreationRequest $request)
    {
        $user                  = request()->user();
        $code                  = Str::random(5);
        $data                  = $request->validated();
        $data['driver_slug']   = Str::uuid();
        $data['provider_slug'] = $user->provider_slug;
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
            ->where('provider_slug', $user->provider_slug)
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
            data: $pickups->toArray()
        );
    }

    public function bulkWasteRequest(PickupCreationRequest $request)
    {
        $code                  = Str::random(5);
        $data                  = $request->validated();
        $data['code']          = $code;
        $user                  = request()->user();
        // Scopes to authenticated client/provider.
        $data['client_slug'] = $user->client_slug;
        $data['provider_slug'] = $user->provider_slug;

        $image_fields = [
            'images',
        ];

        $data = static::processImage($image_fields, $data);

        $pickup = Pickup::create($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bulk waste request created successfully",
            status_code: self::API_SUCCESS,
            data: $pickup->toArray()
        );
    }

    public function updateBulkWasteRequest(UpdatePickupRequest $request, Pickup $pickup)
    {
        $data = $request->validated();
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

        $image_fields = [
            'images',
        ];

        $data = static::processImage($image_fields, $data);

        $pickup->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bulk waste request updated successfully",
            status_code: self::API_SUCCESS,
            data: $pickup->toArray()
        );
    }

    public function updatePickupStatus(PickupStatusChangeRequest $request)
    {
        $data   = $request->validated();
        $user   = request()->user();
        $pickup = Pickup::where([
            'id'          => $data['id'],
            'client_slug' => $user->client_slug,
        ])->first();

        if (! $pickup) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Pickup not found",
                status_code: self::API_NOT_FOUND
            );
        }

        $pickup->status = $data['status'];
        $pickup->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Pickup status updated successfully",
            status_code: self::API_SUCCESS,
            data: $pickup->toArray()
        );
    }

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

    public function reschedulePickup(SetPickupDateRequest $request)
    {
        $data = $request->validated();

        $pickup = Pickup::where('id', $data['id'])->first();
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
                reason: "Pickup not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $pickup->pickup_date = null;
        $pickup->status      = 'rescheduled';
        $pickup->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Pickup rescheduled successfully",
            status_code: self::API_SUCCESS,
            data: $pickup->toArray()
        );
    }

    public function getAllPickups()
    {
        $user    = request()->user();
        $pickups = Pickup::where(['provider_slug' => $user->provider_slug])->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bulk waste requests retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $pickups->toArray()
        );
    }

    public function getSinglePickup($pickup)
    {
        $user = request()->user();

        $pick_up = Pickup::where('code', $pickup)->first();
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
            if ((string) $pick_up->provider_slug !== (string) $user->provider_slug) {
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
            data: $pick_up->toArray()
        );
    }

    public function getClientPickups()
    {
        $user    = request()->user();
        $pickups = Pickup::where(["client_slug" => $user->client_slug, "provider_slug" => $user->provider_slug])->get();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client pickups retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $pickups->toArray()
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
        if (isset($user->provider_slug) && (string) $pickup->provider_slug !== (string) $user->provider_slug) {
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
        if (isset($user->provider_slug) && (string) $pickup->provider_slug !== (string) $user->provider_slug) {
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
        ]);

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

        // Provider-scoped scan updates.
        if (isset($user->provider_slug) && (string) $pickup->provider_slug !== (string) $user->provider_slug) {
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
            ->where('provider_slug', $user->provider_slug)
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

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Pickup scan status updated successfully",
            status_code: self::API_SUCCESS,
            data: $pickup->toArray()
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
        if (isset($user->provider_slug) && (string) $bin->provider_slug !== (string) $user->provider_slug) {
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

        $pickups = Pickup::with(['provider', 'client'])
            ->whereIn('code', $pickupCodes)
            ->where('status', 'pending')
            ->where('scan_status', 'pending')
            ->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bin details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $pickups->toArray()
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
}
