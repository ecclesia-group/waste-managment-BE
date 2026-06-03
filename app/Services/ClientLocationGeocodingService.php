<?php

namespace App\Services;

/**
 * Resolves client addresses to coordinates (Google Maps primary, Ghana Post GPS fallback).
 */
class ClientLocationGeocodingService
{
    public function __construct(
        private readonly GoogleMapsGeocodingService $googleMaps,
        private readonly GhanaPostGpsService $ghanaPostGps,
    ) {}

    /**
     * @return array{latitude: float, longitude: float, source: string}|null
     */
    public function resolveCoordinates(string $address): ?array
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        $google = $this->googleMaps->resolveCoordinates($address);
        if ($google !== null) {
            return array_merge($google, ['source' => 'google_maps']);
        }

        $ghanaPost = $this->ghanaPostGps->resolveCoordinates($address);
        if ($ghanaPost !== null) {
            return array_merge($ghanaPost, ['source' => 'ghana_post_gps']);
        }

        return null;
    }
}
