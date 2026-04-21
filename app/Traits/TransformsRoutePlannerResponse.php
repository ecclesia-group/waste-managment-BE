<?php

namespace App\Traits;

use App\Models\RoutePlanner;
use App\Models\RoutePlannerBinAssignment;

trait TransformsRoutePlannerResponse
{
    use HasClientMapPayload;

    /**
     * Shape route planner "create route" / assignment payload for the Route Planner UI
     * (provider sidebar, driver, fleet, group, assignment summary, per-client rows, map pins).
     */
    protected static function transformRoutePlannerForFrontend(RoutePlanner $plan): array
    {
        $plan->loadMissing([
            'provider',
            'driver',
            'fleet',
            'group',
            'assignments.pickup',
            'assignments.client.group',
        ]);

        /** @var \Illuminate\Support\Collection<int, RoutePlannerBinAssignment> $assignments */
        $assignments = $plan->assignments->sortBy('client_slug')->values();

        $scanned = 0;
        $unscanned = 0;
        $clientsPayload = [];
        $binsPayload = [];

        foreach ($assignments as $assignment) {
            $client = $assignment->client;
            $pickup = $assignment->pickup;

            $assignmentScan = $assignment->scan_status ?? 'pending';
            $pickupScan = $pickup?->scan_status;
            $effectiveForPin = $assignmentScan === 'scanned' || $pickupScan === 'scanned';

            if ($effectiveForPin) {
                $scanned++;
            } else {
                $unscanned++;
            }

            $uiBinStatus = match ($assignmentScan) {
                'scanned' => 'scanned',
                'pending', 'not_scanned' => 'unscanned',
                default => 'unscanned',
            };

            if ($client) {
                $fullName = trim(($client->first_name ?? '').' '.($client->last_name ?? ''));
                $coords = static::clientCoordinatesForMap($client);
                $group = $client->group;

                $clientsPayload[] = [
                    'client_slug' => $client->client_slug,
                    'customer_id' => $client->bin_registration_number
                        ? (string) $client->bin_registration_number
                        : (string) $client->id,
                    'full_name' => $fullName,
                    'phone_number' => $client->phone_number,
                    'email' => $client->email,
                    'gps_address' => $client->gps_address,
                    'pickup_location' => $client->pickup_location,
                    'category' => $client->type,
                    'bin_code' => $client->bin_code,
                    'group_slug' => $client->group_slug,
                    'group_name' => $group?->name ?? $plan->group?->name,
                    'group_tag' => ($group?->name) ?? ($plan->group?->name) ?? $client->group_slug,
                    'coordinates' => $coords,
                    'assignment' => [
                        'pickup_code' => $assignment->pickup_code,
                        'scan_status' => $assignmentScan,
                        'map_marker_color' => $effectiveForPin ? 'green' : 'red',
                        'is_scanned' => $effectiveForPin,
                    ],
                    'pickup' => $pickup ? [
                        'code' => $pickup->code,
                        'scan_status' => $pickup->scan_status,
                        'status' => $pickup->status,
                        'location' => $pickup->location,
                        'pickup_date' => $pickup->pickup_date,
                    ] : null,
                ];
            }

            $binsPayload[] = [
                'pickup_code' => $assignment->pickup_code,
                'client_slug' => $assignment->client_slug,
                'stop_order' => $assignment->stop_order,
                'eta_minutes' => $assignment->eta_minutes,
                'scan_status' => $uiBinStatus,
                'map_marker_color' => $uiBinStatus === 'scanned' ? 'green' : 'red',
                'scanned_at' => $assignment->scanned_at?->toISOString(),
                'unscanned_at' => $assignment->unscanned_at?->toISOString(),
                'coordinates' => static::clientCoordinatesForMap($client),
                'client' => $client?->only([
                    'client_slug',
                    'first_name',
                    'last_name',
                    'phone_number',
                    'email',
                    'gps_address',
                    'pickup_location',
                    'type',
                    'bin_code',
                ]),
                'pickup' => $pickup?->only([
                    'code',
                    'scan_status',
                    'status',
                    'location',
                    'pickup_date',
                ]),
            ];
        }

        return [
            'assignment' => [
                'id' => $plan->id,
                'route_planner_id' => $plan->id,
                'status' => $plan->status,
                'provider_slug' => $plan->provider_slug,
                'driver_slug' => $plan->driver_slug,
                'fleet_slug' => $plan->fleet_slug,
                'group_slug' => $plan->group_slug,
                'created_at' => $plan->created_at,
                'updated_at' => $plan->updated_at,
                'summary' => [
                    'total_clients' => count($clientsPayload),
                    'scanned' => $scanned,
                    'unscanned' => $unscanned,
                ],
                'route_meta' => $plan->route_meta,
            ],
            'provider' => ($p = $plan->provider) ? static::transformRoutePlannerProviderBrief($p) : null,
            'driver' => ($d = $plan->driver) ? static::transformRoutePlannerDriverBrief($d) : null,
            'fleet' => ($f = $plan->fleet) ? static::transformRoutePlannerFleetBrief($f) : null,
            'group' => ($g = $plan->group) ? static::transformRoutePlannerGroupBrief($g) : null,
            'clients' => $clientsPayload,
            'bins' => $binsPayload,
        ];
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
            'business_registration_number' => $provider->business_registration_number,
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
