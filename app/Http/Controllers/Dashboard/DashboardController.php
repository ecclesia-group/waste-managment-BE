<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DistrictAssembly;
use App\Models\Facility;
use App\Models\Pickup;
use App\Models\Provider;
use App\Models\WasteHandoverRequest;
use App\Models\WeighbridgeRecord;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function clientDashboard(Request $request)
    {
        $client = $request->user();
        $provider = Provider::query()->where('provider_slug', $client->provider_slug)->first();
        $district = DistrictAssembly::query()->where('district_assembly_slug', $provider?->district_assembly)->first();
        $primaryZoneId = $provider
            ? DB::table('provider_zones')
                ->where('provider_slug', $provider->provider_slug)
                ->where('status', 'active')
                ->orderByDesc('assigned_at')
                ->value('zone_id')
            : null;
        $zone = $primaryZoneId
            ? Zone::query()->find($primaryZoneId)
            : null;

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client dashboard retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'client' => $client->toArray(),
                'provider' => $provider?->toArray(),
                'district_assembly' => $district?->toArray(),
                'zone' => $zone?->toArray(),
            ]
        );
    }

    public function providerDashboard(Request $request)
    {
        $provider = $request->user();
        $effectiveProviderSlug = self::providerSlug($provider);
        $providerModel = Provider::query()->where('provider_slug', $effectiveProviderSlug)->first();
        $district = DistrictAssembly::query()->where('district_assembly_slug', $providerModel?->district_assembly)->first();
        $zones = $providerModel?->zones()->get() ?? collect();

        $pendingHandover = WasteHandoverRequest::query()
            ->where(function ($q) use ($effectiveProviderSlug) {
                $q->forProvider($effectiveProviderSlug, 'requester_provider_slug')
                    ->orWhere('target_provider_slug', $effectiveProviderSlug);
            })
            ->whereIn('status', ['pending', 'accepted'])
            ->count();

        $scannedBins = Pickup::query()
            ->whereNotNull('route_planner_id')
            ->forProvider($effectiveProviderSlug)
            ->where('scan_status', 'scanned')
            ->count();

        $groups = $providerModel?->groups()->get() ?? collect();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider dashboard retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'provider' => ($providerModel ?? $provider)->toArray(),
                'district_assembly' => $district?->toArray(),
                'zones' => $zones?->toArray(),
                'groups' => $groups->load('clients')->toArray(),
                'pending_handover_requests_count' => $pendingHandover,
                'scanned_bins_count' => $scannedBins,
                'groups_count' => $groups->count(),
                'zones_count' => $zones->count(),
                'customers_count' => $providerModel?->customers()->count() ?? 0,
                'drivers_count' => $providerModel?->drivers()->count() ?? 0,
                'fleets_count' => $providerModel?->fleets()->count() ?? 0,
                'routes_count' => $providerModel?->routes()->count() ?? 0,
                'plans_count' => $providerModel?->routes()->count() ?? 0,
                'assignments_count' => $providerModel?->routes()->count() ?? 0,
            ]
        );
    }

    public function facilityDashboard(Request $request)
    {
        $facility = $request->user();
        $effectiveFacilitySlug = $facility->facility_slug;
        $effectiveDistrictSlug = $facility->district_assembly;

        $district = DistrictAssembly::query()
            ->where('district_assembly_slug', $effectiveDistrictSlug)
            ->first();

        $todayFrom = Carbon::today()->startOfDay();
        $todayTo = Carbon::today()->endOfDay();

        $weekFrom = Carbon::now()->startOfWeek();
        $monthFrom = Carbon::now()->startOfMonth();

        $base = WeighbridgeRecord::query()->where('facility_slug', $effectiveFacilitySlug);

        $todayTotal = (clone $base)->whereBetween('created_at', [$todayFrom, $todayTo])->sum('amount');
        $weekTotal = (clone $base)->where('created_at', '>=', $weekFrom)->sum('amount');
        $monthTotal = (clone $base)->where('created_at', '>=', $monthFrom)->sum('amount');
        $allTimeTotal = (clone $base)->sum('amount');

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Facility dashboard retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'facility' => $facility->toArray(),
                'district_assembly' => $district?->toArray(),
                'waste_overview' => [
                    'today_total_amount' => (float) $todayTotal,
                    'week_total_amount' => (float) $weekTotal,
                    'month_total_amount' => (float) $monthTotal,
                    'all_time_total_amount' => (float) $allTimeTotal,
                ],
            ]
        );
    }

    public function districtAssemblyDashboard(Request $request)
    {
        $user = $request->user();
        $districtSlug = $user->district_assembly_slug;

        $providerSlugs = Provider::query()
            ->where('district_assembly', $districtSlug)
            ->pluck('provider_slug')
            ->toArray();

        $facilitySlugs = Facility::query()
            ->where('district_assembly', $districtSlug)
            ->pluck('facility_slug')
            ->toArray();

        $pendingAssignments = Pickup::query()
            ->whereNotNull('route_planner_id')
            ->whereIn('provider_slug', $providerSlugs)
            ->whereIn('scan_status', ['unscanned', 'pending', 'not_scanned'])
            ->count();

        $scannedAssignments = Pickup::query()
            ->whereNotNull('route_planner_id')
            ->whereIn('provider_slug', $providerSlugs)
            ->where('scan_status', 'scanned')
            ->count();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "District assembly dashboard retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'district_assembly' => $user->toArray(),
                'providers_total' => count($providerSlugs),
                'facilities_total' => count($facilitySlugs),
                'assignments' => [
                    'pending_or_unscanned' => $pendingAssignments,
                    'scanned' => $scannedAssignments,
                ],
            ]
        );
    }

    /**
     * Map-oriented pickup assignments: lat/lng, scan state, zone & MMDA hints.
     *
     * @queryParam group_by provider|plan|group|zone|mmda
     */
    public function mapPickupOverview(Request $request)
    {
        $user = $request->user();
        $groupBy = $request->string('group_by', 'provider')->toString();

        $query = Pickup::query()
            ->whereNotNull('route_planner_id')
            ->with(['client'])
            ->orderByDesc('updated_at');

        if (isset($user->provider_slug)) {
            $query->forProvider((string) self::providerSlug($user));
        } elseif (isset($user->district_assembly_slug)) {
            $query->whereIn('provider_slug', Provider::query()
                ->where('district_assembly', $user->district_assembly_slug)
                ->pluck('provider_slug'));
        }

        if ($request->filled('plan_id')) {
            $query->where('route_planner_id', (int) $request->integer('plan_id'));
        }
        if ($request->filled('group_slug')) {
            $query->where('group_slug', $request->string('group_slug'));
        }

        $pickups = $query->limit(500)->get();

        $providerDistricts = Provider::query()
            ->whereIn('provider_slug', $pickups->pluck('provider_slug')->unique()->filter())
            ->pluck('district_assembly', 'provider_slug');
        $providerZones = DB::table('provider_zones')
            ->whereIn('provider_slug', $pickups->pluck('provider_slug')->unique()->filter())
            ->where('status', 'active')
            ->orderByDesc('assigned_at')
            ->get(['provider_slug', 'zone_id'])
            ->groupBy('provider_slug')
            ->map(fn ($rows) => (int) optional($rows->first())->zone_id);

        $items = $pickups->map(function (Pickup $p) use ($providerDistricts, $providerZones) {
            $c = $p->client;
            $zoneId = $providerZones[$p->provider_slug] ?? null;

            return [
                'route_planner_id' => $p->route_planner_id,
                'pickup_code' => $p->code,
                'provider_slug' => $p->provider_slug,
                'provider_id' => $p->provider_slug,
                'group_slug' => $p->group_slug,
                'zone_id' => $zoneId,
                'mmda_slug' => $providerDistricts[$p->provider_slug] ?? null,
                'latitude' => $c?->latitude !== null ? (float) $c->latitude : null,
                'longitude' => $c?->longitude !== null ? (float) $c->longitude : null,
                'scanned' => $p->scan_status === 'scanned',
                'scan_status' => $p->scan_status,
            ];
        });

        $groups = match ($groupBy) {
            'plan' => $items->groupBy('route_planner_id')->map->values(),
            'group' => $items->groupBy('group_slug')->map->values(),
            'zone' => $items->groupBy('zone_id')->map->values(),
            'mmda' => $items->groupBy('mmda_slug')->map->values(),
            default => $items->groupBy('provider_slug')->map->values(),
        };

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Map pickup overview retrieved successfully',
            status_code: self::API_SUCCESS,
            data: [
                'group_by' => $groupBy,
                'groups' => $groups,
                'items' => $items->values()->all(),
            ]
        );
    }
}
