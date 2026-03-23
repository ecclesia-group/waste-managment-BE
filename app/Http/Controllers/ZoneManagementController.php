<?php
namespace App\Http\Controllers;

use App\Http\Requests\Zone\ZoneCreationRequest;
use App\Http\Requests\Zone\ZoneStatusUpdateRequest;
use App\Http\Requests\Zone\ZoneUpdationRequest;
use App\Models\Provider;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ZoneManagementController extends Controller
{
    // Lists all zones
    public function listZones()
    {
        $zones = Zone::all();
        if ($zones->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "No Zones Found",
                reason: "No zones are registered in the system",
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
    public function createZone(ZoneCreationRequest $request)
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

    // Updates the status of a zone
    public function updateZoneStatus(ZoneStatusUpdateRequest $request)
    {
        $data        = $request->validated();
        $zone        = Zone::where('zone_slug', $data['zone_slug'])->first();
        $zone->status = $data['status'];
        $zone->save();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Zone status updated successfully",
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

    // -------- Admin Zone <-> Provider assignments --------

    // Lists all zones assigned to a provider (admin use).
    public function listProviderZones(Provider $provider)
    {
        $assignments = DB::table('provider_zone_assignments')
            ->join('zones', 'zones.zone_slug', '=', 'provider_zone_assignments.zone_slug')
            ->where('provider_zone_assignments.provider_slug', $provider->provider_slug)
            ->select(
                'provider_zone_assignments.zone_slug',
                'provider_zone_assignments.provider_slug',
                'provider_zone_assignments.assigned_at',
                'provider_zone_assignments.status',
                'zones.name as zone_name',
                'zones.region as zone_region',
                'zones.description as zone_description',
                'zones.locations as zone_locations',
                'zones.status as zone_status'
            )
            ->orderByDesc('provider_zone_assignments.assigned_at')
            ->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider zones retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $assignments->toArray()
        );
    }

    // Assign/activate zones for a provider (admin use).
    // This endpoint does not revoke zones automatically; use revoke endpoint for that.
    public function assignProviderZones(Request $request, Provider $provider)
    {
        $data = $request->validate([
            'zone_slugs' => ['required', 'array', 'min:1'],
            'zone_slugs.*' => ['required', 'string', 'distinct', 'exists:zones,zone_slug'],
        ]);

        $zoneSlugs = array_values($data['zone_slugs']);

        foreach ($zoneSlugs as $zoneSlug) {
            DB::table('provider_zone_assignments')->updateOrInsert(
                ['provider_slug' => $provider->provider_slug, 'zone_slug' => $zoneSlug],
                [
                    'assigned_at' => now(),
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $assignments = DB::table('provider_zone_assignments')
            ->join('zones', 'zones.zone_slug', '=', 'provider_zone_assignments.zone_slug')
            ->where('provider_zone_assignments.provider_slug', $provider->provider_slug)
            ->select(
                'provider_zone_assignments.zone_slug',
                'provider_zone_assignments.provider_slug',
                'provider_zone_assignments.assigned_at',
                'provider_zone_assignments.status',
                'zones.name as zone_name',
                'zones.region as zone_region',
                'zones.description as zone_description',
                'zones.locations as zone_locations',
                'zones.status as zone_status'
            )
            ->orderByDesc('provider_zone_assignments.assigned_at')
            ->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider zones assigned successfully",
            status_code: self::API_SUCCESS,
            data: $assignments->toArray()
        );
    }

    // Revoke a specific provider zone and suspend provider (admin use).
    public function revokeProviderZone(Request $request, Provider $provider, Zone $zone)
    {
        $updated = DB::table('provider_zone_assignments')
            ->where('provider_slug', $provider->provider_slug)
            ->where('zone_slug', $zone->zone_slug)
            ->update(['status' => 'revoked', 'updated_at' => now()]);

        if ($updated === 0) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Provider zone assignment not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        // Operational safety: revoke implies provider suspension (matches your doc requirement).
        $provider->status = 'deactivate';
        $provider->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider zone revoked and provider suspended successfully",
            status_code: self::API_SUCCESS,
            data: [
                'provider' => $provider->toArray(),
                'zone' => $zone->toArray(),
            ]
        );
    }
}
