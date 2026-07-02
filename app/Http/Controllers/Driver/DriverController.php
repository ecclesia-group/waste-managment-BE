<?php
namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\RegisterRequest;
use App\Http\Requests\Driver\StatusRequest;
use App\Http\Requests\Driver\UpdateProfileRequest;
use App\Events\DriverLocationUpdated;
use App\Models\Driver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DriverController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = Auth::guard('provider')->user();
        $actorSlug = self::providerScopeSlug($user);
        $password = Str::random(8);
        $data = static::formatPhoneNumbersInData($request->validated(), ['phone_number', 'emergency_phone_number']);
        $data['driver_slug'] = Str::uuid();
        $data['password'] = $password;
        $data['email_verified_at'] = now();
        $data['provider_slug'] = $actorSlug;

        $image_fields = [
            'license_front_image',
            'license_back_image',
            'profile_image',
        ];

        $data = static::processImage($image_fields, $data);
        $driver = Driver::create($data);
        $driver->load('provider');

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
            data: $this->transformDriver($driver)
        );
    }

    public function allDrivers()
    {
        $user = Auth::guard('provider')->user();
        $ownerSlug = self::providerScopeSlug($user);

        return $this->paginatedApiResponseMapped(
            Driver::query()
                ->with('provider')
                ->forProvider((string) $ownerSlug)
                ->latest()
                ->paginate($this->perPage(request())),
            'Drivers retrieved successfully',
            fn ($driver) => $this->transformDriver($driver),
        );
    }

    public function show(Driver $driver)
    {
        $user = Auth::guard('provider')->user();
        if ((string) $driver->provider_slug !== (string) self::providerScopeSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to view this driver",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $driver->load('provider');

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $this->transformDriver($driver)
        );
    }

    public function updateStatus(StatusRequest $request)
    {
        $data = static::formatPhoneNumbersInData($request->validated(), ['phone_number', 'emergency_phone_number']);
        $user = Auth::guard('provider')->user();
        $ownerSlug = self::providerScopeSlug($user);

        $driver = Driver::query()
            ->where('driver_slug', $data['driver_slug'])
            ->forProvider((string) $ownerSlug)
            ->first();

        if (! $driver) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Driver not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $driver->status = $data['status'];
        $driver->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Driver status updated successfully",
            status_code: self::API_SUCCESS,
            data: $this->transformDriver($driver)
        );
    }

    public function updateDriverProfile(UpdateProfileRequest $request, Driver $driver)
    {
        $user = Auth::guard('provider')->user();
        if ((string) $driver->provider_slug !== (string) self::providerScopeSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to update this driver",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $data = static::formatPhoneNumbersInData($request->validated(), ['phone_number', 'emergency_phone_number']);
        $image_fields = [
            'license_front_image',
            'license_back_image',
            'profile_image',
        ];

        $data = static::processImage($image_fields, $data);
        $driver->update($data);
        $driver->load('provider');

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Driver details updated successfully",
            status_code: self::API_SUCCESS,
            data: $this->transformDriver($driver)
        );
    }

    public function deleteDriver(Driver $driver)
    {
        $user = Auth::guard('provider')->user();
        if ((string) $driver->provider_slug !== (string) self::providerScopeSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to delete this driver",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $driver->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Driver deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    /**
     * Persist driver's current GPS fix for live tracking and route ETA refreshes.
     */
    public function updateLocation(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'driver_slug' => 'required|string|exists:drivers,driver_slug',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = Auth::guard('provider')->user();
        $ownerSlug = self::providerScopeSlug($user);
        $driver = Driver::query()
            ->where('driver_slug', $data['driver_slug'])
            ->forProvider((string) $ownerSlug)
            ->first();

        if (! $driver) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Driver not found',
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $driver->latitude = $data['latitude'];
        $driver->longitude = $data['longitude'];
        $driver->last_location_at = now();
        $driver->save();

        DriverLocationUpdated::dispatch($driver, (float) $data['latitude'], (float) $data['longitude']);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Driver location updated',
            status_code: self::API_SUCCESS,
            data: $driver->only(['driver_slug', 'latitude', 'longitude', 'last_location_at'])
        );
    }

    private function transformDriver(Driver $driver): array
    {
        $payload = $driver->toArray();
        $creator = $driver->provider;
        $payload['created_by_provider_slug'] = $driver->provider_slug;
        $payload['owner_provider_slug'] = $creator
            ? ((bool) ($creator->is_main ?? true)
                ? $creator->provider_slug
                : ($creator->parent_slug ?: $creator->provider_slug))
            : $driver->provider_slug;
        $payload['created_by'] = $creator ? [
            'provider_slug' => $creator->provider_slug,
            'is_main' => (bool) ($creator->is_main ?? true),
            'parent_slug' => $creator->parent_slug,
            'business_name' => $creator->business_name,
            'first_name' => $creator->first_name,
            'last_name' => $creator->last_name,
        ] : null;

        return $payload;
    }
}
