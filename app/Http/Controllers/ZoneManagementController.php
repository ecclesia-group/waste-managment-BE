<?php

namespace App\Http\Controllers;

use App\Http\Requests\Zone\ZoneCreationRequest;
use App\Http\Requests\Zone\ZoneStatusUpdateRequest;
use App\Http\Requests\Zone\ZoneUpdationRequest;
use App\Models\Client;
use App\Models\Pickup;
use App\Models\Provider;
use App\Models\RoutePlanner;
use App\Models\Zone;
use App\Services\ZoneAssignmentService;
use App\Traits\RespondsWithZoneAssignments;
use Illuminate\Http\Request;

/** Admin zone CRUD and zone assignment for providers and facilities. */
class ZoneManagementController extends Controller
{
    use RespondsWithZoneAssignments;
    public function listZones()
    {
        return $this->paginatedApiResponse(
            Zone::query()->orderBy('name')->paginate($this->perPage(request())),
            'All zones retrieved successfully'
        );
    }

    public function getZoneDetails(Zone $zone)
    {
        return $this->zoneOverview($zone);
    }

    /** Zone profile with counts only — use paginated zone/* endpoints for lists. */
    public function zoneOverview(Zone $zone)
    {
        $zones = app(ZoneAssignmentService::class);
        $providerSlugs = $zones->providerSlugsInZone((int) $zone->id);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Zone overview retrieved successfully',
            status_code: self::API_SUCCESS,
            data: [
                'zone' => $zone->toArray(),
                // 'providers_count' => count($providerSlugs),
                // 'clients_count' => Client::query()
                //     ->whereIn('provider_slug', $providerSlugs)
                //     ->where('status', 'active')
                //     ->count(),
                // 'pickups_count' => Pickup::query()
                //     ->whereIn('provider_slug', $providerSlugs)
                //     ->count(),
                // 'route_plans_count' => RoutePlanner::query()
                //     ->whereIn('provider_slug', $providerSlugs)
                //     ->count(),
            ]
        );
    }

    public function createZone(ZoneCreationRequest $request)
    {
        $data = $request->validated();
        $data['status'] = $data['status'] ?? 'active';
        $data['admin_slug'] = auth('admin')->user()->admin_slug;
        $zone = Zone::create($data);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Zone created successfully',
            status_code: self::API_SUCCESS,
            data: $zone->toArray()
        );
    }

    public function updateZone(ZoneUpdationRequest $request, Zone $zone)
    {
        $data = $request->validated();
        $data['admin_slug'] = auth('admin')->user()->admin_slug;
        $zone->update($data);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Zone updated successfully',
            status_code: self::API_SUCCESS,
            data: $zone->toArray()
        );
    }

    public function updateZoneStatus(ZoneStatusUpdateRequest $request)
    {
        $data = $request->validated();
        $zone = Zone::query()->findOrFail($data['zone_id']);
        $zone->status = $data['status'];
        $zone->admin_slug = auth('admin')->user()->admin_slug;
        $zone->save();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Zone status updated successfully',
            status_code: self::API_SUCCESS,
            data: $zone->toArray()
        );
    }

    public function deleteZone(Zone $zone)
    {
        $zone->delete();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Zone deleted successfully',
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    public function listProviderZones(Provider $provider)
    {
        return $this->listProviderZonesResponse($provider);
    }

    public function assignProviderZones(Request $request, Provider $provider)
    {
        return $this->assignProviderZonesResponse($request, $provider);
    }

    public function revokeProviderZone(Request $request, Provider $provider, Zone $zone)
    {
        return $this->revokeProviderZoneResponse($provider, $zone);
    }
}
