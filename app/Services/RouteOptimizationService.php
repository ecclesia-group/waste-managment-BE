<?php

namespace App\Services;

use App\Support\Geo\Haversine;
use Illuminate\Support\Facades\Http;

class RouteOptimizationService
{
    private const FALLBACK_SPEED_KMH = 25;

    /**
     * @param  array{latitude: float, longitude: float}|null  $driverLocation
     * @param  list<array{key: string, latitude: float, longitude: float}>  $stops
     * @return array{
     *   ordered_keys: list<string>,
     *   total_distance_meters: float,
     *   total_duration_seconds: int,
     *   encoded_polyline: string|null,
     *   source: string,
     *   leg_eta_minutes: list<int>
     * }
     */
    public function optimizeRoute(?array $driverLocation, array $stops): array
    {
        if ($stops === []) {
            return [
                'ordered_keys' => [],
                'total_distance_meters' => 0.0,
                'total_duration_seconds' => 0,
                'encoded_polyline' => null,
                'source' => 'empty',
                'leg_eta_minutes' => [],
            ];
        }

        $ordered = $this->nearestNeighborOrder($driverLocation, $stops);
        $key = config('services.google.maps_key');

        if (! empty($key) && $driverLocation !== null) {
            $google = $this->directionsForOrder($driverLocation, $ordered, $key);
            if ($google !== null) {
                return $google;
            }
        }

        return $this->fallbackMetrics($driverLocation, $ordered);
    }

    /**
     * @param  list<array{key: string, latitude: float, longitude: float}>  $orderedStops
     */
    private function directionsForOrder(array $driverLocation, array $orderedStops, string $apiKey): ?array
    {
        $origin = $driverLocation['latitude'].','.$driverLocation['longitude'];
        $last = $orderedStops[array_key_last($orderedStops)];
        $destination = $last['latitude'].','.$last['longitude'];

        $middle = array_slice($orderedStops, 0, -1);
        $query = [
            'origin' => $origin,
            'destination' => $destination,
            'key' => $apiKey,
            'mode' => 'driving',
        ];
        if ($middle !== []) {
            $query['waypoints'] = implode('|', array_map(
                fn ($s) => $s['latitude'].','.$s['longitude'],
                $middle
            ));
        }

        try {
            $response = Http::timeout(20)->get('https://maps.googleapis.com/maps/api/directions/json', $query);
            if (! $response->successful()) {
                return null;
            }
            $json = $response->json();
            if (($json['status'] ?? '') !== 'OK' || empty($json['routes'][0])) {
                return null;
            }
            $route = $json['routes'][0];
            $legs = $route['legs'] ?? [];
            $distance = 0.0;
            $duration = 0;
            $legEtas = [];
            foreach ($legs as $leg) {
                $distance += (float) ($leg['distance']['value'] ?? 0);
                $sec = (int) ($leg['duration']['value'] ?? 0);
                $duration += $sec;
                $legEtas[] = max(1, (int) round($sec / 60));
            }
            $poly = $route['overview_polyline']['points'] ?? null;

            return [
                'ordered_keys' => array_map(fn ($s) => $s['key'], $orderedStops),
                'total_distance_meters' => $distance,
                'total_duration_seconds' => $duration,
                'encoded_polyline' => is_string($poly) ? $poly : null,
                'source' => 'google_directions',
                'leg_eta_minutes' => $legEtas,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  list<array{key: string, latitude: float, longitude: float}>  $stops
     * @return list<array{key: string, latitude: float, longitude: float}>
     */
    private function nearestNeighborOrder(?array $driverLocation, array $stops): array
    {
        $remaining = $stops;
        $ordered = [];
        $currentLat = $driverLocation['latitude'] ?? $stops[0]['latitude'];
        $currentLng = $driverLocation['longitude'] ?? $stops[0]['longitude'];

        while ($remaining !== []) {
            $bestIdx = 0;
            $bestDist = PHP_FLOAT_MAX;
            foreach ($remaining as $idx => $stop) {
                $d = Haversine::meters($currentLat, $currentLng, $stop['latitude'], $stop['longitude']);
                if ($d < $bestDist) {
                    $bestDist = $d;
                    $bestIdx = $idx;
                }
            }
            $next = $remaining[$bestIdx];
            array_splice($remaining, $bestIdx, 1);
            $ordered[] = $next;
            $currentLat = $next['latitude'];
            $currentLng = $next['longitude'];
        }

        return $ordered;
    }

    /**
     * @param  list<array{key: string, latitude: float, longitude: float}>  $orderedStops
     */
    private function fallbackMetrics(?array $driverLocation, array $orderedStops): array
    {
        $prevLat = $driverLocation['latitude'] ?? $orderedStops[0]['latitude'];
        $prevLng = $driverLocation['longitude'] ?? $orderedStops[0]['longitude'];
        $totalM = 0.0;
        $legEtas = [];
        foreach ($orderedStops as $stop) {
            $m = Haversine::meters($prevLat, $prevLng, $stop['latitude'], $stop['longitude']);
            $totalM += $m;
            $kmh = self::FALLBACK_SPEED_KMH;
            $legEtas[] = max(1, (int) round(($m / 1000) / ($kmh / 60)));
            $prevLat = $stop['latitude'];
            $prevLng = $stop['longitude'];
        }
        $totalSeconds = array_sum(array_map(fn ($min) => $min * 60, $legEtas));

        return [
            'ordered_keys' => array_map(fn ($s) => $s['key'], $orderedStops),
            'total_distance_meters' => $totalM,
            'total_duration_seconds' => $totalSeconds,
            'encoded_polyline' => null,
            'source' => 'nearest_neighbor_haversine',
            'leg_eta_minutes' => $legEtas,
        ];
    }
}
