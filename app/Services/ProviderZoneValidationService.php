<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Zone;
use App\Support\Geo\PointInPolygon;
use Illuminate\Support\Collection;

class ProviderZoneValidationService
{
    /**
     * Client is allowed if they have coordinates and fall inside at least one assigned zone polygon.
     * If no zone has polygon data, any client under the provider with coordinates is allowed (backward compatible).
     *
     * @param  Collection<int, Zone>  $assignedZones
     */
    public function clientIsWithinAssignedZones(Client $client, Collection $assignedZones): bool
    {
        if ($client->latitude === null || $client->longitude === null) {
            return false;
        }

        $lat = (float) $client->latitude;
        $lng = (float) $client->longitude;

        if ($assignedZones->isEmpty()) {
            return true;
        }

        $zonesWithBoundary = $assignedZones->filter(fn (Zone $z) => ! empty($z->locations));

        if ($zonesWithBoundary->isEmpty()) {
            return true;
        }

        foreach ($zonesWithBoundary as $zone) {
            $locations = $zone->locations;
            if (PointInPolygon::locationsContainPoint($locations, $lat, $lng)) {
                return true;
            }
        }

        return false;
    }
}
