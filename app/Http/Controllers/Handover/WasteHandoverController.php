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

    /** Fleet types and fees for the create form. */
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

    /**
     * Provider team member (or main account) submits a handover at their current location.
     * Zone peers are notified by SMS, email, and in-app alert.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $submittedBySlug = (string) $user->provider_slug;
        $ownerSlug = (string) ($user->parent_slug ?: $user->provider_slug);

        if ($submittedBySlug === '' || $ownerSlug === '') {
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

        $hasCoordinates = isset($data['latitude'], $data['longitude']);
        $hasGpsAddress = trim((string) ($data['gps_address'] ?? '')) !== '';
        $hasPickupLocation = trim((string) ($data['pickup_location'] ?? '')) !== '';

        if (! $hasCoordinates && ! $hasGpsAddress && ! $hasPickupLocation) {
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

        $zoneSlugs = $this->zoneSlugsForProvider($ownerSlug);
        if ($zoneSlugs === []) {
            return self::apiResponse(true, 'Action Failed', 'Your provider has no active zone assignments', self::API_FAIL, []);
        }

        $ownerProvider = Provider::query()
            ->where('provider_slug', $ownerSlug)
            ->first();

        if (! $ownerProvider?->phone_number) {
            return self::apiResponse(true, 'Action Failed', 'Requester provider must have a phone number on their profile', self::API_FAIL, []);
        }

        $handover = WasteHandoverRequest::create([
            'code' => 'HND-'.Str::upper(Str::random(8)),
            'submitted_by_slug' => $submittedBySlug,
            'requester_provider_slug' => $ownerSlug,
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
                ->with(['requester', 'submittedBy', 'driver', 'fleet'])
                ->visibleInProviderZones($zoneSlugs, $providerSlug, $providerSlug)
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
            ->with(['requester', 'submittedBy', 'acceptedProvider', 'driver', 'fleet'])
            ->where(function ($q) use ($providerSlug, $zoneSlugs) {
                $q->where('requester_provider_slug', $providerSlug)
                    ->orWhere('target_provider_slug', $providerSlug);

                if ($zoneSlugs !== []) {
                    $q->orWhere(function ($zoneQ) use ($zoneSlugs) {
                        $zoneQ->inProviderZones($zoneSlugs);
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

        $handover->load(['requester', 'submittedBy', 'acceptedProvider', 'driver', 'fleet']);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Waste handover request retrieved successfully',
            status_code: self::API_SUCCESS,
            data: $this->transformHandover($handover)
        );
    }

    /**
     * First provider in the zone to accept owns the job; requester gets SMS/email with provider contact.
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
        if (! $this->handoverService->sharesZoneWithRequester($handover, $zoneSlugs)) {
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

        if ((string) $handover->requester_provider_slug === (string) $providerSlug) {
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
            data: $this->transformHandover($handover)
        );
    }

    /** Requester confirms cash/momo payment when the collecting provider arrives. */
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
            $receipt = $this->handoverService->buildReceipt($handover);

            return self::apiResponse(
                false,
                'Action Successful',
                'Already paid',
                self::API_SUCCESS,
                [
                    'handover' => $this->transformHandover($handover),
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
            $handover->save();

            DB::commit();

            $handover->refresh()->load(['acceptedProvider', 'requester']);
            $receipt = $this->handoverService->buildReceipt($handover, $payment);
            app(HandoverNotificationService::class)->notifyPaymentReceipt($handover, $receipt);

            return self::apiResponse(
                in_error: false,
                message: 'Action Successful',
                reason: 'Handover payment confirmed; receipt sent by email when available',
                status_code: self::API_SUCCESS,
                data: [
                    'handover' => $this->transformHandover($handover),
                    'receipt' => $receipt,
                ]
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return self::apiResponse(true, 'Action Failed', 'Payment failed: '.$e->getMessage(), self::API_FAIL, []);
        }
    }

    /** Download / view payment receipt for a paid handover. */
    public function receipt(WasteHandoverRequest $handover, Request $request)
    {
        if (! $this->canAccessHandover($handover, $request->user())) {
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
        $handover->loadMissing(['requester', 'submittedBy', 'acceptedProvider', 'driver', 'fleet']);

        $feeAmount = (float) ($handover->fee_amount ?? 0);
        $latitude = $handover->latitude !== null ? (float) $handover->latitude : null;
        $longitude = $handover->longitude !== null ? (float) $handover->longitude : null;

        $requesterProvider = $this->handoverService->providerContactBrief($handover->requester);
        $submittedBySlug = (string) $handover->submitted_by_slug;
        $ownerSlug = (string) $handover->requester_provider_slug;
        $submittedByTeamMember = $submittedBySlug !== '' && $submittedBySlug !== $ownerSlug;

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
            'submitted_by_slug' => $handover->submitted_by_slug,
            'target_provider_slug' => $handover->target_provider_slug,
            'requester_provider' => $requesterProvider,
            'submitted_by' => $submittedByTeamMember
                ? $this->handoverService->providerContactBrief($handover->submittedBy)
                : null,
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
            'can_pay' => $handover->status === 'accepted'
                && $feeAmount > 0
                && $handover->payment_status !== 'paid',
            'can_download_receipt' => $handover->payment_status === 'paid',
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
        $ownerSlug = self::resolveProviderScopeSlug($user);
        $actorSlug = (string) ($user->provider_slug ?? '');

        if ($handover->requester_provider_slug === $ownerSlug
            || $handover->target_provider_slug === $ownerSlug
            || ($actorSlug !== '' && $handover->submitted_by_slug === $actorSlug)) {
            return true;
        }

        return $this->handoverService->sharesZoneWithRequester(
            $handover,
            $this->zoneSlugsForProvider($ownerSlug)
        );
    }
}
