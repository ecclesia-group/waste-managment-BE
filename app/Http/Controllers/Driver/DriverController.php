<?php
namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\RegisterRequest;
use App\Http\Requests\Driver\StatusRequest;
use App\Http\Requests\Driver\UpdateProfileRequest;
use App\Models\Driver;
use Illuminate\Support\Str;

class DriverController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user                      = auth()->user();
        $data['provider_slug']     = $user->provider_slug;
        dd($data, $user, $user);
        $password                  = Str::random(8);
        $data                      = $request->validated();
        $data['driver_slug']       = Str::uuid();
        $data['password']          = $password;
        $data['email_verified_at'] = now();

        // get all images and check for bases 64 or url business_certificate_image, district_assembly_contract_image, tax_certificate_image, epa_permit_image, profile_image
        $image_fields = [
            'license_front_image',
            'license_back_image',
            'profile_image',
        ];

        $data   = static::processImage($image_fields, $data);
        $driver = Driver::create($data);

        self::sendEmail(
            $driver->email,
            email_class: "App\Mail\ActorAccountCreationMail",
            parameters: [
                $driver->email,
                $password,
                $driver->phone_number,
            ]
        );

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Driver registered successfully",
            status_code: self::API_SUCCESS,
            data: $driver->toArray()
        );
    }

    public function allDrivers()
    {
        $user    = auth()->user();
        $drivers = Driver::where('provider_slug', $user->provider_slug)->get();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Drivers retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $drivers->toArray()
        );
    }

    public function show(Driver $driver)
    {
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $driver->toArray()
        );
    }

    public function updateStatus(StatusRequest $request)
    {
        $data           = $request->validated();
        $driver         = Driver::where('driver_slug', $data['driver_slug'])->first();
        $driver->status = $data['status'];
        $driver->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Driver status updated successfully",
            status_code: self::API_SUCCESS,
            data: $driver->toArray()
        );
    }

    public function updateDriverProfile(UpdateProfileRequest $request, Driver $driver)
    {
        $data         = $request->validated();
        $image_fields = [
            'license_front_image',
            'license_back_image',
            'profile_image',
        ];

        $data = static::processImage($image_fields, $data);
        $driver->update($data);
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Driver details updated successfully",
            status_code: self::API_SUCCESS,
            data: $driver->toArray()
        );
    }

    public function deleteDriver(Driver $driver)
    {
        $driver->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Driver deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}
