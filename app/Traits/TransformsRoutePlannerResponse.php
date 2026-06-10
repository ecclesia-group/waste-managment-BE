<?php

namespace App\Traits;

use App\Models\BulkWasteRequest;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Group;
use App\Models\Pickup;
use App\Models\RoutePlanner;
use App\Services\RoutePlannerService;
use Illuminate\Support\Collection;

/**
 * Maps route_planners + their pickups into frontend "assignments" with nested pickup stops.
 */
trait TransformsRoutePlannerResponse
{
    use HasClientMapPayload;

    /**
     * Frontend shape: assignment (plan) with nested pickups (per client stop).
     */
    protected static function transformAssignment(RoutePlanner $plan): array
    {
        $plan->loadMissing([
            'provider',
            'driver',
            'fleet',
            'pickups.client.group',
        ]);

        $pickups = [];
        $scanned = 0;
        $unscanned = 0;

        foreach ($plan->pickups as $pickup) {
            $client = $pickup->client;
            $coords = static::clientCoordinatesForMap($client);
            $scanStatus = $pickup->scan_status === 'scanned' ? 'scanned' : 'unscanned';

            if ($scanStatus === 'scanned') {
                $scanned++;
            } else {
                $unscanned++;
            }

            $pickups[] = [
                'pickup_code' => $pickup->code,
                'assignment_id' => $plan->id,
                'scan_status' => $scanStatus,
                'pickup_status' => $pickup->status,
                'pickup_date' => $pickup->pickup_date,
                'amount' => $pickup->amount,
                'bulk_waste_request_code' => $pickup->bulk_waste_request_code,
                'client' => [
                    'client_slug' => $pickup->client_slug,
                    'full_name' => trim(($client->first_name ?? '').' '.($client->last_name ?? '')),
                    'phone_number' => $client?->phone_number,
                    'email' => $client?->email,
                    'gps_address' => $client?->gps_address,
                    'pickup_location' => $client?->pickup_location,
                    'bin_code' => $client?->bin_code,
                    'group_slug' => $client?->group_slug,
                    'group_name' => $client?->group?->name,
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                    'map_ready' => $coords['map_ready'],
                ],
            ];
        }

        $pickupType = $plan->pickup_type ?? ($plan->route_meta['pickup_type'] ?? RoutePlannerService::PICKUP_TYPE_NORMAL);
        $selectedGroupSlugs = $plan->selectedGroupSlugs();
        $selectedBulkCodes = $plan->selectedBulkRequestCodes();

        return [
            'assignment_id' => $plan->id,
            'id' => $plan->id,
            'pickup_type' => $pickupType,
            'type' => $pickupType,
            'pickup_date' => $plan->pickup_date?->toISOString(),
            'status' => $plan->status,
            'provider_slug' => $plan->provider_slug,
            'driver_slug' => $plan->driver_slug,
            'fleet_slug' => $plan->fleet_slug,
            'selected_group_slugs' => $selectedGroupSlugs,
            'selected_bulk_request_codes' => $selectedBulkCodes,
            'selection' => static::transformPlanSelection($plan, $pickupType, $selectedGroupSlugs, $selectedBulkCodes),
            'driver' => ($d = $plan->driver) ? static::transformRoutePlannerDriverBrief($d) : null,
            'fleet' => ($f = $plan->fleet) ? static::transformRoutePlannerFleetBrief($f) : null,
            'summary' => [
                'total' => count($pickups),
                'scanned' => $scanned,
                'unscanned' => $unscanned,
            ],
            'pickups' => $pickups,
            'created_at' => $plan->created_at,
            'updated_at' => $plan->updated_at,
        ];
    }

