<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Admin assigns providers and facilities to zones (provider_zones / facility_zones).
 */
class ZoneAssignmentService
{
    public function assignZonesToProvider(string $providerSlug, array $zoneSlugs): void
    {
        foreach (array_unique($zoneSlugs) as $zoneSlug) {
            DB::table('provider_zones')->updateOrInsert(
                ['provider_slug' => $providerSlug, 'zone_slug' => $zoneSlug],
                [
                    'assigned_at' => now(),
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function assignZonesToFacility(string $facilitySlug, array $zoneSlugs): void
    {
        foreach (array_unique($zoneSlugs) as $zoneSlug) {
            DB::table('facility_zones')->updateOrInsert(
                ['facility_slug' => $facilitySlug, 'zone_slug' => $zoneSlug],
                [
                    'assigned_at' => now(),
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function providerSlugsInZone(string $zoneSlug): array
    {
        return DB::table('provider_zones')
            ->where('zone_slug', $zoneSlug)
            ->where('status', 'active')
            ->pluck('provider_slug')
            ->all();
    }

    public function facilitySlugsInZone(string $zoneSlug): array
    {
        return DB::table('facility_zones')
            ->where('zone_slug', $zoneSlug)
            ->where('status', 'active')
            ->pluck('facility_slug')
            ->all();
    }

    /** Zones linked to providers/facilities under a district assembly. */
    public function zoneSlugsForDistrict(string $districtAssemblySlug): array
    {
        $providerSlugs = DB::table('providers')
            ->where('district_assembly', $districtAssemblySlug)
            ->pluck('provider_slug');

        $facilitySlugs = DB::table('facilities')
            ->where('district_assembly', $districtAssemblySlug)
            ->pluck('facility_slug');

        return collect()
            ->merge(
                DB::table('provider_zones')
                    ->whereIn('provider_slug', $providerSlugs)
                    ->where('status', 'active')
                    ->pluck('zone_slug')
            )
            ->merge(
                DB::table('facility_zones')
                    ->whereIn('facility_slug', $facilitySlugs)
                    ->where('status', 'active')
                    ->pluck('zone_slug')
            )
            ->unique()
            ->values()
            ->all();
    }
}
