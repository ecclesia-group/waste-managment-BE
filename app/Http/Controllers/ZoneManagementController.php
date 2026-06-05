<?php
namespace App\Http\Controllers;

use App\Http\Requests\Zone\ZoneCreationRequest;
use App\Http\Requests\Zone\ZoneStatusUpdateRequest;
use App\Http\Requests\Zone\ZoneUpdationRequest;
use App\Models\Client;
use App\Models\Facility;
use App\Models\Pickup;
use App\Models\Provider;
use App\Models\RoutePlanner;
use App\Models\RoutePlannerBinAssignment;
use App\Models\Zone;
use App\Services\ZoneAssignmentService;
use App\Traits\RespondsWithZoneAssignments;
use App\Traits\TransformsRoutePlannerResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Admin zone CRUD and zone assignment for providers and facilities. */
class ZoneManagementController extends Controller
{
    use RespondsWithZoneAssignments, TransformsRoutePlannerResponse;
    public function listZones()
    {
        $zones = Zone::query()->orderBy('name')->get();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'All zones retrieved successfully',
            status_code: self::API_SUCCESS,
            data: $zones->toArray()
        );
    }

    public function getZoneDetails(Zone $zone)
    {
        return $this->zoneOverview($zone);
    }

    /**
     * Zone hub: providers, facilities, clients, plans, assignment logs in this zone.
     */
    public function zoneOverview(Zone $zone)
    {
        $zones = app(ZoneAssignmentService::class);
        $providerSlugs = $zones->providerSlugsInZone($zone->zone_slug);
        $facilitySlugs = $zones->facilitySlugsInZone($zone->zone_slug);

        $providers = Provider::query()
            ->whereIn('provider_slug', $providerSlugs)
            ->get();

        $facilities = Facility::query()
            ->whereIn('facility_slug', $facilitySlugs)
            ->get();

        $clients = Client::query()
            ->whereIn('provider_slug', $providerSlugs)
            ->where('status', 'active')
            ->with('group')
            ->get();

        $plans = RoutePlanner::query()
            ->whereIn('provider_slug', $providerSlugs)
            ->with(['provider', 'driver', 'fleet', 'assignments.client.group', 'assignments.pickup'])
            ->latest()
            ->limit(50)
            ->get();

        $assignmentLogs = RoutePlannerBinAssignment::query()
            ->where('provider_slug', $providerSlugs)
            ->with(['client', 'pickup', 'routePlanner'])
            ->latest()
            ->limit(100)
            ->get();

        $pickups = Pickup::query()
            ->whereIn('provider_slug', $providerSlugs)
            ->latest()
            ->limit(100)
            ->get();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Zone overview retrieved successfully',
            status_code: self::API_SUCCESS,
            data: [
                'zone' => $zone->toArray(),
                'providers' => $providers->toArray(),
                'facilities' => $facilities->toArray(),
                'clients_count' => $clients->count(),
                'clients' => $clients->toArray(),
                'assignments' => self::transformAssignmentsList($plans),
                'assignment_logs' => $assignmentLogs->toArray(),
                'pickups' => $pickups->toArray(),
            ]
        );
    }

    public function createZone(ZoneCreationRequest $request)
    {
        $data = $request->validated();
        $data['zone_slug'] = (string) Str::uuid();
        $data['status'] = $data['status'] ?? 'active';
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
        $zone->update($request->validated());

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
        $zone = Zone::where('zone_slug', $data['zone_slug'])->firstOrFail();
        $zone->status = $data['status'];
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

    public function listFacilityZones(Facility $facility)
    {
        return $this->listFacilityZonesResponse($facility);
    }

    public function assignFacilityZones(Request $request, Facility $facility)
    {
        return $this->assignFacilityZonesResponse($request, $facility);
    }

    public function revokeProviderZone(Request $request, Provider $provider, Zone $zone)
    {
        return $this->revokeProviderZoneResponse($provider, $zone);
    }

    public function revokeFacilityZone(Request $request, Facility $facility, Zone $zone)
    {
        return $this->revokeFacilityZoneResponse($facility, $zone);
    }
}
