<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\DistrictAssembly;
use App\Models\Facility;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\RoutePlannerBinAssignment;
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
        $zone = Zone::query()->where('zone_slug', $provider?->zone_slug)->first();

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
        $district = DistrictAssembly::query()->where('district_assembly_slug', $provider->district_assembly)->first();
        $zone = Zone::query()->where('zone_slug', $provider->zone_slug)->first();
        $effectiveProviderSlug = $provider->provider_slug;

        $pendingHandover = WasteHandoverRequest::query()
            ->where(function ($q) use ($effectiveProviderSlug) {
                $q->where('requester_provider_slug', $effectiveProviderSlug)
                    ->orWhere('target_provider_slug', $effectiveProviderSlug);
            })
            ->whereIn('status', ['pending', 'accepted'])
            ->count();

        $scannedBins = RoutePlannerBinAssignment::query()
            ->where('provider_slug', $effectiveProviderSlug)
            ->where('scan_status', 'scanned')
            ->count();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider dashboard retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'provider' => $provider->toArray(),
                'district_assembly' => $district?->toArray(),
                'zone' => $zone?->toArray(),
                'pending_handover_requests_count' => $pendingHandover,
                'scanned_bins_count' => $scannedBins,
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

        $pendingAssignments = RoutePlannerBinAssignment::query()
            ->whereIn('provider_slug', $providerSlugs)
            ->whereIn('scan_status', ['pending', 'not_scanned'])
            ->count();

        $scannedAssignments = RoutePlannerBinAssignment::query()
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
}

