<?php
namespace App\Http\Controllers\Pickup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pickup\PickupCreationRequest;
use App\Http\Requests\Pickup\SetPickupDateRequest;
use App\Http\Requests\Pickup\SetPickupPriceRequest;
use App\Models\Pickup;
use Illuminate\Support\Str;

class PickupController extends Controller
{
    public function providerPickupCreation(PickupCreationRequest $request)
    {
        $code                = Str::random(5);
        $data                = $request->validated();
        $data['driver_slug'] = Str::uuid();
        $data['code']        = $code;

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

    public function bulkWasteRequest(PickupCreationRequest $request)
    {
        $code         = Str::random(5);
        $data         = $request->validated();
        $data['code'] = $code;

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

    public function getAllPickups()
    {
        $pickups = Pickup::get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Bulk waste requests retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $pickups->toArray()
        );
    }

    public function getSinglePickup($pickup_code)
    {
        $pickup = Pickup::where('code', $pickup_code)->first();
        if (! $pickup) {
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
            data: $pickup->toArray()
        );
    }

    public function getClientPickups()
    {
        $user    = request()->user();
        $pickups = Pickup::where('client_slug', $user->client_slug)->get();
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

    public function getPickupDates()
    {
        $pickup_dates = Pickup::whereNotNull('pickup_date')->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Pickup dates retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $pickup_dates->toArray()
        );
    }
}
