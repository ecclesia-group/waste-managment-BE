<?php

namespace App\Traits;

use App\Models\Client;
use App\Models\Pickup;

trait HasClientMapPayload
{
    /**
     * @return array{latitude: ?float, longitude: ?float, map_ready: bool}
     */
    protected static function clientCoordinatesForMap(?Client $client): array
    {
        if ($client === null) {
            return ['latitude' => null, 'longitude' => null, 'map_ready' => false];
        }

        $lat = $client->latitude;
        $lng = $client->longitude;

        if ($lat === null || $lat === '' || $lng === null || $lng === '') {
            return ['latitude' => null, 'longitude' => null, 'map_ready' => false];
        }

        return [
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
            'map_ready' => true,
        ];
    }

    /**
     * Pickups + Route Planner map UI: pickup row with customer, group tag, and coordinates.
     */
    protected static function enrichPickupForPickupUi(Pickup $pickup): array
    {
        $pickup->loadMissing(['client.group']);
        $client = $pickup->client;
        // $group = $client?->group;
        $coords = static::clientCoordinatesForMap($client);

        return array_merge($pickup->toArray(), [
            'map' => [
                'coordinates' => $coords,
            ],
            'provider' => $pickup->provider->toArray(),
            // 'customer' => $client ? [
            //     'client_slug' => $client->client_slug,
            //     'full_name' => trim(($client->first_name ?? '').' '.($client->last_name ?? '')),
            //     'phone_number' => $client->phone_number,
            //     'email' => $client->email,
            //     'gps_address' => $client->gps_address,
            //     'pickup_location' => $client->pickup_location,
            //     'category' => $client->type,
            //     'bin_code' => $client->bin_code,
            //     'group_slug' => $client->group_slug,
            //     'group_name' => $group?->name,
            //     'group_tag' => $group?->name ?? $group?->group_slug ?? $client->group_slug,
            //     'coordinates' => $coords,
            // ] : null,
        ]);
    }
}
