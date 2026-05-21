<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GhanaPostGpsService
{
    private const ENDPOINT = 'https://ghanapostgps.sperixlabs.org/get-location';

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public function resolveCoordinates(string $gpsAddress): ?array
    {
        $address = trim($gpsAddress);
        if ($address === '') {
            return null;
        }

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->post(self::ENDPOINT, [
                    'address' => $address,
                ]);

            if (! $response->successful()) {
                Log::warning('Ghana Post GPS lookup failed', [
                    'status' => $response->status(),
                    'address' => $address,
                ]);

                return null;
            }

            $payload = $response->json();
            if (! ($payload['found'] ?? false)) {
                return null;
            }

            $row = $payload['data']['Table'][0] ?? null;
            if (! is_array($row)) {
                return null;
            }

            $latitude = $row['CenterLatitude'] ?? null;
            $longitude = $row['CenterLongitude'] ?? null;

            if ($latitude === null || $longitude === null) {
                return null;
            }

            return [
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
            ];
        } catch (\Throwable $e) {
            Log::warning('Ghana Post GPS lookup exception', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
