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
        $pick_up = Pickup::where('code', $pickup)->first();
        if (! $pick_up) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Pickup not found",
                status_code: self::API_NOT_FOUND
            );
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

        $pickup = Pickup::where('code', $data['code'])->first();
        if (! $pickup) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Pickup not found",
                status_code: self::API_NOT_FOUND
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

        $pickup = Pickup::where('code', $data['code'])->first();
        if (! $pickup) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Pickup not found",
                status_code: self::API_NOT_FOUND
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
        $data = request()->validate([
            'code'   => 'required|string|exists:pickups,code',
            'status' => 'required|string|in:scanned,not_scanned',
        ]);

        $pickup = Pickup::where('code', $data['code'])->first();
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

        $pickup = Pickup::with(['provider:id,provider_slug,name,email', 'client:id,client_slug,name,email'])
            ->where([
                'client_slug'   => $bin->client_slug,
                'provider_slug' => $bin->provider_slug,
                'status'        => 'pending',
                'scan_status'   => 'pending',
            ])->get();

        // Check if eager loading worked
        foreach ($pickup as $p) {
            echo "Provider loaded: " . ($p->relationLoaded('provider') ? 'Yes' : 'No') . "\n";
            echo "Client loaded: " . ($p->relationLoaded('client') ? 'Yes' : 'No') . "\n";
        }

        if ($pickup->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "No pending pickups found for this bin",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bin details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $pickup->toArray()
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
