<?php

namespace App\Http\Controllers\Handover;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\WasteHandoverRequest;
use App\Services\HandoverNotificationService;
use App\Services\HandoverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WasteHandoverController extends Controller
{
    public function __construct(
        private readonly HandoverService $handoverService,
    ) {}

    public function fleetTypes()
    {
        $options = collect($this->handoverService->fleetTypeOptions())
            ->map(fn (array $meta, string $key) => [
                'key' => $key,
                'label' => $meta['label'],
                'fee' => (float) $meta['fee'],
            ])
            ->values()
            ->all();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Handover fleet types retrieved successfully',
            status_code: self::API_SUCCESS,
            data: $options
        );
    }

    /** Provider (main or team member) creates a handover; zone peers are notified. */
    public function create(Request $request)
    {
        $user = $request->user();
        $requesterSlug = self::actorProviderSlug($user);

        if ($requesterSlug === '') {
            return self::apiResponse(true, 'Action Failed', 'Provider context is required', self::API_FAIL, []);
        }

        $allowedFleetTypes = implode(',', $this->handoverService->fleetTypeKeys());

        $data = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'fleet_type' => ['required', 'string', 'in:'.$allowedFleetTypes],
            'pickup_location' => ['nullable', 'string', 'max:500'],
            'gps_address' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $this->hasLocationInput($data)) {
            return self::apiResponse(
                true,
                'Action Failed',
                'Provide latitude and longitude, a Ghana Post GPS address, or a pickup location',
                self::API_FAIL,
                []
            );
        }

        try {
            $fee = $this->handoverService->feeForFleetType($data['fleet_type']);
            $coords = $this->handoverService->resolvePickupCoordinates(
                isset($data['latitude']) ? (float) $data['latitude'] : null,
                isset($data['longitude']) ? (float) $data['longitude'] : null,
                $data['gps_address'] ?? null,
                $data['pickup_location'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return self::apiResponse(true, 'Action Failed', $e->getMessage(), self::API_FAIL, []);
        }

        $zoneSlugs = $this->zoneSlugsForActor($user);
        if ($zoneSlugs === []) {
            return self::apiResponse(true, 'Action Failed', 'Your provider has no active zone assignments', self::API_FAIL, []);
        }

        $requesterProvider = Provider::query()
            ->where('provider_slug', $requesterSlug)
            ->first();

        if (! $requesterProvider?->phone_number) {
            return self::apiResponse(true, 'Action Failed', 'You must have a phone number on your profile', self::API_FAIL, []);
        }

        $handover = WasteHandoverRequest::create([
            'code' => 'HND-'.Str::upper(Str::random(8)),
            'requester_provider_slug' => $requesterSlug,
            'target_provider_slug' => null,
            'pickup_location' => $data['pickup_location'] ?? null,
            'gps_address' => $data['gps_address'] ?? null,
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'fleet_type' => $data['fleet_type'],
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
            data: $this->transformHandover($handover->fresh(), $user)
        );
    }

    /** All handover requests created by the logged-in provider. */
    public function myRequests(Request $request)
    {
        $ownerSlug = (string) self::ownerProviderSlug($request->user());

        $query = WasteHandoverRequest::query()
            ->with(['requester', 'acceptedProvider', 'driver', 'fleet'])
            ->forProviderOrganisation($ownerSlug, 'requester_provider_slug')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return $this->paginatedApiResponseMapped(
            $query->paginate($this->perPage($request)),
            'Your handover requests retrieved successfully',
            fn ($h) => $this->transformHandover($h, $request->user()),
            'requests'
        );
    }

    /** Accepted handovers created by the logged-in provider (awaiting payment). */
    public function myAccepted(Request $request)
    {
        return $this->paginatedApiResponseMapped(
            WasteHandoverRequest::query()
                ->with(['requester', 'acceptedProvider', 'driver', 'fleet'])
                ->forProviderOrganisation((string) self::ownerProviderSlug($request->user()), 'requester_provider_slug')
                ->where('status', 'accepted')
                ->latest()
                ->paginate($this->perPage($request)),
            'Accepted handover requests retrieved successfully',
            fn ($h) => $this->transformHandover($h, $request->user()),
            'requests'
        );
    }

    /** Completed handovers created by the logged-in provider. */
    public function myCompleted(Request $request)
    {
        return $this->paginatedApiResponseMapped(
            WasteHandoverRequest::query()
                ->with(['requester', 'acceptedProvider', 'driver', 'fleet'])
                ->forProviderOrganisation((string) self::ownerProviderSlug($request->user()), 'requester_provider_slug')
                ->where('status', 'completed')
                ->latest()
                ->paginate($this->perPage($request)),
            'Completed handover requests retrieved successfully',
            fn ($h) => $this->transformHandover($h, $request->user()),
            'requests'
        );
    }

    /** Jobs the logged-in provider accepted as the collector. */
    public function acceptedJobs(Request $request)
    {
        $ownerSlug = (string) self::ownerProviderSlug($request->user());

        $query = WasteHandoverRequest::query()
            ->with(['requester', 'acceptedProvider', 'driver', 'fleet'])
            ->forProviderOrganisation($ownerSlug, 'target_provider_slug')
            ->whereIn('status', ['accepted', 'completed'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return $this->paginatedApiResponseMapped(
            $query->paginate($this->perPage($request)),
            'Accepted handover jobs retrieved successfully',
            fn ($h) => $this->transformHandover($h, $request->user()),
            'requests'
        );
    }

    /** Pending requests in the caller's zone(s) from other providers. */
    public function availableInZone(Request $request)
    {
        $providerSlug = self::actorProviderSlug($request->user());
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
                ->visibleInProviderZones($zoneSlugs, $providerSlug, $providerSlug)
                ->latest()
                ->paginate($this->perPage($request)),
            'Available handover requests in your zone',
            fn ($h) => $this->transformHandover($h, $request->user()),
            'requests'
        );
    }

    public function fleetsForDriver(Request $request, string $driverSlug)
    {
        $driver = Driver::query()->where('driver_slug', $driverSlug)->first();
        if (! $driver) {
            return self::apiResponse(true, 'Action Failed', 'Driver not found', self::API_NOT_FOUND, []);
        }

        $callerSlug = self::actorProviderSlug($request->user());
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
            data: $this->transformHandover($handover, $request->user())
        );
    }

    /** Update a pending request (requester only). */
    public function update(WasteHandoverRequest $handover, Request $request)
    {
        if (! self::recordBelongsToProviderOrganisation($handover->requester_provider_slug, $request->user())) {
            return self::apiResponse(true, 'Action Failed', 'Only the requester can update this handover', self::API_FAIL, []);
        }

        if ($handover->status !== 'pending') {
            return self::apiResponse(true, 'Action Failed', 'Only pending requests can be updated', self::API_FAIL, []);
        }

        $allowedFleetTypes = implode(',', $this->handoverService->fleetTypeKeys());

        $data = $request->validate([
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'fleet_type' => ['sometimes', 'string', 'in:'.$allowedFleetTypes],
            'pickup_location' => ['sometimes', 'nullable', 'string', 'max:500'],
            'gps_address' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if ($data === []) {
            return self::apiResponse(true, 'Action Failed', 'No fields to update', self::API_FAIL, []);
        }

        try {
            if (isset($data['fleet_type'])) {
                $handover->fleet_type = $data['fleet_type'];
                $handover->fee_amount = $this->handoverService->feeForFleetType($data['fleet_type']);
                $handover->payment_status = $handover->fee_amount > 0 ? 'unpaid' : 'paid';
            }

            if (array_key_exists('pickup_location', $data)) {
                $handover->pickup_location = $data['pickup_location'];
            }

            if (array_key_exists('gps_address', $data)) {
                $handover->gps_address = $data['gps_address'];
            }

            $locationTouched = array_key_exists('latitude', $data)
                || array_key_exists('longitude', $data)
                || array_key_exists('gps_address', $data)
                || array_key_exists('pickup_location', $data);

            if ($locationTouched) {
                $coords = $this->handoverService->resolvePickupCoordinates(
                    array_key_exists('latitude', $data) ? (float) $data['latitude'] : ($handover->latitude !== null ? (float) $handover->latitude : null),
                    array_key_exists('longitude', $data) ? (float) $data['longitude'] : ($handover->longitude !== null ? (float) $handover->longitude : null),
                    $data['gps_address'] ?? $handover->gps_address,
                    $data['pickup_location'] ?? $handover->pickup_location,
                );
                $handover->latitude = $coords['latitude'];
                $handover->longitude = $coords['longitude'];
            }

            $handover->save();
        } catch (InvalidArgumentException $e) {
            return self::apiResponse(true, 'Action Failed', $e->getMessage(), self::API_FAIL, []);
        }

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Handover request updated successfully',
            status_code: self::API_SUCCESS,
            data: $this->transformHandover($handover->fresh(), $request->user())
        );
    }

    /** Delete a pending request (requester only). */
    public function destroy(WasteHandoverRequest $handover, Request $request)
    {
        if (! self::recordBelongsToProviderOrganisation($handover->requester_provider_slug, $request->user())) {
            return self::apiResponse(true, 'Action Failed', 'Only the requester can delete this handover', self::API_FAIL, []);
        }

        if ($handover->status !== 'pending') {
            return self::apiResponse(true, 'Action Failed', 'Only pending requests can be deleted', self::API_FAIL, []);
        }

        $handover->delete();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Handover request deleted successfully',
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    public function accept(WasteHandoverRequest $handover, Request $request)
    {
        $providerSlug = self::actorProviderSlug($request->user());

        $payload = $request->validate([
            'driver_slug' => ['nullable', 'string', 'exists:drivers,driver_slug'],
            'fleet_slug' => ['nullable', 'string', 'exists:fleets,fleet_slug'],
        ]);

        if ((string) $handover->requester_provider_slug === $providerSlug) {
            return self::apiResponse(true, 'Action Failed', 'You cannot accept your own handover request', self::API_FAIL, []);
        }

        $zoneSlugs = $this->zoneSlugsForProvider($providerSlug);
        if (! $this->handoverService->sharesZoneWithRequester($handover, $zoneSlugs)) {
            return self::apiResponse(true, 'Action Failed', 'Request is outside your zone', self::API_FAIL, []);
        }

        try {
            $result = DB::transaction(function () use ($handover, $providerSlug, $payload, $request) {
                $locked = WasteHandoverRequest::query()->lockForUpdate()->find($handover->id);

                if (! $locked || $locked->status !== 'pending') {
                    return null;
                }

                $driverSlug = $payload['driver_slug'] ?? $locked->selected_driver_slug;
                $fleetSlug = $payload['fleet_slug'] ?? $locked->selected_fleet_slug;

                if ($driverSlug) {
                    $driver = Driver::query()->where('driver_slug', $driverSlug)->first();
                    if (! $driver || ! self::recordBelongsToProviderOrganisation($driver->provider_slug, $request->user())) {
                        throw new \RuntimeException('Driver must belong to your provider account');
                    }
                    if ($fleetSlug) {
                        $ok = Fleet::query()
                            ->where('fleet_slug', $fleetSlug)
                            ->forProviderOrganisation((string) self::ownerProviderSlug($request->user()))
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

                if ((float) ($locked->fee_amount ?? 0) <= 0) {
                    $locked->status = 'completed';
                    $locked->completed_at = now();
                }

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
            data: $this->transformHandover($result, $request->user())
        );
    }

    public function decline(WasteHandoverRequest $handover, Request $request)
    {
        $providerSlug = self::actorProviderSlug($request->user());

        if ($handover->status !== 'pending') {
            return self::apiResponse(true, 'Action Failed', 'Request is not pending', self::API_FAIL, []);
        }

        if ((string) $handover->requester_provider_slug === $providerSlug) {
            return self::apiResponse(true, 'Action Failed', 'You cannot decline your own handover request', self::API_FAIL, []);
        }

        $zones = $this->zoneSlugsForProvider($providerSlug);
        if (! $this->handoverService->sharesZoneWithRequester($handover, $zones)) {
            return self::apiResponse(true, 'Action Failed', 'Request is outside your zone', self::API_FAIL, []);
        }

        $this->handoverService->recordDecline($handover, $providerSlug);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Decline recorded; request remains available for other providers in your zone',
            status_code: self::API_SUCCESS,
            data: $this->transformHandover($handover, $request->user())
        );
    }

    /** Requester or accepting provider confirms payment; marks handover completed. */
    public function confirmPayment(WasteHandoverRequest $handover, Request $request)
    {
        $actorSlug = self::actorProviderSlug($request->user());
        $isRequester = (string) $handover->requester_provider_slug === $actorSlug;
        $isCollector = (string) $handover->target_provider_slug === $actorSlug;

        if (! $isRequester && ! $isCollector) {
            return self::apiResponse(true, 'Action Failed', 'Only the requester or accepting provider can confirm payment', self::API_FAIL, []);
        }

        if ($handover->status !== 'accepted') {
            return self::apiResponse(true, 'Action Failed', 'Request must be accepted before payment', self::API_FAIL, []);
        }

        $fee = (float) ($handover->fee_amount ?? 0);
        if ($fee <= 0) {
            $handover->payment_status = 'paid';
            $handover->paid_at = now();
            $handover->status = 'completed';
            $handover->completed_at = now();
            $handover->save();

            return self::apiResponse(false, 'Action Successful', 'No payment required', self::API_SUCCESS, $this->transformHandover($handover, $request->user()));
        }

        if ($handover->payment_status === 'paid') {
            $receipt = $this->handoverService->buildReceipt($handover);

            return self::apiResponse(
                false,
                'Action Successful',
                'Already paid',
                self::API_SUCCESS,
                [
                    'handover' => $this->transformHandover($handover, $request->user()),
                    'receipt' => $receipt,
                ]
            );
        }

        $paymentData = $request->validate([
            'payment_method' => ['required', 'string', 'in:momo,card,cash'],
            'network' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string'],
            'name' => ['required', 'string'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
        ]);

        DB::beginTransaction();
        try {
            $handover->loadMissing('requester');
            $transactionId = $paymentData['transaction_id']
                ?? ('HND-PAY-'.Str::upper(Str::random(10)));

            $payment = Payment::create([
                'client_slug' => 'handover:'.$handover->code,
                'provider_slug' => $handover->target_provider_slug,
                'payment_type' => Payment::PAYMENT_TYPE_HANDOVER,
                'payable_reference' => $handover->code,
                'transaction_id' => $transactionId,
                'payment_method' => $paymentData['payment_method'],
                'network' => $paymentData['network'] ?? 'unknown',
                'phone_number' => $paymentData['phone_number'] ?? null,
                'name' => $paymentData['name'],
                'client_email' => $handover->requester?->email,
                'amount' => $fee,
                'currency' => 'GHS',
                'status' => Payment::STATUS_PAID,
                'pickup_id' => null,
                'purchase_id' => null,
            ]);

            $handover->payment_status = 'paid';
            $handover->paid_at = now();
            $handover->status = 'completed';
            $handover->completed_at = now();
            $handover->save();

            DB::commit();

            $handover->refresh()->load(['acceptedProvider', 'requester']);
            $receipt = $this->handoverService->buildReceipt($handover, $payment);
            app(HandoverNotificationService::class)->notifyPaymentReceipt($handover, $receipt);

            return self::apiResponse(
                in_error: false,
                message: 'Action Successful',
                reason: 'Handover payment confirmed and request completed',
                status_code: self::API_SUCCESS,
                data: [
                    'handover' => $this->transformHandover($handover, $request->user()),
                    'receipt' => $receipt,
                ]
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return self::apiResponse(true, 'Action Failed', 'Payment failed: '.$e->getMessage(), self::API_FAIL, []);
        }
    }

    public function receipt(WasteHandoverRequest $handover, Request $request)
    {
        if (! $this->canAccessHandover($handover, $request->user(), allowPaidOnly: true)) {
            return self::apiResponse(true, 'Action Failed', 'Unauthorized', self::API_FAIL, []);
        }

        if ($handover->payment_status !== 'paid') {
            return self::apiResponse(true, 'Action Failed', 'Payment has not been confirmed for this handover', self::API_FAIL, []);
        }

        $receipt = $this->handoverService->buildReceipt($handover);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Handover payment receipt retrieved successfully',
            status_code: self::API_SUCCESS,
            data: ['receipt' => $receipt]
        );
    }

    private function transformHandover(WasteHandoverRequest $handover, ?object $user = null): array
    {
        $handover->loadMissing(['requester', 'acceptedProvider', 'driver', 'fleet']);

        $feeAmount = (float) ($handover->fee_amount ?? 0);
        $latitude = $handover->latitude !== null ? (float) $handover->latitude : null;
        $longitude = $handover->longitude !== null ? (float) $handover->longitude : null;
        $actorSlug = $user ? self::actorProviderSlug($user) : '';

        return [
            'id' => $handover->id,
            'code' => $handover->code,
            'title' => $this->handoverService->handoverTitle($handover),
            'status' => $handover->status,
            'fleet_type' => $handover->fleet_type,
            'fleet_type_label' => $handover->fleet_type
                ? $this->handoverService->fleetTypeLabel((string) $handover->fleet_type)
                : null,
            'fee_amount' => $feeAmount,
            'payment_status' => $handover->payment_status,
            'paid_at' => $handover->paid_at?->toISOString(),
            'pickup' => [
                'location' => $handover->pickup_location,
                'gps_address' => $handover->gps_address,
                'coordinates' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'map_ready' => $latitude !== null && $longitude !== null,
                ],
            ],
            'requester_provider_slug' => $handover->requester_provider_slug,
            'target_provider_slug' => $handover->target_provider_slug,
            'requester_provider' => $this->handoverService->providerContactBrief($handover->requester),
            'accepted_provider' => $this->handoverService->providerContactBrief($handover->acceptedProvider),
            'driver' => $handover->driver?->only([
                'driver_slug',
                'first_name',
                'last_name',
                'phone_number',
                'email',
                'provider_slug',
            ]),
            'fleet' => $handover->fleet?->only([
                'fleet_slug',
                'license_plate',
                'vehicle_make',
                'model',
            ]),
            'accepted_at' => $handover->accepted_at?->toISOString(),
            'completed_at' => $handover->completed_at?->toISOString(),
            'created_at' => $handover->created_at?->toISOString(),
            'updated_at' => $handover->updated_at?->toISOString(),
            'can_update' => $user
                && self::recordBelongsToProviderOrganisation($handover->requester_provider_slug, $user)
                && $handover->status === 'pending',
            'can_delete' => $user
                && self::recordBelongsToProviderOrganisation($handover->requester_provider_slug, $user)
                && $handover->status === 'pending',
            'can_pay' => $user && $actorSlug !== ''
                && (self::recordBelongsToProviderOrganisation($handover->requester_provider_slug, $user)
                    || self::recordBelongsToProviderOrganisation($handover->target_provider_slug, $user))
                && $handover->status === 'accepted'
                && $feeAmount > 0
                && $handover->payment_status !== 'paid',
            'can_download_receipt' => $handover->payment_status === 'paid',
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function hasLocationInput(array $data): bool
    {
        return isset($data['latitude'], $data['longitude'])
            || trim((string) ($data['gps_address'] ?? '')) !== ''
            || trim((string) ($data['pickup_location'] ?? '')) !== '';
    }

    /** Zones for the actor; team members fall back to parent zones for notifications. */
    private function zoneSlugsForActor(object $user): array
    {
        $zones = $this->zoneSlugsForProvider(self::actorProviderSlug($user));

        if ($zones === [] && ! empty($user->parent_slug)) {
            $zones = $this->zoneSlugsForProvider((string) $user->parent_slug);
        }

        return $zones;
    }

    private function zoneSlugsForProvider(string $providerSlug): array
    {
        return DB::table('provider_zones')
            ->where('provider_slug', $providerSlug)
            ->where('status', 'active')
            ->pluck('zone_slug')
            ->all();
    }

    private function canAccessHandover(
        WasteHandoverRequest $handover,
        object $user,
        bool $allowPaidOnly = false,
    ): bool {
        $actorSlug = self::actorProviderSlug($user);

        if (self::recordBelongsToProviderOrganisation($handover->requester_provider_slug, $user)
            || self::recordBelongsToProviderOrganisation($handover->target_provider_slug, $user)) {
            return true;
        }

        if ($allowPaidOnly) {
            return false;
        }

        if ($handover->status !== 'pending') {
            return false;
        }

        return $this->handoverService->sharesZoneWithRequester(
            $handover,
            $this->zoneSlugsForProvider($actorSlug)
        );
    }
}
