<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminZoneCreationRequest;
use App\Http\Requests\Admin\AdminZoneUpdationRequest;
use App\Models\Zone;

class AdminZoneManagementController extends Controller
{
    // Lists all zones
    public function listZones()
    {
        $zones = Zone::all();
        if ($zones->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Unsuccessful",
                reason: "No zones found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Zones retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $zones->toArray()
        );
    }

    // Gets details of a single zone
    public function getZoneDetails($zone_id)
    {
        $zone = Zone::find($zone_id);
        if (! $zone) {
            return self::apiResponse(
                in_error: true,
                message: "Action Unsuccessful",
                reason: "Zone not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Zone details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $zone->toArray()
        );
    }

    // Creates a new zone
    public function createZone(AdminZoneCreationRequest $request)
    {
        $data = $request->validated();
        $zone = Zone::create($data);
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Zone created successfully",
            status_code: self::API_SUCCESS,
            data: $zone->toArray()
        );
    }

    // Updates an existing zone
    public function updateZone(AdminZoneUpdationRequest $request, $zone_id)
    {
        $data = $request->validated();
        $zone = Zone::find($zone_id);
        if (! $zone) {
            return self::apiResponse(
                in_error: true,
                message: "Action Unsuccessful",
                reason: "Zone not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        $zone->update($data);
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Zone updated successfully",
            status_code: self::API_SUCCESS,
            data: $zone->toArray()
        );
    }

    // Deletes a zone
    public function deleteZone($zone_id)
    {
        $zone = Zone::find($zone_id);
        if (! $zone) {
            return self::apiResponse(
                in_error: true,
                message: "Action Unsuccessful",
                reason: "Zone not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        $zone->delete();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Zone deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}
