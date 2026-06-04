<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ZoneAssignmentService
{
    public function zonesForMmda(string $districtAssemblySlug): Collection
    {
        return DB::table('district_assembly_zones')
            ->join('zones', 'zones.zone_slug', '=', 'district_assembly_zones.zone_slug')
            ->where('district_assembly_zones.district_assembly_slug', $districtAssemblySlug)
            ->where('district_assembly_zones.status', 'active')
            ->select(
                'zones.*',
                'district_assembly_zones.assigned_at',
                'district_assembly_zones.status as assignment_status'
            )
            ->orderBy('zones.name')
            ->get();
    }

    public function mmdaZoneSlugs(string $districtAssemblySlug): array
    {
        return DB::table('district_assembly_zones')
            ->where('district_assembly_slug', $districtAssemblySlug)
            ->where('status', 'active')
            ->pluck('zone_slug')
            ->all();
    }

    public function assignZonesToMmda(string $districtAssemblySlug, array $zoneSlugs): void
    {
        foreach (array_unique($zoneSlugs) as $zoneSlug) {
            DB::table('district_assembly_zones')->updateOrInsert(
                ['district_assembly_slug' => $districtAssemblySlug, 'zone_slug' => $zoneSlug],
                [
                    'assigned_at' => now(),
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function assertZonesBelongToMmda(string $districtAssemblySlug, array $zoneSlugs): bool
    {
        if ($zoneSlugs === []) {
            return false;
        }

        $allowed = $this->mmdaZoneSlugs($districtAssemblySlug);
        if ($allowed === []) {
            return false;
        }

        return count(array_diff($zoneSlugs, $allowed)) === 0;
    }

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
}
