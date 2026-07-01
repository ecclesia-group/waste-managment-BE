<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Admin and MMDA assign providers to zones via provider_zones (zone_id). */
class ZoneAssignmentService
{
    public function listProviderZoneAssignments(string $providerSlug): Collection
    {
        return DB::table('provider_zones')
            ->join('zones', 'zones.id', '=', 'provider_zones.zone_id')
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

    public function assignZonesToProvider(string $providerSlug, array $zoneIds): void
    {
        foreach (array_unique($zoneIds) as $zoneId) {
            DB::table('provider_zones')->updateOrInsert(
                ['provider_slug' => $providerSlug, 'zone_id' => (int) $zoneId],
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
    public function syncProviderZones(string $providerSlug, array $zoneIds): void
    {
        $zoneIds = array_values(array_unique(array_map('intval', $zoneIds)));

        $query = DB::table('provider_zones')->where('provider_slug', $providerSlug);
        if ($zoneIds === []) {
            $query->update(['status' => 'revoked', 'updated_at' => now()]);

            return;
        }

        $query->whereNotIn('zone_id', $zoneIds)
            ->update(['status' => 'revoked', 'updated_at' => now()]);

        $this->assignZonesToProvider($providerSlug, $zoneIds);
    }

    public function setProviderZones(string $providerSlug, array $zoneIds, bool $replace = false): void
    {
        if ($replace) {
            $this->syncProviderZones($providerSlug, $zoneIds);
        } else {
            $this->assignZonesToProvider($providerSlug, $zoneIds);
        }
    }

    public function revokeProviderZone(string $providerSlug, int $zoneId): bool
    {
        return DB::table('provider_zones')
            ->where('provider_slug', $providerSlug)
            ->where('zone_id', $zoneId)
            ->update(['status' => 'revoked', 'updated_at' => now()]) > 0;
    }

    public function providerSlugsInZone(int $zoneId): array
    {
        return DB::table('provider_zones')
            ->where('zone_id', $zoneId)
            ->where('status', 'active')
            ->pluck('provider_slug')
            ->all();
    }
}
