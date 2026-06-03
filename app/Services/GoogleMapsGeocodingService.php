<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapsGeocodingService
{
    private const GEOCODE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public function resolveCoordinates(string $address): ?array
    {
        $address = trim($address);
        $apiKey = (string) config('services.google.maps_key');

        if ($address === '' || $apiKey === '') {
            return null;
        }

        try {
            $query = [
                'address' => $address,
                'key' => $apiKey,
            ];

            $region = (string) config('services.google.geocode_region', 'gh');
            if ($region !== '') {
                $query['region'] = $region;
            }

            $response = Http::timeout(20)
                ->acceptJson()
                ->get(self::GEOCODE_URL, $query);

            if (! $response->successful()) {
                Log::warning('Google Maps geocoding HTTP error', [
                    'status' => $response->status(),
                    'address' => $address,
                ]);

                return null;
            }

            $payload = $response->json();
            $status = (string) ($payload['status'] ?? '');

            if ($status !== 'OK') {
                Log::warning('Google Maps geocoding API status', [
                    'status' => $status,
                    'address' => $address,
                    'error_message' => $payload['error_message'] ?? null,
                ]);

                return null;
            }

            $location = $payload['results'][0]['geometry']['location'] ?? null;
            if (! is_array($location)) {
                return null;
            }

            $latitude = $location['lat'] ?? null;
            $longitude = $location['lng'] ?? null;

            if ($latitude === null || $longitude === null) {
                return null;
            }

            return [
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
            ];
        } catch (\Throwable $e) {
            Log::warning('Google Maps geocoding exception', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
