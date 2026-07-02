<?php
namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\FleetStatusUpdateRequest;
use App\Http\Requests\Fleet\RegisterFleetRequest;
use App\Http\Requests\Fleet\UpdateFleetRequest;
use App\Models\Fleet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FleetManagementController extends Controller
{
    public function register(RegisterFleetRequest $request)
    {
        $data = static::formatPhoneNumbersInData($request->validated(), ['owner_phone_number']);
        $data['fleet_slug'] = Str::uuid();
        $user = Auth::guard('provider')->user();
        $data['provider_slug'] = self::providerScopeSlug($user);

        $image_fields = [
            'vehicle_images',
            'vehicle_registration_certificate_image',
            'vehicle_insurance_certificate_image',
            'vehicle_roadworthy_certificate_image',
        ];

        $data = static::processImage($image_fields, $data);
        $fleet = Fleet::create($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Fleet registered successfully",
            status_code: self::API_SUCCESS,
            data: $fleet->load('provider')->toArray()
        );
    }

    public function getAllFleets()
    {
        return $this->paginatedApiResponse(
            Fleet::query()
                ->where('status', 'active')
                ->latest()
                ->paginate($this->perPage(request())),
            'All fleets retrieved successfully'
        );
    }

    public function allFleets()
    {
        $user = Auth::guard('provider')->user();
        $ownerSlug = self::providerScopeSlug($user);

        return $this->paginatedApiResponse(
            Fleet::query()
                ->forProvider((string) $ownerSlug)
                ->with('provider')
                ->latest()
                ->paginate($this->perPage(request())),
            'Fleets retrieved successfully'
        );
    }

    public function show(Fleet $fleet)
    {
        $user = Auth::guard('provider')->user();
        if ($user && (string) $fleet->provider_slug !== (string) self::providerScopeSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to view this fleet",
                status_code: self::API_FAIL,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Fleet details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $fleet->load('provider')->toArray()
        );
    }

    public function updateStatus(FleetStatusUpdateRequest $request)
    {
        $data = static::formatPhoneNumbersInData($request->validated(), ['owner_phone_number']);
        $user = Auth::guard('provider')->user();
        $ownerSlug = self::providerScopeSlug($user);
        $fleet = Fleet::query()
            ->where('fleet_slug', $data['fleet_slug'])
            ->forProvider((string) $ownerSlug)
            ->first();

        if (! $fleet) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Fleet not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $fleet->status = $data['status'];
        $fleet->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Fleet status updated successfully",
            status_code: self::API_SUCCESS,
            data: $fleet->load('provider')->toArray()
        );
    }

    public function updateFleet(UpdateFleetRequest $request, Fleet $fleet)
    {
        $user = Auth::guard('provider')->user();
        if ((string) $fleet->provider_slug !== (string) self::providerScopeSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to update this fleet",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $data = static::formatPhoneNumbersInData($request->validated(), ['owner_phone_number']);
        unset($data['provider_slug']);

        $image_fields = [
            'vehicle_images',
            'vehicle_registration_certificate_image',
            'vehicle_insurance_certificate_image',
            'vehicle_roadworthy_certificate_image',
        ];

        $data = static::processImage($image_fields, $data);
        $fleet->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Fleet details updated successfully",
            status_code: self::API_SUCCESS,
            data: $fleet->toArray()
        );
    }

    public function deleteFleet(Fleet $fleet)
    {
        $user = Auth::guard('provider')->user();
        if ((string) $fleet->provider_slug !== (string) self::providerScopeSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to delete this fleet",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $fleet->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Fleet deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}
