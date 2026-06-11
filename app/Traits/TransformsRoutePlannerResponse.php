<?php

namespace App\Traits;

use App\Models\BulkWasteRequest;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Group;
use App\Models\Pickup;
use App\Models\RoutePlanner;
use App\Services\RoutePlannerService;
use Illuminate\Support\Collection;

trait TransformsRoutePlannerResponse
{
    use HasClientMapPayload;

    protected static function transformRoutePlannerSummary(RoutePlanner $plan): array
    {
        $plan->loadMissing(['driver', 'fleet']);
        $plan->loadCount([
            'pickups as total_pickups',
            'pickups as scanned_pickups' => fn ($query) => $query->where('scan_status', 'scanned'),
        ]);

        $pickupType = $plan->pickup_type
            ?? ($plan->route_meta['pickup_type'] ?? RoutePlannerService::PICKUP_TYPE_NORMAL);

        $total = (int) ($plan->total_pickups ?? 0);
        $scanned = (int) ($plan->scanned_pickups ?? 0);

        $payload = [
            'id' => $plan->id,
            'status' => $plan->status,
            'pickup_type' => $pickupType,
            'pickup_date' => $plan->pickup_date?->toISOString(),
            'provider_slug' => $plan->provider_slug,
            'driver' => ($d = $plan->driver) ? static::transformRoutePlannerDriverBrief($d) : null,
            'fleet' => ($f = $plan->fleet) ? static::transformRoutePlannerFleetBrief($f) : null,
            'summary' => [
                'total' => $total,
                'scanned' => $scanned,
                'unscanned' => max(0, $total - $scanned),
            ],
            'groups' => $pickupType === RoutePlannerService::PICKUP_TYPE_NORMAL
                ? static::groupsBrief($plan->provider_slug, $plan->selectedGroupSlugs())
                : [],
            'created_at' => $plan->created_at,
            'updated_at' => $plan->updated_at,
        ];

        if ($pickupType === RoutePlannerService::PICKUP_TYPE_BULK) {
            $bulkRequests = static::bulkWasteRequestsBrief(
                $plan->provider_slug,
                $plan->selectedBulkRequestCodes()
            );
            $payload['bulk_waste_requests'] = $bulkRequests;
        }

        return $payload;
    }

    protected static function transformPickupStop(Pickup $pickup, ?int $routePlannerId = null): array
    {
        $client = $pickup->client;
        $coords = static::clientCoordinatesForMap($client);
        $routePlanner = $pickup->routePlanner;

        return [
            'id' => $pickup->id,
            'pickup_type' => $routePlanner?->pickup_type,
            'pickup_code' => $pickup->code,
            'scan_status' => $pickup->scan_status ?? 'unscanned',
            'status' => $pickup->status,
            'pickup_date' => $pickup->pickup_date,
            'amount' => $pickup->amount,
            'client' => [
                'client_slug' => $pickup->client_slug,
                'name' => trim(($client->first_name ?? '').' '.($client->last_name ?? '')),
                'gps_address' => $client?->gps_address,
                'pickup_location' => $client?->pickup_location,
                'latitude' => $coords['latitude'],
                'longitude' => $coords['longitude'],
                'bin_code' => $client?->bin_code,
                'group_slug' => $client?->group_slug,
                'group_name' => $client?->group?->name,
            ],
        ];
    }

    protected static function transformClientPickupDetails(Client $client): array
    {
        $client->loadMissing(['group', 'bin']);
        $coords = static::clientCoordinatesForMap($client);
        $bin = $client->primaryBin();

        return [
            'client_slug' => $client->client_slug,
            'name' => trim(($client->first_name ?? '').' '.($client->last_name ?? '')),
            'phone_number' => $client->phone_number,
            'email' => $client->email,
            'gps_address' => $client->gps_address,
            'pickup_location' => $client->pickup_location,
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'group' => $client->group ? [
                'group_slug' => $client->group->group_slug,
                'name' => $client->group->name,
            ] : null,
            'bin' => $bin ? [
                'bin_slug' => $bin->bin_slug,
                'bin_code' => $bin->bin_code,
                'status' => $bin->status,
            ] : null,
        ];
    }

    /**
     * @param  list<string>  $requestCodes
     * @return list<array<string, mixed>>
     */
    protected static function bulkWasteRequestsBrief(string $providerSlug, array $requestCodes): array
    {
        if ($requestCodes === []) {
            return [];
        }

        return BulkWasteRequest::query()
            ->with('client')
            ->where('provider_slug', $providerSlug)
            ->whereIn('request_code', $requestCodes)
            ->get()
            ->sortBy(fn (BulkWasteRequest $bulk) => array_search($bulk->request_code, $requestCodes, true))
            ->values()
            ->map(function (BulkWasteRequest $bulk) {
                $client = $bulk->client;
                $coords = static::clientCoordinatesForMap($client);

                return [
                    'request_code' => $bulk->request_code,
                    'title' => $bulk->title,
                    'status' => $bulk->status,
                    'amount' => $bulk->amount,
                    'pickup_date' => $bulk->pickup_date,
                    'client' => $client ? [
                        'client_slug' => $client->client_slug,
                        'name' => trim(($client->first_name ?? '').' '.($client->last_name ?? '')),
                        'phone_number' => $client->phone_number,
                        'gps_address' => $client->gps_address,
                        'pickup_location' => $client->pickup_location,
                        'latitude' => $coords['latitude'],
                        'longitude' => $coords['longitude'],
                    ] : null,
                ];
            })
            ->all();
    }

    /**
     * @param  list<string>  $groupSlugs
     * @return list<array{group_slug: string, name: string}>
     */
    protected static function groupsBrief(string $providerSlug, array $groupSlugs): array
    {
        if ($groupSlugs === []) {
            return [];
        }

        return Group::query()
            ->where('provider_slug', $providerSlug)
            ->whereIn('group_slug', $groupSlugs)
            ->orderBy('name')
            ->get(['group_slug', 'name'])
            ->map(fn (Group $group) => [
                'group_slug' => $group->group_slug,
                'name' => $group->name,
            ])
            ->values()
            ->all();
    }

    protected static function transformRoutePlannersList(Collection $plans): array
    {
        return $plans->map(fn (RoutePlanner $plan) => static::transformRoutePlannerSummary($plan))->values()->all();
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
}
