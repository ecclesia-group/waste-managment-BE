<?php

namespace App\Http\Controllers\Pickup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pickup\PickupCreationRequest;
use App\Http\Requests\Pickup\SetPickupDateRequest;
use App\Http\Requests\Pickup\SetPickupPriceRequest;
use App\Http\Requests\Pickup\UpdatePickupRequest;
use App\Models\BulkWasteRequest;
use App\Models\Client;
use App\Models\Item;
use App\Models\Payment;
use App\Models\Pickup;
use App\Services\BulkWasteRequestCheckoutService;
use App\Services\RoutePlannerService;
use App\Traits\HasClientMapPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $ownerSlug = self::providerScopeSlug($user);
        $data['provider_slug'] = self::providerScopeSlug($user);
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
            ->forProvider((string) $ownerSlug)
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

        return $this->paginatedApiResponse(
            Pickup::query()
                ->where('client_slug', $user->client_slug)
                ->forProvider((string) $user->provider_slug)
                ->where('status', 'completed')
                ->with(['provider', 'client'])
                ->latest()
                ->paginate($this->perPage(request())),
            'Client completed pickups retrieved successfully'
        );
    }

    public function bulkWasteRequest(PickupCreationRequest $request)
    {
        $user = request()->user();

        $hasUnpaidCompletedBulk = Pickup::query()
            ->where('client_slug', $user->client_slug)
            ->where('status', 'completed')
            ->whereNotNull('bulk_waste_request_code')
            ->whereIn('bulk_waste_request_code', function ($query) use ($user) {
                $query->select('request_code')
                    ->from('bulk_waste_requests')
                    ->where('client_slug', $user->client_slug)
                    ->where('payment_status', '!=', 'paid');
            })
            ->exists();

        if ($hasUnpaidCompletedBulk) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Pay for your completed bulk waste pickup before submitting a new request',
                status_code: self::API_FAIL,
                data: []
            );
        }

        $code = Str::upper(Str::random(8));
        $data                  = $request->validated();
        $user                  = request()->user();
        $providerSlug = $user->provider_slug ?? self::providerScopeSlug($user);
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
            'status' => 'pending_approval',
        ]);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bulk waste request submitted and pending provider approval",
            status_code: self::API_SUCCESS,
            data: $bulkRequest->toArray()
        );
    }

    public function updateBulkWasteRequest(UpdatePickupRequest $request, string $requestCode)
    {
        $data = $request->validated();
        $user = request()->user();
        $bulkRequest = BulkWasteRequest::query()
            ->where('request_code', $requestCode)
            ->where('client_slug', $user->client_slug)
            // ->with('client')
            ->first();
        if (! $bulkRequest) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Bulk waste request not found",
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

        $bulkRequest->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bulk waste request updated successfully",
            status_code: self::API_SUCCESS,
            data: $bulkRequest->toArray()
        );
    }

    public function clientBulkWasteRequests(Request $request)
    {
        $clientSlug = (string) $request->user()->client_slug;

        return $this->paginatedApiResponse(
            BulkWasteRequest::query()
                // ->with('client')
                ->where('client_slug', $clientSlug)
                ->orderByDesc('created_at')
                ->paginate($this->perPage($request)),
            'Bulk waste requests retrieved successfully'
        );
    }

    public function clientBulkWasteRequestShow(Request $request, string $requestCode)
    {
        $clientSlug = (string) $request->user()->client_slug;
        $item = BulkWasteRequest::query()
            ->where('client_slug', $clientSlug)
            ->where('request_code', $requestCode)
            ->first();

        if (! $item) {
            return self::apiResponse(true, "Action Failed", "Bulk waste request not found", self::API_NOT_FOUND, []);
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bulk waste request retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $item->toArray()
        );
    }

    public function  deleteBulkWasteRequest(Request $request, string $requestCode)
    {
        $clientSlug = (string) $request->user()->client_slug;
        $bulkRequest = BulkWasteRequest::query()
            ->where('client_slug', $clientSlug)
            ->where('request_code', $requestCode)
            ->first();

        if (! $bulkRequest) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Bulk waste request not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $bulkRequest->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bulk waste request deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    public function providerBulkWasteRequests(Request $request)
    {
        $providerSlug = self::providerScopeSlug($request->user());
        $query = BulkWasteRequest::query()
            ->with('client')
            ->forProvider((string) $providerSlug);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return $this->paginatedApiResponse(
            $query->orderByDesc('created_at')->paginate($this->perPage($request)),
            'Bulk waste requests retrieved successfully'
        );
    }

    public function updateBulkWasteRequestStatus(Request $request, string $requestCode)
    {
        $data = $request->validate([
            'status' => 'required|string|in:approved,rejected',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $providerSlug = self::providerScopeSlug($request->user());
        $bulkRequest = BulkWasteRequest::query()
            ->where('request_code', $requestCode)
            ->forProvider((string) $providerSlug)
            ->first();

        if (! $bulkRequest) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Bulk request not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
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
        $bulkRequest->approved_at = $approved ? now() : null;
        $bulkRequest->rejected_at = $approved ? null : now();
        $bulkRequest->rejection_reason = $data['rejection_reason'] ?? null;
        $bulkRequest->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bulk request status updated successfully",
            status_code: self::API_SUCCESS,
            data: $bulkRequest->load('client')->toArray()
        );
    }

    public function setBulkWasteRequestPrice(Request $request, string $requestCode)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $providerSlug = self::providerScopeSlug($request->user());
        $bulkRequest = BulkWasteRequest::query()
            ->where('request_code', $requestCode)
            ->forProvider((string) $providerSlug)
            ->first();

        if (! $bulkRequest) {
            return self::apiResponse(true, "Action Failed", "Bulk request not found", self::API_NOT_FOUND, []);
        }

        if (! in_array($bulkRequest->status, ['pending_approval', 'approved'], true)) {
            return self::apiResponse(true, "Action Failed", "Only pending or approved bulk requests can be priced", self::API_FAIL, []);
        }

        $bulkRequest->amount = $data['amount'];
        $bulkRequest->status = 'approved';
        $bulkRequest->payment_status = ((float) $data['amount']) > 0 ? 'unpaid' : 'paid';
        $bulkRequest->approved_at ??= now();
        $bulkRequest->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bulk waste request price set successfully",
            status_code: self::API_SUCCESS,
            data: $bulkRequest->load('client')->toArray()
        );
    }

    public function providerBulkWasteRequestShow(Request $request, string $requestCode)
    {
        $providerSlug = self::providerScopeSlug($request->user());
        $bulkRequest = BulkWasteRequest::query()
            ->with('client')
            ->forProvider((string) $providerSlug)
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

    // public function deletePickup(Pickup $pickup)
    // {
    //     $user = request()->user();
    //     if ($pickup->client_slug !== $user->client_slug) {
    //         return self::apiResponse(
    //             in_error: true,
    //             message: "Action Failed",
    //             reason: "Pickup not found",
    //             status_code: self::API_NOT_FOUND,
    //             data: []
    //         );
    //     }

    //     $pickup->delete();

    //     return self::apiResponse(
    //         in_error: false,
    //         message: "Action Successful",
    //         reason: "Pickup deleted successfully",
    //         status_code: self::API_SUCCESS,
    //         data: []
    //     );
    // }

    public function providerUpdatePickup(UpdatePickupRequest $request, string $pickupCode)
    {
        $ownerSlug = self::providerScopeSlug($request->user());
        $pickup = Pickup::query()
            ->where('code', $pickupCode)
            ->forProvider((string) $ownerSlug)
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
        $ownerSlug = self::providerScopeSlug($request->user());
        $deleted = Pickup::query()
            ->where('code', $pickupCode)
            ->forProvider((string) $ownerSlug)
            ->delete();

        if ($deleted === 0) {
            return self::apiResponse(true, "Action Failed", "Pickup not found", self::API_NOT_FOUND, []);
        }

        return self::apiResponse(false, "Action Successful", "Pickup deleted successfully", self::API_SUCCESS, []);
    }

    // public function reschedulePickup(SetPickupDateRequest $request)
    // {
    //     $data = $request->validated();

    //     $pickup = Pickup::where('code', $data['code'])->first();
    //     if (! $pickup) {
    //         return self::apiResponse(
    //             in_error: true,
    //             message: "Action Failed",
    //             reason: "Pickup not found",
    //             status_code: self::API_NOT_FOUND
    //         );
    //     }

    //     $user = request()->user();
    //     if ($pickup->client_slug !== $user->client_slug) {
    //         return self::apiResponse(
    //             in_error: true,
    //             message: "Action Failed",
    //             reason: "Unauthorized to reschedule this pickup",
    //             status_code: self::API_NOT_FOUND,
    //             data: []
    //         );
    //     }

    //     $pickup->pickup_date = $data['pickup_date'];
    //     $pickup->status      = 'rescheduled';
    //     $pickup->save();

    //     return self::apiResponse(
    //         in_error: false,
    //         message: "Action Successful",
    //         reason: "Pickup rescheduled successfully",
    //         status_code: self::API_SUCCESS,
    //         data: self::enrichPickupForPickupUi($pickup)
    //     );
    // }

    public function getAllPickups()
    {
        $user = request()->user();
        $ownerSlug = self::providerScopeSlug($user);

        return $this->paginatedApiResponseMapped(
            Pickup::query()
                ->with(['client.group'])
                ->forProvider((string) $ownerSlug)
                ->latest()
                ->paginate($this->perPage(request())),
            'Pickups retrieved successfully',
            fn(Pickup $p) => self::enrichPickupForPickupUi($p)
        );
    }

    public function getSinglePickup(Request $request, string $pickupCode)
    {
        $user = request()->user();

        $pick_up = Pickup::with(['client.group'])->where('code', $pickupCode)->first();
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
            if (
                (string) $pick_up->client_slug !== (string) $user->client_slug
                || (string) $pick_up->provider_slug !== (string) $user->provider_slug
            ) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Unauthorized to view this pickup",
                    status_code: self::API_FAIL,
                    data: []
                );
            }
        } elseif (isset($user->provider_slug)) {
            if ((string) $pick_up->provider_slug !== (string) self::providerScopeSlug($user)) {
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
        $user = request()->user();

        return $this->paginatedApiResponseMapped(
            Pickup::query()
                ->with(['client.group', 'routePlanner'])
                ->where('client_slug', $user->client_slug)
                ->forProvider((string) $user->provider_slug)
                ->latest()
                ->paginate($this->perPage(request())),
            'Client pickups retrieved successfully',
            fn(Pickup $p) => self::enrichPickupForPickupUi($p)
        );
    }

    // public function setPickupPrice(SetPickupPriceRequest $request)
    // {
    //     $data = $request->validated();
    //     $user = request()->user();

    //     $pickup = Pickup::where('code', $data['code'])->first();
    //     if (! $pickup) {
    //         return self::apiResponse(
    //             in_error: true,
    //             message: "Action Failed",
    //             reason: "Pickup not found",
    //             status_code: self::API_NOT_FOUND
    //         );
    //     }

    //     // Provider-scoped update.
    //     $providerSlug = self::providerScopeSlug($user);
    //     if ($providerSlug && (string) $pickup->provider_slug !== (string) $providerSlug) {
    //         return self::apiResponse(
    //             in_error: true,
    //             message: "Action Failed",
    //             reason: "Unauthorized to update this pickup price",
    //             status_code: self::API_FAIL,
    //             data: []
    //         );
    //     }

    //     $pickup->amount = $data['amount'];
    //     $pickup->status = 'priced';
    //     $pickup->save();

    //     return self::apiResponse(
    //         in_error: false,
    //         message: "Action Successful",
    //         reason: "Pickup price set successfully",
    //         status_code: self::API_SUCCESS,
    //         data: $pickup->toArray()
    //     );
    // }

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
        if (isset($user->provider_slug) && (string) $pickup->provider_slug !== (string) self::providerScopeSlug($user)) {
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

    /**
     * Update pickup scan status (map: scanned = green, unscanned = red).
     * No GPS proximity or offline lag checks — status updates immediately.
     */
    public function setScanStatus()
    {
        $data = request()->validate([
            'pickup_code' => 'required|string|exists:pickups,code',
            'status' => 'required|string|in:scanned,not_scanned,unscanned',
            'comment' => 'nullable|string',
        ]);

        $user = request()->user();

        $pickup = Pickup::query()->where('code', $data['pickup_code'])->first();
        if (! $pickup) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Pickup not found",
                status_code: self::API_NOT_FOUND
            );
        }

        // Provider-scoped scan updates.
        if (isset($user->provider_slug) && (string) $pickup->provider_slug !== (string) self::providerScopeSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to update scan status for this pickup",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $routePlannerService = app(RoutePlannerService::class);
        $pickup = $routePlannerService->updatePickupScanStatus(
            $pickup,
            $data['status'],
            $data['comment'] ?? null
        );

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Pickup scan status updated successfully",
            status_code: self::API_SUCCESS,
            data: self::manualScanPickupPayload($pickup)
        );
    }

    public function manualCodeScan()
    {
        $request = request();
        $request->merge([
            'item_code' => $request->input('item_code') ?? $request->input('bin_code'),
        ]);

        $data = $request->validate([
            'item_code' => 'required|string|exists:items,item_code',
            'pickup_code' => 'required|string|exists:pickups,code',
            'comment' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();

        $item = Item::query()
            ->where('item_code', $data['item_code'])
            ->where('status', Item::STATUS_ACTIVE)
            ->first();

        if (! $item) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Item not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        // Provider-scoped manual scan: ensure this item belongs to the current provider.
        if (isset($user->provider_slug) && (string) $item->provider_slug !== (string) self::providerScopeSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to scan this item",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $pickup = Pickup::with(['provider', 'client.group', 'routePlanner'])
            ->where('code', $data['pickup_code'])
            ->whereNotNull('route_planner_id')
            ->where('client_slug', $item->client_slug)
            ->where('provider_slug', $item->provider_slug)
            ->whereIn('status', ['pending', 'scheduled'])
            ->whereIn('scan_status', ['unscanned', 'pending', 'not_scanned'])
            ->first();

        if (! $pickup) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "No pending pickup found for this item and pickup",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $pickup = app(RoutePlannerService::class)->updatePickupScanStatus(
            $pickup,
            'scanned',
            $data['comment'] ?? null
        );

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Pickup scanned successfully",
            status_code: self::API_SUCCESS,
            data: self::manualScanPickupPayload($pickup, $data['item_code'])
        );
    }

    public function getPickupDates()
    {
        $user = request()->user();

        return $this->paginatedApiResponseMapped(
            Pickup::query()
                ->with(['routePlanner'])
                ->where('client_slug', $user->client_slug)
                ->whereNotNull('pickup_date')
                ->orderByDesc('pickup_date')
                ->paginate($this->perPage(request())),
            'Pickup schedules retrieved successfully',
            fn(Pickup $pickup) => self::enrichPickupForPickupUi($pickup)
        );
    }
}
