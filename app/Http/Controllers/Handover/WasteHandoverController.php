<?php

namespace App\Http\Controllers\Handover;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\WasteHandoverRequest;
use App\Services\ClientLocationGeocodingService;
use App\Services\HandoverNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WasteHandoverController extends Controller
{
    /**
     * Team member (or main provider account) submits an aboboya handover request.
     * All other providers in the same zone(s) receive SMS, email, and in-app alerts.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $requesterSlug = self::resolveProviderScopeSlug($user);

        $data = $request->validate([
            'title' => ['required', 'string'],
            'requester_type' => ['nullable', 'string', 'in:aboboya,provider'],
            'requester_name' => ['required', 'string', 'max:255'],
            'requester_phone' => ['required', 'string', 'max:50'],
            'requester_email' => ['nullable', 'email', 'max:255'],
            'waste_types' => ['nullable', 'array'],
            'waste_types.*' => ['string'],
            'description' => ['nullable', 'string'],
            'pickup_location' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'selected_driver_slug' => ['nullable', 'string', 'exists:drivers,driver_slug'],
            'selected_fleet_slug' => ['nullable', 'string', 'exists:fleets,fleet_slug'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable'],
            'fee_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $zoneSlugs = $this->zoneSlugsForProvider($requesterSlug);
        if ($zoneSlugs === []) {
            return self::apiResponse(true, 'Action Failed', 'Your provider has no active zone assignments', self::API_FAIL, []);
        }

        $coords = $this->resolveHandoverCoordinates($data);
        if ($coords === null) {
            return self::apiResponse(
                true,
                'Action Failed',
                'Provide latitude/longitude or a pickup_location that can be geocoded',
                self::API_FAIL,
                []
            );
        }

        $driverSlug = $data['selected_driver_slug'] ?? null;
        $fleetSlug = $data['selected_fleet_slug'] ?? null;

        if ($driverSlug) {
            $driver = Driver::query()->where('driver_slug', $driverSlug)->first();
            if (! $driver) {
                return self::apiResponse(true, 'Action Failed', 'Driver not found', self::API_NOT_FOUND, []);
            }
            if ($fleetSlug) {
                $fleetOk = Fleet::query()
                    ->where('fleet_slug', $fleetSlug)
                    ->where('provider_slug', $driver->provider_slug)
                    ->exists();
                if (! $fleetOk) {
                    return self::apiResponse(true, 'Action Failed', 'Fleet does not belong to the selected driver provider', self::API_FAIL, []);
                }
            }
        }

        $data = static::processImage(['images'], $data);
        $fee = (float) ($data['fee_amount'] ?? 0);

        $handover = WasteHandoverRequest::create([
            'code' => 'HND-'.Str::upper(Str::random(8)),
            'requester_provider_slug' => $requesterSlug,
            'requester_type' => $data['requester_type'] ?? 'aboboya',
            'requester_name' => $data['requester_name'],
            'requester_phone' => $data['requester_phone'],
            'requester_email' => $data['requester_email'] ?? null,
            'submitted_by_slug' => $user->provider_slug ?? null,
            'target_provider_slug' => null,
            'zone_slug' => $zoneSlugs[0],
            'zone_slugs' => $zoneSlugs,
            'title' => $data['title'],
            'waste_types' => $data['waste_types'] ?? [],
            'description' => $data['description'] ?? null,
            'pickup_location' => $data['pickup_location'] ?? null,
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'selected_driver_slug' => $driverSlug,
            'selected_fleet_slug' => $fleetSlug,
            'images' => $data['images'] ?? [],
            'fee_amount' => $fee,
            'payment_status' => $fee > 0 ? 'unpaid' : 'paid',
            'status' => 'pending',
        ]);

        app(HandoverNotificationService::class)->notifyProvidersInZones($handover, $zoneSlugs);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Waste handover request created; providers in your zone have been notified',
            status_code: self::API_CREATED,
            data: $this->transformHandover($handover->fresh())
        );
    }

    /** Pending requests in the caller's zone(s) that another provider may accept. */
    public function availableInZone(Request $request)
    {
        $providerSlug = self::resolveProviderScopeSlug($request->user());
        $zoneSlugs = $this->zoneSlugsForProvider($providerSlug);

        if ($zoneSlugs === []) {
            return $this->paginatedApiResponse(
                WasteHandoverRequest::query()->whereRaw('1 = 0')->paginate($this->perPage($request)),
                'No zone assignments',
                'requests'
            );
        }

        return $this->paginatedApiResponseMapped(
            WasteHandoverRequest::query()
                ->with(['requester', 'driver', 'fleet'])
                ->visibleInProviderZones($zoneSlugs, $providerSlug)
                ->latest()
                ->paginate($this->perPage($request)),
            'Available handover requests in your zone',
            fn ($h) => $this->transformHandover($h),
            'requests'
        );
    }

    public function fleetsForDriver(Request $request, string $driverSlug)
    {
        $driver = Driver::query()->where('driver_slug', $driverSlug)->first();
        if (! $driver) {
            return self::apiResponse(true, 'Action Failed', 'Driver not found', self::API_NOT_FOUND, []);
        }

        $callerSlug = self::resolveProviderScopeSlug($request->user());
        $callerZones = $this->zoneSlugsForProvider($callerSlug);
        $driverZones = $this->zoneSlugsForProvider($driver->provider_slug);

        if (count(array_intersect($callerZones, $driverZones)) === 0) {
            return self::apiResponse(true, 'Action Failed', 'Driver is outside your zone', self::API_FAIL, []);
        }

        return $this->paginatedApiResponse(
            Fleet::query()
                ->where('provider_slug', $driver->provider_slug)
                ->where('status', 'active')
                ->latest()
                ->paginate($this->perPage($request)),
            'Fleets for driver retrieved successfully'
        );
    }

    public function list(Request $request)
    {
        $providerSlug = self::resolveProviderScopeSlug($request->user());
        $zoneSlugs = $this->zoneSlugsForProvider($providerSlug);

        $query = WasteHandoverRequest::query()
            ->with(['requester', 'acceptedProvider', 'driver', 'fleet'])
            ->where(function ($q) use ($providerSlug, $zoneSlugs) {
                $q->where('requester_provider_slug', $providerSlug)
                    ->orWhere('target_provider_slug', $providerSlug);

                if ($zoneSlugs !== []) {
                    $q->orWhere(function ($zoneQ) use ($zoneSlugs) {
                        $zoneQ->whereIn('zone_slug', $zoneSlugs);
                        foreach ($zoneSlugs as $slug) {
                            $zoneQ->orWhereJsonContains('zone_slugs', $slug);
                        }
                    });
                }
            })
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return $this->paginatedApiResponseMapped(
            $query->paginate($this->perPage($request)),
            'Waste handover requests retrieved successfully',
            fn ($h) => $this->transformHandover($h),
            'requests'
        );
    }

    public function show(WasteHandoverRequest $handover, Request $request)
    {
        if (! $this->canAccessHandover($handover, $request->user())) {
            return self::apiResponse(true, 'Action Failed', 'Unauthorized', self::API_FAIL, []);
        }

        $handover->load(['requester', 'acceptedProvider', 'driver', 'fleet']);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Waste handover request retrieved successfully',
            status_code: self::API_SUCCESS,
            data: $this->transformHandover($handover)
        );
    }

    /**
     * First provider in the zone to accept owns the job; requester gets SMS/email with provider & fleet details.
     */
    public function accept(WasteHandoverRequest $handover, Request $request)
    {
        $providerSlug = self::resolveProviderScopeSlug($request->user());

        $payload = $request->validate([
            'driver_slug' => ['nullable', 'string', 'exists:drivers,driver_slug'],
            'fleet_slug' => ['nullable', 'string', 'exists:fleets,fleet_slug'],
        ]);

        if ((string) $handover->requester_provider_slug === (string) $providerSlug) {
            return self::apiResponse(true, 'Action Failed', 'You cannot accept your own handover request', self::API_FAIL, []);
        }

        $zoneSlugs = $this->zoneSlugsForProvider($providerSlug);
        $handoverZones = $handover->zone_slugs ?? [$handover->zone_slug];
        if (count(array_intersect($zoneSlugs, $handoverZones)) === 0) {
            return self::apiResponse(true, 'Action Failed', 'Request is outside your zone', self::API_FAIL, []);
        }

        try {
            $result = DB::transaction(function () use ($handover, $providerSlug, $payload) {
                $locked = WasteHandoverRequest::query()->lockForUpdate()->find($handover->id);

                if (! $locked || $locked->status !== 'pending') {
                    return null;
                }

                $driverSlug = $payload['driver_slug'] ?? $locked->selected_driver_slug;
                $fleetSlug = $payload['fleet_slug'] ?? $locked->selected_fleet_slug;

                if ($driverSlug) {
                    $driver = Driver::query()->where('driver_slug', $driverSlug)->first();
                    if (! $driver || (string) $driver->provider_slug !== (string) $providerSlug) {
                        throw new \RuntimeException('Driver must belong to your provider account');
                    }
                    if ($fleetSlug) {
                        $ok = Fleet::query()
                            ->where('fleet_slug', $fleetSlug)
                            ->where('provider_slug', $providerSlug)
                            ->exists();
                        if (! $ok) {
                            throw new \RuntimeException('Fleet must belong to your provider account');
                        }
                    }
                    $locked->selected_driver_slug = $driverSlug;
                    $locked->selected_fleet_slug = $fleetSlug;
                }

                $locked->target_provider_slug = $providerSlug;
                $locked->status = 'accepted';
                $locked->accepted_at = now();
                $locked->save();

                return $locked->fresh()->load(['requester', 'acceptedProvider', 'driver', 'fleet']);
            });
        } catch (\RuntimeException $e) {
            return self::apiResponse(true, 'Action Failed', $e->getMessage(), self::API_FAIL, []);
        }

        if ($result === null) {
            return self::apiResponse(true, 'Action Failed', 'Request is no longer available', self::API_FAIL, []);
        }

        $acceptingProvider = Provider::query()
            ->where('provider_slug', $providerSlug)
            ->first();

        if ($acceptingProvider) {
            app(HandoverNotificationService::class)->notifyRequesterAccepted($result, $acceptingProvider);
        }

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Waste handover request accepted; requester has been notified',
            status_code: self::API_SUCCESS,
            data: $this->transformHandover($result)
        );
    }

    public function decline(WasteHandoverRequest $handover, Request $request)
    {
        $providerSlug = self::resolveProviderScopeSlug($request->user());

        if ($handover->status !== 'pending') {
            return self::apiResponse(true, 'Action Failed', 'Request is not pending', self::API_FAIL, []);
        }

        if (! in_array($handover->zone_slug, $this->zoneSlugsForProvider($providerSlug), true)
            && count(array_intersect($handover->zone_slugs ?? [], $this->zoneSlugsForProvider($providerSlug))) === 0) {
            return self::apiResponse(true, 'Action Failed', 'Request is outside your zone', self::API_FAIL, []);
        }

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Decline noted (request remains available for other providers)',
            status_code: self::API_SUCCESS,
            data: $this->transformHandover($handover)
        );
    }

    public function confirmPayment(WasteHandoverRequest $handover, Request $request)
    {
        $requesterSlug = self::resolveProviderScopeSlug($request->user());

        if ((string) $handover->requester_provider_slug !== (string) $requesterSlug) {
            return self::apiResponse(true, 'Action Failed', 'Only the submitting provider can confirm payment', self::API_FAIL, []);
        }

        if ($handover->status !== 'accepted') {
            return self::apiResponse(true, 'Action Failed', 'Request must be accepted before payment', self::API_FAIL, []);
        }

        $fee = (float) ($handover->fee_amount ?? 0);
        if ($fee <= 0) {
            $handover->payment_status = 'paid';
            $handover->paid_at = now();
            $handover->save();

            return self::apiResponse(false, 'Action Successful', 'No payment required', self::API_SUCCESS, $this->transformHandover($handover));
        }

        if ($handover->payment_status === 'paid') {
            return self::apiResponse(false, 'Action Successful', 'Already paid', self::API_SUCCESS, $this->transformHandover($handover));
        }

        $paymentData = $request->validate([
            'payment_method' => ['required', 'string', 'in:momo,card,cash'],
            'network' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string'],
            'name' => ['required', 'string'],
        ]);

        DB::beginTransaction();
        try {
            Payment::create([
                'client_slug' => 'handover:'.$handover->code,
                'provider_slug' => $handover->target_provider_slug,
                'payment_type' => 'waste_handover',
                'transaction_id' => 'HND-PAY-'.now()->format('YmdHis'),
                'payment_method' => $paymentData['payment_method'],
                'network' => $paymentData['network'] ?? 'unknown',
                'phone_number' => $paymentData['phone_number'] ?? null,
                'name' => $paymentData['name'],
                'amount' => $fee,
                'currency' => 'GHS',
                'status' => Payment::STATUS_PAID,
                'pickup_id' => null,
                'purchase_id' => null,
            ]);

            $handover->payment_status = 'paid';
            $handover->paid_at = now();
            $handover->save();

            DB::commit();

            return self::apiResponse(
                in_error: false,
                message: 'Action Successful',
                reason: 'Handover payment confirmed',
                status_code: self::API_SUCCESS,
                data: $this->transformHandover($handover->fresh()->load(['acceptedProvider']))
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return self::apiResponse(true, 'Action Failed', 'Payment failed: '.$e->getMessage(), self::API_FAIL, []);
        }
    }

    public function complete(WasteHandoverRequest $handover, Request $request)
    {
        $providerSlug = self::resolveProviderScopeSlug($request->user());

        if ($handover->status !== 'accepted') {
            return self::apiResponse(true, 'Action Failed', 'Request must be accepted before completion', self::API_FAIL, []);
        }

        if ((string) $handover->target_provider_slug !== (string) $providerSlug) {
            return self::apiResponse(true, 'Action Failed', 'Only the accepting provider can complete this request', self::API_FAIL, []);
        }

        if ((float) ($handover->fee_amount ?? 0) > 0 && $handover->payment_status !== 'paid') {
            return self::apiResponse(true, 'Action Failed', 'Payment must be confirmed before completion', self::API_FAIL, []);
        }

        $handover->status = 'completed';
        $handover->completed_at = now();
        $handover->save();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Waste handover request completed successfully',
            status_code: self::API_SUCCESS,
            data: $this->transformHandover($handover->fresh())
        );
    }

    private function transformHandover(WasteHandoverRequest $handover): array
    {
        $payload = $handover->toArray();
        $payload['coordinates'] = [
            'latitude' => $handover->latitude !== null ? (float) $handover->latitude : null,
            'longitude' => $handover->longitude !== null ? (float) $handover->longitude : null,
            'map_ready' => $handover->latitude !== null && $handover->longitude !== null,
        ];
        $payload['requester_contact'] = [
            'name' => $handover->requester_name,
            'phone_number' => $handover->requester_phone,
            'email' => $handover->requester_email,
        ];
        $payload['accepted_provider'] = $handover->acceptedProvider?->only([
            'provider_slug',
            'business_name',
            'first_name',
            'last_name',
            'phone_number',
            'email',
        ]);
        $payload['requester'] = $handover->requester?->only([
            'provider_slug',
            'business_name',
            'first_name',
            'last_name',
        ]);
        $payload['driver'] = $handover->driver?->only([
            'driver_slug',
            'first_name',
            'last_name',
            'phone_number',
            'email',
            'provider_slug',
        ]);
        $payload['fleet'] = $handover->fleet?->only([
            'fleet_slug',
            'license_plate',
            'vehicle_make',
            'model',
        ]);
        $payload['amount_payable'] = (float) ($handover->fee_amount ?? 0);
        $payload['can_pay'] = $handover->status === 'accepted'
            && $payload['amount_payable'] > 0
            && $handover->payment_status !== 'paid';

        return $payload;
    }

    private function resolveHandoverCoordinates(array $data): ?array
    {
        if (! empty($data['latitude']) && ! empty($data['longitude'])) {
            return [
                'latitude' => (float) $data['latitude'],
                'longitude' => (float) $data['longitude'],
            ];
        }

        if (empty($data['pickup_location'])) {
            return null;
        }

        $resolved = app(ClientLocationGeocodingService::class)
            ->resolveCoordinates((string) $data['pickup_location']);

        if ($resolved === null) {
            return null;
        }

        return [
            'latitude' => $resolved['latitude'],
            'longitude' => $resolved['longitude'],
        ];
    }

    private function zoneSlugsForProvider(string $providerSlug): array
    {
        return DB::table('provider_zones')
            ->where('provider_slug', $providerSlug)
            ->where('status', 'active')
            ->pluck('zone_slug')
            ->all();
    }

    private function canAccessHandover(WasteHandoverRequest $handover, object $user): bool
    {
        $slug = self::resolveProviderScopeSlug($user);
        if ($handover->requester_provider_slug === $slug || $handover->target_provider_slug === $slug) {
            return true;
        }

        $zones = $handover->zone_slugs ?? [$handover->zone_slug];

        return count(array_intersect($zones, $this->zoneSlugsForProvider($slug))) > 0;
    }
}
