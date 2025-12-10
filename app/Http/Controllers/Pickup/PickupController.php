<?php
namespace App\Http\Controllers\Pickup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pickup\ProviderPickupCreationRequest;
use App\Models\Pickup;
use Illuminate\Support\Str;

class PickupController extends Controller
{
    public function providerPickupCreation(ProviderPickupCreationRequest $request)
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
}
