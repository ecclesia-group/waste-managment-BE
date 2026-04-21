<?php

namespace App\Support\Geo;

/**
 * Ray-casting point-in-polygon for WGS84 lat/lng (adequate for small municipal polygons).
 */
final class PointInPolygon
{
    /**
     * @param  array<int, array{0: float, 1: float}|array{lat: float, lng: float}>  $ring  Closed ring in order (lng/lat pairs or lat/lng assoc).
     */
    public static function ringContainsPoint(array $ring, float $lat, float $lng): bool
    {
        if (count($ring) < 3) {
            return false;
        }

        $pairs = self::normalizeRing($ring);
        $inside = false;
        $n = count($pairs);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = $pairs[$i];
            [$xj, $yj] = $pairs[$j];
            $intersect = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi);
            if ($intersect) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * @param  mixed  $locations  Decoded JSON: GeoJSON Polygon coordinates, [[lng,lat],...], or [{lat,lng},...]
     */
    public static function locationsContainPoint(mixed $locations, float $lat, float $lng): bool
    {
        if ($locations === null) {
            return false;
        }
        if (is_string($locations)) {
            $decoded = json_decode($locations, true);
            $locations = is_array($decoded) ? $decoded : null;
        }
        if (! is_array($locations) || $locations === []) {
            return false;
        }

        // GeoJSON Polygon: [ [ [lng,lat], ... ] ]
        if (isset($locations[0][0]) && is_array($locations[0][0])) {
            $ring = $locations[0];
            if (isset($ring[0][0]) && is_numeric($ring[0][0])) {
                return self::ringContainsPoint($ring, $lat, $lng);
            }
        }

        // Single ring [[lng,lat],...]
        if (isset($locations[0]) && is_array($locations[0]) && isset($locations[0][0]) && is_numeric($locations[0][0])) {
            return self::ringContainsPoint($locations, $lat, $lng);
        }

        // [{lat,lng},...]
        if (isset($locations[0]['lat'])) {
            return self::ringContainsPoint($locations, $lat, $lng);
        }

        return false;
    }

    /**
     * @return array<int, array{0: float, 1: float}>
     */
    private static function normalizeRing(array $ring): array
    {
        $out = [];
        foreach ($ring as $pt) {
            if (is_array($pt) && isset($pt['lat'], $pt['lng'])) {
                $out[] = [(float) $pt['lng'], (float) $pt['lat']];
            } elseif (is_array($pt) && count($pt) >= 2) {
                $a = (float) $pt[0];
                $b = (float) $pt[1];
                // Heuristic: if first value looks like latitude (Ghana ~5–11), treat as lat,lng
                if (abs($a) <= 90 && abs($b) <= 180 && abs($a) > abs($b)) {
                    $out[] = [$b, $a];
                } else {
                    $out[] = [$a, $b];
                }
            }
        }

        return $out;
    }
}