    /**
     * @param  list<string>  $selectedGroupSlugs
     * @param  list<string>  $selectedBulkCodes
     * @return array<string, mixed>
     */
    protected static function transformPlanSelection(
        RoutePlanner $plan,
        string $pickupType,
        array $selectedGroupSlugs,
        array $selectedBulkCodes
    ): array {
        if ($pickupType === RoutePlannerService::PICKUP_TYPE_BULK) {
            $bulkRequests = BulkWasteRequest::query()
                ->with('client')
                ->where('provider_slug', $plan->provider_slug)
                ->whereIn('request_code', $selectedBulkCodes)
                ->get();

            return [
                'mode' => RoutePlannerService::PICKUP_TYPE_BULK,
                'bulk_waste_requests' => $bulkRequests->map(fn (BulkWasteRequest $bulk) => [
                    'request_code' => $bulk->request_code,
                    'title' => $bulk->title,
                    'status' => $bulk->status,
                    'client_slug' => $bulk->client_slug,
                    'client_name' => trim(($bulk->client?->first_name ?? '').' '.($bulk->client?->last_name ?? '')),
                ])->values()->all(),
            ];
        }

        $groups = Group::query()
            ->with(['clients' => fn ($query) => $query
                ->where('provider_slug', $plan->provider_slug)
                ->where('status', 'active')])
            ->where('provider_slug', $plan->provider_slug)
            ->whereIn('group_slug', $selectedGroupSlugs)
            ->get();

        return [
            'mode' => RoutePlannerService::PICKUP_TYPE_NORMAL,
            'groups' => $groups->map(fn (Group $group) => [
                'group_slug' => $group->group_slug,
                'name' => $group->name,
                'clients_count' => $group->clients->count(),
                'clients' => $group->clients->map(fn ($client) => [
                    'client_slug' => $client->client_slug,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'phone_number' => $client->phone_number,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    protected static function transformAssignmentsList(Collection $plans): array
    {
        return $plans->map(fn (RoutePlanner $plan) => static::transformAssignment($plan))->values()->all();
    }

    protected static function transformRoutePlannerForFrontend(RoutePlanner $plan): array
    {
        return static::transformAssignment($plan);
    }

    protected static function transformRoutePlannerDriverBrief(Driver $driver): array
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
            'phone_number' => $driver->phone_number,
            'email' => $driver->email,
            'display_label' => $full !== '' ? $full : $driver->driver_slug,
            'profile_image' => $profileUrl,
        ];
    }

    protected static function transformRoutePlannerFleetBrief(Fleet $fleet): array
    {
        return [
            'fleet_slug' => $fleet->fleet_slug,
            'license_plate' => $fleet->license_plate,
            'vehicle_make' => $fleet->vehicle_make,
            'model' => $fleet->model,
            'display_label' => $fleet->license_plate ? (string) $fleet->license_plate : $fleet->fleet_slug,
        ];
    }

    /**
     * Lean map payload for a plan: just what the frontend needs to plot pickup stops.
     * Each stop carries the client's coordinates (latitude/longitude/gps_address).
     */
    protected static function transformRoutePlannerMap(RoutePlanner $plan): array
    {
        $plan->loadMissing(['driver', 'fleet', 'pickups.client']);

        $scanned = 0;
        $mapReady = 0;

        $stops = $plan->pickups->map(function (Pickup $pickup) use (&$scanned, &$mapReady) {
            $client = $pickup->client;
            $coords = static::clientCoordinatesForMap($client);

            if ($pickup->scan_status === 'scanned') {
                $scanned++;
            }
            if ($coords['map_ready']) {
                $mapReady++;
            }

            return [
                'pickup_code' => $pickup->code,
                'scan_status' => $pickup->scan_status === 'scanned' ? 'scanned' : 'unscanned',
                'pickup_status' => $pickup->status,
                'pickup_date' => $pickup->pickup_date,
                'client' => [
                    'client_slug' => $pickup->client_slug,
                    'full_name' => trim(($client->first_name ?? '').' '.($client->last_name ?? '')),
                    'phone_number' => $client?->phone_number,
                    'gps_address' => $client?->gps_address,
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                    'map_ready' => $coords['map_ready'],
                ],
            ];
        })->values()->all();

        return [
            'route_planner_id' => $plan->id,
            'provider_slug' => $plan->provider_slug,
            'pickup_type' => $plan->pickup_type,
            'status' => $plan->status,
            'pickup_date' => $plan->pickup_date?->toISOString(),
            'driver' => ($d = $plan->driver) ? static::transformRoutePlannerDriverBrief($d) : null,
            'fleet' => ($f = $plan->fleet) ? static::transformRoutePlannerFleetBrief($f) : null,
            'summary' => [
                'total' => count($stops),
                'scanned' => $scanned,
                'unscanned' => count($stops) - $scanned,
                'map_ready' => $mapReady,
            ],
            'pickups' => $stops,
        ];
    }
}
