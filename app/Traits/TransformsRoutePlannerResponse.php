<?php

namespace App\Traits;

use App\Models\RoutePlanner;
use App\Models\RoutePlannerBinAssignment;
use Illuminate\Support\Collection;

trait TransformsRoutePlannerResponse
{
    use HasClientMapPayload;

    protected static function assignmentIsScanned(RoutePlannerBinAssignment $assignment): bool
    {
        $assignmentScan = (string) ($assignment->scan_status ?? 'unscanned');

        return $assignmentScan === 'scanned'
            || ($assignment->pickup?->scan_status === 'scanned');
    }

    protected static function assignmentUiScanStatus(RoutePlannerBinAssignment $assignment): string
    {
        $status = (string) ($assignment->scan_status ?? 'unscanned');

        return $status === 'scanned' ? 'scanned' : 'unscanned';
    }

    /**
     * Flat list of map markers for a plan (used on all_plans and detail).
     */
    protected static function buildPlanMapPins(RoutePlanner $plan): array
    {
        $plan->loadMissing(['assignments.client', 'assignments.pickup']);

        return $plan->assignments->map(function (RoutePlannerBinAssignment $assignment) {
            $client = $assignment->client;
            $coords = static::clientCoordinatesForMap($client);

            return [
                'client_slug' => $assignment->client_slug,
                'pickup_code' => $assignment->pickup_code,
                'full_name' => trim(($client->first_name ?? '').' '.($client->last_name ?? '')),
                'bin_code' => $client?->bin_code,
                'gps_address' => $client?->gps_address,
                'latitude' => $coords['latitude'],
                'longitude' => $coords['longitude'],
                'map_ready' => $coords['map_ready'],
                'scan_status' => static::assignmentUiScanStatus($assignment),
            ];
        })->values()->all();
    }

    /**
     * Provider plan list: type, provider, fleet, groups summary, scan counts, date, coordinates.
     */
    protected static function transformPlansList(Collection $plans): array
    {
        return $plans->map(function (RoutePlanner $plan) {
            $plan->loadMissing([
                'provider',
                'driver',
                'fleet',
                'assignments.client.group',
                'assignments.pickup',
            ]);

            $scanned = 0;
            $unscanned = 0;
            foreach ($plan->assignments as $assignment) {
                if (static::assignmentIsScanned($assignment)) {
                    $scanned++;
                } else {
                    $unscanned++;
                }
            }

            $groups = $plan->assignments
                ->groupBy(fn (RoutePlannerBinAssignment $a) => $a->client?->group_slug ?: 'ungrouped')
                ->map(function (Collection $rows, string $groupSlug) {
                    $first = $rows->first();
                    $group = $first?->client?->group;

                    return [
                        'group_slug' => $groupSlug === 'ungrouped' ? null : $groupSlug,
                        'name' => $group?->name ?? ($groupSlug === 'ungrouped' ? 'Ungrouped' : $groupSlug),
                        'clients_count' => $rows->count(),
                        'scanned' => $rows->filter(fn (RoutePlannerBinAssignment $a) => static::assignmentIsScanned($a))->count(),
                        'unscanned' => $rows->reject(fn (RoutePlannerBinAssignment $a) => static::assignmentIsScanned($a))->count(),
                    ];
                })
                ->values()
                ->all();

            return [
                'id' => $plan->id,
                'pickup_type' => $plan->pickup_type ?? ($plan->route_meta['pickup_type'] ?? 'normal'),
                'pickup_date' => $plan->pickup_date?->toISOString(),
                'status' => $plan->status,
                'provider' => ($p = $plan->provider) ? static::transformRoutePlannerProviderBrief($p) : null,
                'driver' => ($d = $plan->driver) ? static::transformRoutePlannerDriverBrief($d) : null,
                'fleet' => ($f = $plan->fleet) ? static::transformRoutePlannerFleetBrief($f) : null,
                'groups' => $groups,
                'summary' => [
                    'total' => $plan->assignments->count(),
                    'scanned' => $scanned,
                    'unscanned' => $unscanned,
                ],
                'map_pins' => static::buildPlanMapPins($plan),
                'route_meta' => $plan->route_meta,
                'created_at' => $plan->created_at,
            ];
        })->values()->all();
    }

