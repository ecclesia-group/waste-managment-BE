<?php
namespace App\Http\Controllers;

use App\Http\Requests\Zone\ZoneCreationRequest;
use App\Http\Requests\Zone\ZoneUpdationRequest;
use App\Models\Complaint;
use App\Models\Zone;
use Illuminate\Support\Str;

class ZoneManagementController extends Controller
{
    // Lists all zones
    public function listZones()
    {
        $zones = Zone::all();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Zones retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $zones->toArray()
        );
    }

    // Gets details of a single zone
    public function getZoneDetails(Zone $zone)
    {
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Zone details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $zone->toArray()
        );
    }

    // Creates a new zone
    public function create(CreationRequest $request)
    {
        $data              = $request->validated();
        $data['zone_slug'] = Str::uuid();
        $zone              = Zone::create($data);
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Zone created successfully",
            status_code: self::API_SUCCESS,
            data: $zone->toArray()
        );
    }

    // Updates an existing zone
    public function updateZone(ZoneUpdationRequest $request, Zone $zone)
    {
        $data = $request->validated();
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
    public function deleteZone(Zone $zone)
    {
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
