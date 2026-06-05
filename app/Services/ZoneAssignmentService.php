<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Admin and MMDA assign providers/facilities to zones (provider_zones / facility_zones).
 */
class ZoneAssignmentService
{
    public function listProviderZoneAssignments(string $providerSlug): Collection
    {
        return DB::table('provider_zones')
            ->join('zones', 'zones.zone_slug', '=', 'provider_zones.zone_slug')
            ->where('provider_zones.provider_slug', $providerSlug)
            ->select(
                'provider_zones.*',
                'zones.name',
                'zones.region',
                'zones.description',
                'zones.locations',
                'zones.status as zone_status'
            )
            ->orderByDesc('provider_zones.assigned_at')
            ->get();
    }

    public function listActiveProviderZoneAssignments(string $providerSlug): Collection
    {
        return $this->listProviderZoneAssignments($providerSlug)
            ->where('status', 'active')
            ->values();
    }

    public function listFacilityZoneAssignments(string $facilitySlug): Collection
    {
        return DB::table('facility_zones')
            ->join('zones', 'zones.zone_slug', '=', 'facility_zones.zone_slug')
            ->where('facility_zones.facility_slug', $facilitySlug)
            ->select(
                'facility_zones.*',
                'zones.name',
                'zones.region',
                'zones.description',
                'zones.locations',
                'zones.status as zone_status'
            )
            ->orderByDesc('facility_zones.assigned_at')
            ->get();
    }

    public function listActiveFacilityZoneAssignments(string $facilitySlug): Collection
    {
        return $this->listFacilityZoneAssignments($facilitySlug)
            ->where('status', 'active')
            ->values();
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

    /** Replace a provider's active zone set (reallocation). */
    public function syncProviderZones(string $providerSlug, array $zoneSlugs): void
    {
        $zoneSlugs = array_values(array_unique($zoneSlugs));

        $query = DB::table('provider_zones')->where('provider_slug', $providerSlug);
        if ($zoneSlugs === []) {
            $query->update(['status' => 'revoked', 'updated_at' => now()]);

            return;
        }

        $query->whereNotIn('zone_slug', $zoneSlugs)
            ->update(['status' => 'revoked', 'updated_at' => now()]);

        $this->assignZonesToProvider($providerSlug, $zoneSlugs);
    }

    /** Replace a facility's active zone set (reallocation). */
    public function syncFacilityZones(string $facilitySlug, array $zoneSlugs): void
    {
        $zoneSlugs = array_values(array_unique($zoneSlugs));

        $query = DB::table('facility_zones')->where('facility_slug', $facilitySlug);
        if ($zoneSlugs === []) {
            $query->update(['status' => 'revoked', 'updated_at' => now()]);

            return;
        }

        $query->whereNotIn('zone_slug', $zoneSlugs)
            ->update(['status' => 'revoked', 'updated_at' => now()]);

        $this->assignZonesToFacility($facilitySlug, $zoneSlugs);
    }

    public function setProviderZones(string $providerSlug, array $zoneSlugs, bool $replace = false): void
    {
        if ($replace) {
            $this->syncProviderZones($providerSlug, $zoneSlugs);
        } else {
            $this->assignZonesToProvider($providerSlug, $zoneSlugs);
        }
    }

    public function setFacilityZones(string $facilitySlug, array $zoneSlugs, bool $replace = false): void
    {
        if ($replace) {
            $this->syncFacilityZones($facilitySlug, $zoneSlugs);
        } else {
            $this->assignZonesToFacility($facilitySlug, $zoneSlugs);
        }
    }

    public function revokeProviderZone(string $providerSlug, string $zoneSlug): bool
    {
        return DB::table('provider_zones')
            ->where('provider_slug', $providerSlug)
            ->where('zone_slug', $zoneSlug)
            ->update(['status' => 'revoked', 'updated_at' => now()]) > 0;
    }

    public function revokeFacilityZone(string $facilitySlug, string $zoneSlug): bool
    {
        return DB::table('facility_zones')
            ->where('facility_slug', $facilitySlug)
            ->where('zone_slug', $zoneSlug)
            ->update(['status' => 'revoked', 'updated_at' => now()]) > 0;
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