    /**
     * Plan detail: groups with client rows (lat/lng, scan status, pickup, price for bulk).
     */
    protected static function transformPlanDetail(RoutePlanner $plan): array
    {
        $plan->loadMissing([
            'provider',
            'driver',
            'fleet',
            'assignments.client.group',
            'assignments.pickup',
        ]);

        $scanned = 0;
        $unscanned = 0;

        $groups = $plan->assignments
            ->groupBy(fn (RoutePlannerBinAssignment $a) => $a->client?->group_slug ?: 'ungrouped')
            ->map(function (Collection $rows, string $groupSlug) use (&$scanned, &$unscanned) {
                $first = $rows->first();
                $group = $first?->client?->group;

                $clients = $rows->map(function (RoutePlannerBinAssignment $assignment) use (&$scanned, &$unscanned) {
                    $client = $assignment->client;
                    $pickup = $assignment->pickup;
                    $isScanned = static::assignmentIsScanned($assignment);

                    if ($isScanned) {
                        $scanned++;
                    } else {
                        $unscanned++;
                    }

                    $coords = static::clientCoordinatesForMap($client);

                    return [
                        'client_slug' => $assignment->client_slug,
                        'full_name' => trim(($client->first_name ?? '').' '.($client->last_name ?? '')),
                        'phone_number' => $client?->phone_number,
                        'email' => $client?->email,
                        'gps_address' => $client?->gps_address,
                        'pickup_location' => $client?->pickup_location,
                        'bin_code' => $client?->bin_code,
                        'coordinates' => $coords,
                        'scan_status' => static::assignmentUiScanStatus($assignment),
                        'pickup_code' => $assignment->pickup_code,
                        'pickup' => $pickup ? [
                            'code' => $pickup->code,
                            'status' => $pickup->status,
                            'scan_status' => $pickup->scan_status,
                            'pickup_date' => $pickup->pickup_date,
                            'amount' => $pickup->amount,
                            'bulk_waste_request_code' => $pickup->bulk_waste_request_code,
                            'requires_payment_before_pickup' => ! empty($pickup->bulk_waste_request_code),
                        ] : null,
                    ];
                })->values()->all();

                return [
                    'group_slug' => $groupSlug === 'ungrouped' ? null : $groupSlug,
                    'name' => $group?->name ?? ($groupSlug === 'ungrouped' ? 'Ungrouped' : $groupSlug),
                    'clients' => $clients,
                ];
            })
            ->values()
            ->all();

        return [
            'id' => $plan->id,
            'pickup_type' => $plan->pickup_type ?? ($plan->route_meta['pickup_type'] ?? 'normal'),
            'pickup_date' => $plan->pickup_date?->toISOString(),
            'status' => $plan->status,
            'provider' => ($p = $plan->provider) ? static::transformRoutePlannerProviderBrief($p) : null,
            'driver' => ($d = $plan->driver) ? static::transformRoutePlannerDriverBrief($d) : null,
            'fleet' => ($f = $plan->fleet) ? static::transformRoutePlannerFleetBrief($f) : null,
            'groups' => $groups,
            'summary' => [
                'total' => $scanned + $unscanned,
                'scanned' => $scanned,
                'unscanned' => $unscanned,
            ],
            'map_pins' => static::buildPlanMapPins($plan),
            'route_meta' => $plan->route_meta,
            'created_at' => $plan->created_at,
            'updated_at' => $plan->updated_at,
        ];
    }

    protected static function transformRoutePlannerForFrontend(RoutePlanner $plan): array
    {
        return static::transformPlanDetail($plan);
    }

    protected static function transformRoutePlannerProviderBrief($provider): array
    {
        return [
            'provider_slug' => $provider->provider_slug,
            'display_name' => $provider->business_name
                ?: trim(($provider->first_name ?? '').' '.($provider->last_name ?? '')),
            'business_name' => $provider->business_name,
            'first_name' => $provider->first_name,
            'last_name' => $provider->last_name,
            'phone_number' => $provider->phone_number,
            'email' => $provider->email,
            'region' => $provider->region ?? null,
            'location' => $provider->location ?? null,
        ];
    }

    protected static function transformRoutePlannerDriverBrief($driver): array
    {
        $full = trim(implode(' ', array_filter([
            $driver->first_name,
            $driver->middle_name ?? null,
            $driver->last_name,
        ])));

        $profile = $driver->profile_image ?? null;
        $profileUrl = is_array($profile) ? ($profile[0] ?? null) : $profile;

        return [
            'driver_slug' => $driver->driver_slug,
            'full_name' => $full,
            'first_name' => $driver->first_name,
            'last_name' => $driver->last_name,
            'phone_number' => $driver->phone_number,
            'email' => $driver->email,
            'status' => $driver->status,
            'display_label' => $full !== '' ? $full : $driver->driver_slug,
            'profile_image' => $profileUrl,
            'coordinates' => ($driver->latitude !== null && $driver->longitude !== null)
                ? ['latitude' => (float) $driver->latitude, 'longitude' => (float) $driver->longitude, 'map_ready' => true]
                : ['latitude' => null, 'longitude' => null, 'map_ready' => false],
        ];
    }

    protected static function transformRoutePlannerFleetBrief($fleet): array
    {
        return [
            'fleet_slug' => $fleet->fleet_slug,
            'license_plate' => $fleet->license_plate,
            'vehicle_make' => $fleet->vehicle_make,
            'model' => $fleet->model,
            'display_label' => $fleet->license_plate ? (string) $fleet->license_plate : $fleet->fleet_slug,
            'owner_phone_number' => $fleet->owner_phone_number,
        ];
    }

    protected static function transformRoutePlannerGroupBrief($group): array
    {
        return [
            'group_slug' => $group->group_slug,
            'name' => $group->name,
            'description' => $group->description,
            'status' => $group->status,
        ];
    }
}
