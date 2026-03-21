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
        $data                  = $request->validated();
        $data['code']          = Str::random(5);
        $data['fleet_slug']    = Str::uuid();
        $user                  = Auth::guard('provider')->user();
        $data['provider_slug'] = $user->provider_slug;

        $image_fields = [
            'vehicle_images',
            'vehicle_registration_certificate_image',
            'vehicle_insurance_certificate_image',
            'vehicle_roadworthy_certificate_image',
        ];

        $data  = static::processImage($image_fields, $data);
        $fleet = Fleet::create($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Fleet registered successfully",
            status_code: self::API_SUCCESS,
            data: $fleet->toArray()
        );
    }

    public function allFleets()
    {
        $user   = Auth::guard('provider')->user();
        $fleets = Fleet::where('provider_slug', $user->provider_slug)->get();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Fleets retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $fleets->toArray()
        );
    }

    public function show(Fleet $fleet)
    {
        $user = Auth::guard('provider')->user();
        if ((string) $fleet->provider_slug !== (string) $user->provider_slug) {
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
            data: $fleet->toArray()
        );
    }

    public function updateStatus(FleetStatusUpdateRequest $request)
    {
        $data          = $request->validated();
        $user          = Auth::guard('provider')->user();
        $fleet         = Fleet::query()
            ->where('fleet_slug', $data['fleet_slug'])
            ->where('provider_slug', $user->provider_slug)
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
            data: $fleet->toArray()
        );
    }

    public function updateFleet(UpdateFleetRequest $request, Fleet $fleet)
    {
        $user = Auth::guard('provider')->user();
        if ((string) $fleet->provider_slug !== (string) $user->provider_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to update this fleet",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $data         = $request->validated();

        // Tenant isolation: never allow provider re-assignment via payload.
        $data['provider_slug'] = $user->provider_slug;
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
        if ((string) $fleet->provider_slug !== (string) $user->provider_slug) {
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
