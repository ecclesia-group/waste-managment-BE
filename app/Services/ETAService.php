<?php

namespace App\Services;

use App\Support\Geo\Haversine;
use Illuminate\Support\Facades\Http;

class ETAService
{
    /**
     * ETA in minutes for each leg (driver → stop1 → stop2 …).
     *
     * @param  array{latitude: float, longitude: float}|null  $origin
     * @param  list<array{latitude: float, longitude: float}>  $stops
     * @return list<int>
     */
    public function legEtasMinutes(?array $origin, array $stops): array
    {
        if ($stops === []) {
            return [];
        }

        $key = config('services.google.maps_key');
        if (! empty($key) && $origin !== null) {
            $matrix = $this->googleMatrixMinutes($origin, $stops, $key);
            if ($matrix !== null) {
                return $matrix;
            }
        }

        return $this->haversineLegMinutes($origin, $stops);
    }

    /**
     * @return list<int>|null
     */
    private function googleMatrixMinutes(array $origin, array $stops, string $apiKey): ?array
    {
        $origins = [$origin['latitude'].','.$origin['longitude']];
        $destinations = array_map(fn ($s) => $s['latitude'].','.$s['longitude'], $stops);

        try {
            $response = Http::timeout(20)->get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'origins' => implode('|', $origins),
                'destinations' => implode('|', $destinations),
                'mode' => 'driving',
                'key' => $apiKey,
            ]);
            if (! $response->successful()) {
                return null;
            }
            $json = $response->json();
            $row = $json['rows'][0]['elements'] ?? null;
            if (! is_array($row)) {
                return null;
            }
            $out = [];
            foreach ($row as $el) {
                if (($el['status'] ?? '') !== 'OK') {
                    return null;
                }
                $sec = (int) ($el['duration']['value'] ?? 0);
                $out[] = max(1, (int) round($sec / 60));
            }

            return $out;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<int>
     */
    private function haversineLegMinutes(?array $origin, array $stops): array
    {
        $prevLat = $origin['latitude'] ?? $stops[0]['latitude'];
        $prevLng = $origin['longitude'] ?? $stops[0]['longitude'];
        $kmh = 25;
        $out = [];
        foreach ($stops as $stop) {
            $m = Haversine::meters($prevLat, $prevLng, $stop['latitude'], $stop['longitude']);
            $out[] = max(1, (int) round(($m / 1000) / ($kmh / 60)));
            $prevLat = $stop['latitude'];
            $prevLng = $stop['longitude'];
        }

        return $out;
    }
}
