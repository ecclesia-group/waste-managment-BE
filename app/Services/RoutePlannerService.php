<?php

namespace App\Services;

use App\Models\BulkWasteRequest;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Pickup;
use App\Models\RoutePlanner;
use App\Support\ProviderOrganisation;
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

    public const PLAN_STATUS_SCHEDULED = 'scheduled';

    public const PLAN_STATUS_COMPLETED = 'completed';

    /** @return array{pickup_types: list<string>, normal: array<string, mixed>, bulk_waste_request: array<string, mixed>} */
    public function planOptionsForProvider(string $providerSlug): array
    {
        $ownerSlug = ProviderOrganisation::ownerSlug($providerSlug) ?? $providerSlug;

        $groups = Group::query()
            ->forProviderOrganisation($ownerSlug)
            ->where('status', 'active')
            ->with(['clients' => fn ($query) => $query
                ->forProviderOrganisation($ownerSlug)
                ->where('status', 'active')])
            ->orderBy('name')
            ->get();

        $bulkRequests = BulkWasteRequest::query()
            ->with('client')
            ->forProviderOrganisation($ownerSlug)
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
                    'clients_count' => $group->clients->count(),
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
                    'client_slug' => $bulk->client_slug,
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

        if (! $this->authorizePlanResources($data['provider_slug'], $data['driver_slug'], $data['fleet_slug'], $pickupType, $groupSlugs, $bulkCodes, ['approved'])) {
            throw new InvalidArgumentException('Driver, fleet, group, or bulk request is not valid for this provider');
        }

        [$clients, $bulkByClient] = $this->resolvePlanClients(
            $data['provider_slug'],
            $pickupType,
            $groupSlugs,
            $bulkCodes,
            ['approved']
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
                'status' => $data['status'] ?? self::PLAN_STATUS_SCHEDULED,
                'route_meta' => [
                    'pickup_type' => $pickupType,
                    'pickup_date' => $pickupDate->toISOString(),
                    'selected_group_slugs' => $pickupType === self::PICKUP_TYPE_NORMAL ? $groupSlugs->all() : [],
                    'selected_bulk_request_codes' => $pickupType === self::PICKUP_TYPE_BULK ? $bulkCodes->all() : [],
                ],
            ]);

            $this->createPickupsForPlan($routePlanner, $clients, $bulkByClient, $pickupType, $pickupDate);

            return $routePlanner->load([
                'driver',
                'fleet',
                'pickups.client.group',
            ]);
        });
    }

    /**
     * @param  array{
     *     driver_slug?: string,
     *     fleet_slug?: string,
     *     pickup_date?: string|null,
     *     pickup_type?: string,
     *     group_slugs?: list<string>|null,
     *     bulk_request_codes?: list<string>|null,
     *     status?: string|null
     * }  $data
     */
    public function updatePlan(RoutePlanner $plan, array $data): RoutePlanner
    {
        return DB::transaction(function () use ($plan, $data) {
            $pickupType = (string) ($data['pickup_type'] ?? $plan->pickup_type ?? self::PICKUP_TYPE_NORMAL);

            $groupSlugs = array_key_exists('group_slugs', $data)
                ? collect($data['group_slugs'] ?? [])->filter()->unique()->values()
                : collect($plan->selectedGroupSlugs());

            $bulkCodes = array_key_exists('bulk_request_codes', $data)
                ? collect($data['bulk_request_codes'] ?? [])->filter()->unique()->values()
                : collect($plan->selectedBulkRequestCodes());

            if (array_key_exists('pickup_type', $data)) {
                if ($pickupType === self::PICKUP_TYPE_NORMAL) {
                    $bulkCodes = collect();
                } else {
                    $groupSlugs = collect();
                }
            }

            $selectionTouched = array_key_exists('pickup_type', $data)
                || array_key_exists('group_slugs', $data)
                || array_key_exists('bulk_request_codes', $data);

            if ($selectionTouched) {
                $this->assertValidPickupTypeSelection($pickupType, $groupSlugs, $bulkCodes);
            }

            $driverSlug = $data['driver_slug'] ?? $plan->driver_slug;
            $fleetSlug = $data['fleet_slug'] ?? $plan->fleet_slug;

            if ($selectionTouched || isset($data['driver_slug']) || isset($data['fleet_slug'])) {
                if (! $this->authorizePlanResources(
                    $plan->provider_slug,
                    $driverSlug,
                    $fleetSlug,
                    $pickupType,
                    $groupSlugs,
                    $bulkCodes,
                    ['approved', 'scheduled']
                )) {
                    throw new InvalidArgumentException('Driver, fleet, group, or bulk request is not valid for this provider');
                }
            }

            $pickupDate = isset($data['pickup_date'])
                ? Carbon::parse($data['pickup_date'])
                : ($plan->pickup_date ?? Carbon::now());

            $routeMeta = is_array($plan->route_meta) ? $plan->route_meta : [];

            if ($selectionTouched || isset($data['pickup_type']) || isset($data['pickup_date'])) {
                $routeMeta = [
                    'pickup_type' => $pickupType,
                    'pickup_date' => $pickupDate->toISOString(),
                    'selected_group_slugs' => $pickupType === self::PICKUP_TYPE_NORMAL ? $groupSlugs->all() : [],
                    'selected_bulk_request_codes' => $pickupType === self::PICKUP_TYPE_BULK ? $bulkCodes->all() : [],
                ];
            }

            $plan->fill([
                'driver_slug' => $driverSlug,
                'fleet_slug' => $fleetSlug,
                'pickup_date' => $pickupDate,
                'pickup_type' => $pickupType,
                'group_slug' => $pickupType === self::PICKUP_TYPE_NORMAL ? $groupSlugs->first() : null,
                'route_meta' => $routeMeta,
            ]);

            if (array_key_exists('status', $data) && $data['status'] !== null) {
                $plan->status = $data['status'];
            }

            $plan->save();

            if ($selectionTouched) {
                $this->syncPickupsForPlan($plan, $pickupType, $groupSlugs, $bulkCodes, $pickupDate);
            } elseif (isset($data['pickup_date'])) {
                Pickup::query()
                    ->where('route_planner_id', $plan->id)
                    ->where(fn ($query) => $query->whereNull('scan_status')->orWhere('scan_status', '!=', 'scanned'))
                    ->update(['pickup_date' => $pickupDate]);
            }

            return $plan->fresh()->load([
                'driver',
                'fleet',
                'pickups.client.group',
            ]);
        });
    }

    public function deletePlan(RoutePlanner $plan): void
    {
        DB::transaction(function () use ($plan) {
            $pickups = Pickup::query()
                ->where('route_planner_id', $plan->id)
                ->get();

            $pickupIds = $pickups->pluck('id')->map(fn ($id) => (string) $id)->all();
            $bulkCodes = $pickups->pluck('bulk_waste_request_code')->filter()->unique()->values()->all();

            if ($pickupIds !== []) {
                Payment::query()
                    ->where('payment_type', Payment::PAYMENT_TYPE_PICKUP)
                    ->whereIn('pickup_id', $pickupIds)
                    ->where('status', Payment::STATUS_PENDING)
                    ->delete();
            }

            Pickup::query()
                ->where('route_planner_id', $plan->id)
                ->delete();

            foreach ($bulkCodes as $bulkCode) {
                $stillReferenced = Pickup::query()
                    ->where('bulk_waste_request_code', $bulkCode)
                    ->exists();

                if ($stillReferenced) {
                    continue;
                }

                BulkWasteRequest::query()
                    ->where('request_code', $bulkCode)
                    ->where('status', 'scheduled')
                    ->update([
                        'status' => 'approved',
                        'pickup_date' => null,
                    ]);
            }

            $plan->delete();
        });
    }

    public function updatePickupScanStatus(Pickup $pickup, string $status, ?string $comment = null): Pickup
    {
        $canonicalStatus = match ($status) {
            'scanned' => 'scanned',
            'not_scanned', 'unscanned' => 'unscanned',
            default => $status,
        };

        $pickup->scan_status = $canonicalStatus;
        $pickup->scanned_at = $canonicalStatus === 'scanned' ? now() : null;
        $pickup->unscanned_at = $canonicalStatus === 'unscanned' ? now() : null;

        if ($comment !== null && $comment !== '') {
            $pickup->description = $comment;
        }

        if ($canonicalStatus === 'scanned') {
            $pickup->status = 'completed';
        } elseif ($canonicalStatus === 'unscanned') {
            $pickup->status = 'scheduled';
        }

        $pickup->save();

        if ($canonicalStatus === 'scanned') {
            $this->afterPickupScanned($pickup);
        }

        $pickup->refresh();
        $pickup->unsetRelation('routePlanner');
        $pickup->load('routePlanner');

        return $pickup;
    }

    public function afterPickupScanned(Pickup $pickup): void
    {
        if ($pickup->scan_status !== 'scanned' || ! $pickup->route_planner_id) {
            return;
        }

        DB::transaction(function () use ($pickup) {
            $this->maybeCreatePickupPayment($pickup);
            $this->syncRoutePlannerCompletion((int) $pickup->route_planner_id);
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

            return;
        }

        if ($pickupType === self::PICKUP_TYPE_BULK) {
            if ($bulkCodes->isEmpty()) {
                throw new InvalidArgumentException('Select at least one bulk waste request code for a bulk pickup plan');
            }

            return;
        }

        throw new InvalidArgumentException('pickup_type must be normal or bulk_waste_request');
    }

    /**
     * @param  list<string>  $allowedBulkStatuses
     * @return array{0: Collection<int, Client>, 1: Collection<string, BulkWasteRequest>}
     */
    private function resolvePlanClients(
        string $providerSlug,
        string $pickupType,
        Collection $groupSlugs,
        Collection $bulkCodes,
        array $allowedBulkStatuses = ['approved']
    ): array {
        $ownerSlug = ProviderOrganisation::ownerSlug($providerSlug) ?? $providerSlug;

        if ($pickupType === self::PICKUP_TYPE_BULK) {
            $bulkByClient = BulkWasteRequest::query()
                ->forProviderOrganisation($ownerSlug)
                ->whereIn('status', $allowedBulkStatuses)
                ->whereIn('request_code', $bulkCodes->all())
                ->get()
                ->keyBy('client_slug');

            $clients = Client::query()
                ->forProviderOrganisation($ownerSlug)
                ->where('status', 'active')
                ->whereIn('client_slug', $bulkByClient->keys()->all())
                ->get();

            return [$clients, $bulkByClient];
        }

        $clients = Client::query()
            ->forProviderOrganisation($ownerSlug)
            ->where('status', 'active')
            ->whereIn('group_slug', $groupSlugs->all())
            ->get();

        return [$clients, collect()];
    }

    private function syncPickupsForPlan(
        RoutePlanner $plan,
        string $pickupType,
        Collection $groupSlugs,
        Collection $bulkCodes,
        Carbon $pickupDate
    ): void {
        [$clients, $bulkByClient] = $this->resolvePlanClients(
            $plan->provider_slug,
            $pickupType,
            $groupSlugs,
            $bulkCodes,
            ['approved', 'scheduled']
        );

        if ($clients->isEmpty()) {
            throw new RuntimeException(
                $pickupType === self::PICKUP_TYPE_BULK
                    ? 'No approved bulk waste requests found for the selected codes'
                    : 'No active clients found in the selected groups'
            );
        }

        $targetClientSlugs = $clients->pluck('client_slug')->all();

        $scannedOutsideSelection = Pickup::query()
            ->where('route_planner_id', $plan->id)
            ->whereNotIn('client_slug', $targetClientSlugs)
            ->where('scan_status', 'scanned')
            ->exists();

        if ($scannedOutsideSelection) {
            throw new InvalidArgumentException('Cannot update plan selection: one or more removed stops are already scanned');
        }

        $scannedClientSlugs = Pickup::query()
            ->where('route_planner_id', $plan->id)
            ->where('scan_status', 'scanned')
            ->whereIn('client_slug', $targetClientSlugs)
            ->pluck('client_slug')
            ->all();

        Pickup::query()
            ->where('route_planner_id', $plan->id)
            ->where(fn ($query) => $query->whereNull('scan_status')->orWhere('scan_status', '!=', 'scanned'))
            ->delete();

        $clientsNeedingPickups = $clients
            ->whereNotIn('client_slug', $scannedClientSlugs)
            ->values();

        $this->createPickupsForPlan($plan, $clientsNeedingPickups, $bulkByClient, $pickupType, $pickupDate);
    }

    private function createPickupsForPlan(
        RoutePlanner $plan,
        Collection $clients,
        Collection $bulkByClient,
        string $pickupType,
        Carbon $pickupDate
    ): void {
        foreach ($clients as $client) {
            $bulkRequest = $bulkByClient->get($client->client_slug);

            $pickup = Pickup::create([
                'code' => $this->generateUniquePickupCode(),
                'route_planner_id' => $plan->id,
                'bulk_waste_request_code' => $bulkRequest?->request_code,
                'client_slug' => $client->client_slug,
                'group_slug' => $pickupType === self::PICKUP_TYPE_NORMAL ? $client->group_slug : null,
                'title' => $bulkRequest?->title ?? 'Scheduled pickup',
                'category' => $pickupType === self::PICKUP_TYPE_BULK ? 'bulk_waste_request' : 'normal_pickup',
                'description' => $bulkRequest?->description,
                'amount' => $bulkRequest?->amount,
                'status' => 'scheduled',
                'scan_status' => 'unscanned',
                'location' => $client->pickup_location ?: ($client->gps_address ?: 'Unknown'),
                'provider_slug' => $plan->provider_slug,
                'images' => $bulkRequest?->images,
                'pickup_date' => $pickupDate,
            ]);

            if ($bulkRequest) {
                $this->scheduleBulkRequest($bulkRequest, $pickupDate);
            }

            unset($pickup);
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

    private function maybeCreatePickupPayment(Pickup $pickup): void
    {
        $pickup->loadMissing('routePlanner');
        $pickupType = $pickup->routePlanner?->pickup_type
            ?? ($pickup->routePlanner?->route_meta['pickup_type'] ?? self::PICKUP_TYPE_NORMAL);

        if ($pickupType !== self::PICKUP_TYPE_BULK) {
            return;
        }

        $amount = round((float) ($pickup->amount ?? 0), 2);
        if ($amount <= 0) {
            return;
        }

        $exists = Payment::query()
            ->where('pickup_id', (string) $pickup->id)
            ->where('payment_type', Payment::PAYMENT_TYPE_PICKUP)
            ->whereIn('status', [
                Payment::STATUS_PENDING,
                Payment::STATUS_PAID,
                Payment::STATUS_SUCCESSFUL,
            ])
            ->exists();

        if ($exists) {
            return;
        }

        $client = $pickup->relationLoaded('client')
            ? $pickup->client
            : Client::query()->where('client_slug', $pickup->client_slug)->first();

        $name = $client
            ? trim(($client->first_name ?? '').' '.($client->last_name ?? ''))
            : 'Client';

        Payment::create([
            'client_slug' => $pickup->client_slug,
            'provider_slug' => $pickup->provider_slug,
            'payment_type' => Payment::PAYMENT_TYPE_PICKUP,
            'payable_reference' => $pickup->code,
            'transaction_id' => 'PUP-'.Str::upper(Str::random(12)),
            'payment_method' => 'pending',
            'network' => 'unknown',
            'name' => $name !== '' ? $name : 'Client',
            'client_email' => $client?->email,
            'amount' => $amount,
            'currency' => 'GHS',
            'status' => Payment::STATUS_PENDING,
            'pickup_id' => (string) $pickup->id,
            'purchase_id' => null,
        ]);
    }

    private function syncRoutePlannerCompletion(int $routePlannerId): void
    {
        $hasUnscanned = Pickup::query()
            ->where('route_planner_id', $routePlannerId)
            ->where(function ($query) {
                $query->whereNull('scan_status')
                    ->orWhere('scan_status', '!=', 'scanned');
            })
            ->exists();

        if (! $hasUnscanned) {
            RoutePlanner::query()
                ->where('id', $routePlannerId)
                ->update(['status' => self::PLAN_STATUS_COMPLETED]);
        }
    }

    /**
     * @param  list<string>  $allowedBulkStatuses
     */
    private function authorizePlanResources(
        string $providerSlug,
        string $driverSlug,
        string $fleetSlug,
        string $pickupType,
        Collection $groupSlugs,
        Collection $bulkCodes,
        array $allowedBulkStatuses = ['approved']
    ): bool {
        $ownerSlug = ProviderOrganisation::ownerSlug($providerSlug) ?? $providerSlug;

        if (! Driver::query()
            ->where('driver_slug', $driverSlug)
            ->forProviderOrganisation($ownerSlug)
            ->exists()) {
            return false;
        }

        if (! Fleet::query()
            ->where('fleet_slug', $fleetSlug)
            ->forProviderOrganisation($ownerSlug)
            ->exists()) {
            return false;
        }

        if ($pickupType === self::PICKUP_TYPE_NORMAL) {
            $count = Group::query()
                ->whereIn('group_slug', $groupSlugs->all())
                ->forProviderOrganisation($ownerSlug)
                ->count();

            return $count === $groupSlugs->count();
        }

        $bulkCount = BulkWasteRequest::query()
            ->forProviderOrganisation($ownerSlug)
            ->whereIn('status', $allowedBulkStatuses)
            ->whereIn('request_code', $bulkCodes->all())
            ->count();

        return $bulkCount === $bulkCodes->count();
    }

    /**
     * Link legacy pickups that were created before route_planner_id was stored on pickups.
     */
    // public function ensurePickupsLinked(RoutePlanner $plan): void
    // {
    //     if (Pickup::query()->where('route_planner_id', $plan->id)->exists()) {
    //         return;
    //     }

    //     if ($plan->created_at === null) {
    //         return;
    //     }

    //     $windowStart = $plan->created_at->copy()->subSeconds(5);
    //     $windowEnd = $plan->created_at->copy()->addSeconds(30);

    //     $query = Pickup::query()
    //         ->where('provider_slug', $plan->provider_slug)
    //         ->whereNull('route_planner_id')
    //         ->whereBetween('created_at', [$windowStart, $windowEnd]);

    //     $pickupType = $plan->pickup_type
    //         ?? ($plan->route_meta['pickup_type'] ?? self::PICKUP_TYPE_NORMAL);

    //     if ($pickupType === self::PICKUP_TYPE_BULK) {
    //         $bulkCodes = $plan->selectedBulkRequestCodes();
    //         if ($bulkCodes === []) {
    //             return;
    //         }

    //         $query->whereIn('bulk_waste_request_code', $bulkCodes);
    //     } else {
    //         $clientSlugs = $this->clientSlugsForPlan($plan);
    //         if ($clientSlugs === []) {
    //             return;
    //         }

    //         $query
    //             ->whereNull('bulk_waste_request_code')
    //             ->whereIn('client_slug', $clientSlugs);
    //     }

    //     $query->update(['route_planner_id' => $plan->id]);
    // }

    // /** @return list<string> */
    // private function clientSlugsForPlan(RoutePlanner $plan): array
    // {
    //     if (($plan->pickup_type ?? null) === self::PICKUP_TYPE_BULK) {
    //         return BulkWasteRequest::query()
    //             ->where('provider_slug', $plan->provider_slug)
    //             ->whereIn('request_code', $plan->selectedBulkRequestCodes())
    //             ->pluck('client_slug')
    //             ->filter()
    //             ->unique()
    //             ->values()
    //             ->all();
    //     }

    //     $groupSlugs = $plan->selectedGroupSlugs();
    //     if ($groupSlugs === []) {
    //         return [];
    //     }

    //     return Client::query()
    //         ->where('provider_slug', $plan->provider_slug)
    //         ->whereIn('group_slug', $groupSlugs)
    //         ->pluck('client_slug')
    //         ->all();
    // }

    private function generateUniquePickupCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Pickup::where('code', $code)->exists());

        return $code;
    }
}
