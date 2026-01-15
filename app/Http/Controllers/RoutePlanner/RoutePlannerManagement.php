<?php
namespace App\Http\Controllers\RoutePlanner;

use App\Http\Controllers\Controller;
use App\Models\RoutePlanner;
use Illuminate\Support\Str;

class RoutePlannerManagement extends Controller
{
    public function register(RegisterFleetRequest $request)
    {
        $data               = $request->validated();
        $data['code']       = Str::random(5);
        $data['fleet_slug'] = Str::uuid();

        $image_fields = [
            'vehicle_images',
            'vehicle_registration_certificate_image',
            'vehicle_insurance_certificate_image',
            'vehicle_roadworthy_certificate_image',
        ];

        $data         = static::processImage($image_fields, $data);
        $routePlanner = RoutePlanner::create($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route create successfully",
            status_code: self::API_SUCCESS,
            data: $routePlanner->toArray()
        );
    }

    public function allFleets()
    {
        $routePlanner = RoutePlanner::all();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Routes retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $routePlanner->toArray()
        );
    }

    public function show(RoutePlanner $routePlanner)
    {
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $routePlanner->toArray()
        );
    }

    public function updateStatus(FleetStatusUpdateRequest $request)
    {
        $data                 = $request->validated();
        $routePlanner         = RoutePlanner::where('fleet_slug', $data['fleet_slug'])->first();
        $routePlanner->status = $data[' '];
        $routePlanner->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route planner status updated successfully",
            status_code: self::API_SUCCESS,
            data: $routePlanner->toArray()
        );
    }

    public function updateFleet(UpdateFleetRequest $request, RoutePlanner $routePlanner)
    {
        $data         = $request->validated();
        $image_fields = [
            'vehicle_images',
            'vehicle_registration_certificate_image',
            'vehicle_insurance_certificate_image',
            'vehicle_roadworthy_certificate_image',
        ];

        $data = static::processImage($image_fields, $data);
        $routePlanner->update($data);
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route planner details updated successfully",
            status_code: self::API_SUCCESS,
            data: $routePlanner->toArray()
        );
    }

    public function deleteFleet(RoutePlanner $routePlanner)
    {
        $routePlanner->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route planner deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}
