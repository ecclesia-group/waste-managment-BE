<?php

namespace App\Services;

use App\Models\BulkWasteRequest;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Group;
use App\Models\Pickup;
use App\Models\RoutePlanner;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class RoutePlannerService
{
    public const PICKUP_TYPE_NORMAL = 'normal';

    public const PICKUP_TYPE_BULK = 'bulk_waste_request';

    /** @return array{pickup_types: list<string>, normal: array<string, mixed>, bulk_waste_request: array<string, mixed>} */
    public function planOptionsForProvider(string $providerSlug): array
    {
        $groups = Group::query()
            ->where('provider_slug', $providerSlug)
            ->where('status', 'active')
            ->with(['clients' => fn ($query) => $query
                ->where('provider_slug', $providerSlug)
                ->where('status', 'active')])
            ->orderBy('name')
            ->get();

        $bulkRequests = BulkWasteRequest::query()
            ->with('client')
            ->where('provider_slug', $providerSlug)
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();

        return [
            'pickup_types' => [self::PICKUP_TYPE_NORMAL, self::PICKUP_TYPE_BULK],
            'normal' => [
                'label' => 'Normal pickup (select groups)',
                'description' => 'Choose one or more groups. All active clients in those groups become route stops.',
                'groups' => $groups->map(fn (Group $group) => [
                    'group_slug' => $group->group_slug,
                    'name' => $group->name,
                    'description' => $group->description,
                    'clients_count' => $group->clients->count(),
                    'clients' => $group->clients->map(fn (Client $client) => [
                        'client_slug' => $client->client_slug,
                        'first_name' => $client->first_name,
                        'last_name' => $client->last_name,
                        'phone_number' => $client->phone_number,
                        'gps_address' => $client->gps_address,
                        'pickup_location' => $client->pickup_location,
                    ])->values()->all(),
                ])->values()->all(),
            ],
            'bulk_waste_request' => [
                'label' => 'Bulk waste pickup (select request codes)',
                'description' => 'Choose approved bulk waste request codes. Clients behind those requests become route stops.',
                'bulk_waste_requests' => $bulkRequests->map(fn (BulkWasteRequest $bulk) => [
                    'request_code' => $bulk->request_code,
                    'title' => $bulk->title,
                    'status' => $bulk->status,
                    'amount' => $bulk->amount,
                    'pickup_date' => $bulk->pickup_date,
                    'client' => $bulk->client ? [
                        'client_slug' => $bulk->client->client_slug,
                        'first_name' => $bulk->client->first_name,
                        'last_name' => $bulk->client->last_name,
                        'phone_number' => $bulk->client->phone_number,
                        'gps_address' => $bulk->client->gps_address,
                    ] : null,
                ])->values()->all(),
            ],
        ];
    }

    /**
     * @param  array{
     *     provider_slug: string,
     *     driver_slug: string,
     *     fleet_slug: string,
     *     pickup_type: string,
     *     pickup_date?: string|null,
     *     group_slugs?: list<string>|null,
     *     bulk_request_codes?: list<string>|null,
     *     status?: string|null
     * }  $data
     */
    public function createPlan(array $data): RoutePlanner
    {
        $pickupType = (string) $data['pickup_type'];
        $pickupDate = isset($data['pickup_date'])
            ? Carbon::parse($data['pickup_date'])
            : Carbon::now();

        $groupSlugs = collect($data['group_slugs'] ?? [])->filter()->unique()->values();
        $bulkCodes = collect($data['bulk_request_codes'] ?? [])->filter()->unique()->values();

        $this->assertValidPickupTypeSelection($pickupType, $groupSlugs, $bulkCodes);

        if (! $this->authorizePlanResources($data['provider_slug'], $data['driver_slug'], $data['fleet_slug'], $pickupType, $groupSlugs, $bulkCodes)) {
            throw new InvalidArgumentException('Driver, fleet, group, or bulk request is not valid for this provider');
        }

        [$clients, $bulkByClient] = $this->resolvePlanClients(
            $data['provider_slug'],
            $pickupType,
            $groupSlugs,
            $bulkCodes
        );

        if ($clients->isEmpty()) {
            throw new RuntimeException(
                $pickupType === self::PICKUP_TYPE_BULK
                    ? 'No approved bulk waste requests found for the selected codes'
                    : 'No active clients found in the selected groups'
            );
        }

        return DB::transaction(function () use ($data, $pickupType, $pickupDate, $groupSlugs, $bulkCodes, $clients, $bulkByClient) {
            $routePlanner = RoutePlanner::create([
                'provider_slug' => $data['provider_slug'],
                'driver_slug' => $data['driver_slug'],
                'fleet_slug' => $data['fleet_slug'],
                'group_slug' => $pickupType === self::PICKUP_TYPE_NORMAL ? $groupSlugs->first() : null,
                'pickup_date' => $pickupDate,
                'pickup_type' => $pickupType,
                'status' => $data['status'] ?? 'pending',
                'route_meta' => [
                    'pickup_type' => $pickupType,
                    'pickup_date' => $pickupDate->toISOString(),
                    'selected_group_slugs' => $pickupType === self::PICKUP_TYPE_NORMAL ? $groupSlugs->all() : [],
                    'selected_bulk_request_codes' => $pickupType === self::PICKUP_TYPE_BULK ? $bulkCodes->all() : [],
                ],
            ]);

            $this->createPlanStops($routePlanner, $clients, $bulkByClient, $pickupType, $pickupDate);

            return $routePlanner->load([
                'provider',
                'driver',
                'fleet',
                'pickups.client.group',
            ]);
        });
    }

    private function assertValidPickupTypeSelection(
        string $pickupType,
        Collection $groupSlugs,
        Collection $bulkCodes
    ): void {
        if ($pickupType === self::PICKUP_TYPE_NORMAL) {
            if ($groupSlugs->isEmpty()) {
                throw new InvalidArgumentException('Select at least one group for a normal pickup plan');
            }

            if ($bulkCodes->isNotEmpty()) {
                throw new InvalidArgumentException('bulk_request_codes are not used for normal pickup plans');
            }

            return;
        }

        if ($pickupType === self::PICKUP_TYPE_BULK) {
            if ($bulkCodes->isEmpty()) {
                throw new InvalidArgumentException('Select at least one bulk waste request code for a bulk pickup plan');
            }

            if ($groupSlugs->isNotEmpty()) {
                throw new InvalidArgumentException('group_slugs are not used for bulk waste pickup plans');
            }

            return;
        }

        throw new InvalidArgumentException('pickup_type must be normal or bulk_waste_request');
    }

    /**
     * @return array{0: Collection<int, Client>, 1: Collection<string, BulkWasteRequest>}
     */
    private function resolvePlanClients(
        string $providerSlug,
        string $pickupType,
        Collection $groupSlugs,
        Collection $bulkCodes
    ): array {
        $bulkByClient = collect();

        if ($pickupType === self::PICKUP_TYPE_BULK) {
            $bulkByClient = BulkWasteRequest::query()
                ->where('provider_slug', $providerSlug)
                ->where('status', 'approved')
                ->whereIn('request_code', $bulkCodes->all())
                ->get()
                ->keyBy('client_slug');

            $clients = Client::query()
                ->where('provider_slug', $providerSlug)
                ->where('status', 'active')
                ->whereIn('client_slug', $bulkByClient->keys()->all())
                ->get();

            return [$clients, $bulkByClient];
        }

        $clients = Client::query()
            ->where('provider_slug', $providerSlug)
            ->where('status', 'active')
            ->whereIn('group_slug', $groupSlugs->all())
            ->get();

        return [$clients, $bulkByClient];
    }

    /**
     * Create one pickup stop per client, linked directly to the plan via route_planner_id.
     */
    private function createPlanStops(
        RoutePlanner $plan,
        Collection $clients,
        Collection $bulkByClient,
        string $pickupType,
        Carbon $pickupDate
    ): void {
        foreach ($clients as $client) {
            $bulkRequest = $bulkByClient->get($client->client_slug);

            Pickup::create([
                'code' => $this->generateUniquePickupCode(),
                'route_planner_id' => $plan->id,
                'provider_slug' => $plan->provider_slug,
                'client_slug' => $client->client_slug,
                'group_slug' => $pickupType === self::PICKUP_TYPE_NORMAL ? $client->group_slug : null,
                'bulk_waste_request_code' => $bulkRequest?->request_code,
                'title' => $bulkRequest?->title ?? 'Scheduled pickup',
                'category' => $pickupType === self::PICKUP_TYPE_BULK ? 'bulk_waste_request' : 'normal_pickup',
                'description' => $bulkRequest?->description,
                'amount' => $bulkRequest?->amount,
                'status' => 'scheduled',
                'scan_status' => 'unscanned',
                'location' => $client->pickup_location ?: ($client->gps_address ?: 'Unknown'),
                'images' => $bulkRequest?->images,
                'pickup_date' => $pickupDate,
            ]);

            if ($bulkRequest) {
                $this->scheduleBulkRequest($bulkRequest, $pickupDate);
            }
        }
    }

    private function scheduleBulkRequest(BulkWasteRequest $bulkRequest, Carbon $pickupDate): void
    {
        $bulkRequest->pickup_date = $pickupDate;
        $bulkRequest->status = 'scheduled';

        if ($bulkRequest->payment_status === null) {
            $bulkRequest->payment_status = ((float) ($bulkRequest->amount ?? 0)) > 0 ? 'unpaid' : 'paid';
        }

        $bulkRequest->save();
    }

    private function authorizePlanResources(
        string $providerSlug,
        string $driverSlug,
        string $fleetSlug,
        string $pickupType,
        Collection $groupSlugs,
        Collection $bulkCodes
    ): bool {
        if (! Driver::query()
            ->where('driver_slug', $driverSlug)
            ->where('provider_slug', $providerSlug)
            ->exists()) {
            return false;
        }

        if (! Fleet::query()
            ->where('fleet_slug', $fleetSlug)
            ->where('provider_slug', $providerSlug)
            ->exists()) {
            return false;
        }

        if ($pickupType === self::PICKUP_TYPE_NORMAL) {
            $count = Group::query()
                ->whereIn('group_slug', $groupSlugs->all())
                ->where('provider_slug', $providerSlug)
                ->count();

            return $count === $groupSlugs->count();
        }

        $bulkCount = BulkWasteRequest::query()
            ->where('provider_slug', $providerSlug)
            ->where('status', 'approved')
            ->whereIn('request_code', $bulkCodes->all())
            ->count();

        return $bulkCount === $bulkCodes->count();
    }

    private function generateUniquePickupCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Pickup::where('code', $code)->exists());

        return $code;
    }
}
