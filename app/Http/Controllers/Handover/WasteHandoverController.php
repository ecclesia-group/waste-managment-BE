<?php

namespace App\Http\Controllers\Handover;

use App\Http\Controllers\Controller;
use App\Mail\HandoverDriverAlertMail;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\WasteHandoverRequest;
use App\Services\ClientLocationGeocodingService;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class WasteHandoverController extends Controller
{
    public function create(Request $request)
    {
        $requesterSlug = self::resolveProviderScopeSlug($request->user());

        $data = $request->validate([
            'title' => ['required', 'string'],
            'requester_type' => ['nullable', 'string', 'in:aboboya,provider'],
            'waste_types' => ['nullable', 'array'],
            'waste_types.*' => ['string'],
            'description' => ['nullable', 'string'],
            'pickup_location' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'selected_driver_slug' => ['required', 'string', 'exists:drivers,driver_slug'],
            'selected_fleet_slug' => ['nullable', 'string', 'exists:fleets,fleet_slug'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable'],
            'fee_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $driver = Driver::query()->where('driver_slug', $data['selected_driver_slug'])->first();
        if (! $driver) {
            return self::apiResponse(true, 'Action Failed', 'Driver not found', self::API_NOT_FOUND, []);
        }

        if (! empty($data['selected_fleet_slug'])) {
            $fleetOk = Fleet::query()
                ->where('fleet_slug', $data['selected_fleet_slug'])
                ->where('provider_slug', $driver->provider_slug)
                ->exists();
            if (! $fleetOk) {
                return self::apiResponse(true, 'Action Failed', 'Fleet does not belong to the selected driver provider', self::API_FAIL, []);
            }
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

        $zoneSlug = $this->primaryZoneSlugForProvider($requesterSlug);
        if ($zoneSlug === null) {
            return self::apiResponse(true, 'Action Failed', 'Requester has no active zone assigned', self::API_FAIL, []);
        }

        $driverZoneSlugs = $this->zoneSlugsForProvider($driver->provider_slug);
        if (! in_array($zoneSlug, $driverZoneSlugs, true)) {
            return self::apiResponse(
                true,
                'Action Failed',
                'Selected driver is not in your zone',
                self::API_FAIL,
                []
            );
        }

        $data = static::processImage(['images'], $data);
        $fee = (float) ($data['fee_amount'] ?? 0);

        $handover = WasteHandoverRequest::create([
            'code' => 'HND-'.Str::upper(Str::random(8)),
            'requester_provider_slug' => $requesterSlug,
            'requester_type' => $data['requester_type'] ?? 'aboboya',
            'target_provider_slug' => null,
            'zone_slug' => $zoneSlug,
            'title' => $data['title'],
            'waste_types' => $data['waste_types'] ?? [],
            'description' => $data['description'] ?? null,
            'pickup_location' => $data['pickup_location'] ?? null,
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'selected_driver_slug' => $driver->driver_slug,
            'selected_fleet_slug' => $data['selected_fleet_slug'] ?? null,
            'images' => $data['images'] ?? [],
            'fee_amount' => $fee,
            'payment_status' => $fee > 0 ? 'unpaid' : 'paid',
            'status' => 'pending',
        ]);

        $this->notifyDriverOfHandover($handover, $driver);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Waste handover request created successfully',
            status_code: self::API_CREATED,
            data: $this->transformHandover($handover->fresh())
        );
    }

    /**
     * Pending handover requests visible in the caller's zone (not live map — zone filter only).
     */
    public function availableInZone(Request $request)
    {
        $providerSlug = self::resolveProviderScopeSlug($request->user());
        $zoneSlugs = $this->zoneSlugsForProvider($providerSlug);

        if ($zoneSlugs === []) {
            return self::apiResponse(false, 'Action Successful', 'No zone assignments', self::API_SUCCESS, ['requests' => []]);
        }

        $requests = WasteHandoverRequest::query()
            ->with(['requester', 'driver', 'fleet'])
            ->where('status', 'pending')
            ->whereIn('zone_slug', $zoneSlugs)
            ->where(function ($q) use ($providerSlug) {
                $q->whereNull('target_provider_slug')
                    ->orWhere('target_provider_slug', $providerSlug);
            })
            ->latest()
            ->get();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Available handover requests in your zone',
            status_code: self::API_SUCCESS,
            data: [
                'requests' => $requests->map(fn ($h) => $this->transformHandover($h))->values()->all(),
            ]
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

        $fleets = Fleet::query()
            ->where('provider_slug', $driver->provider_slug)
            ->where('status', 'active')
            ->get();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Fleets for driver retrieved successfully',
            status_code: self::API_SUCCESS,
            data: [
                'driver_slug' => $driver->driver_slug,
                'provider_slug' => $driver->provider_slug,
                'fleets' => $fleets->toArray(),
            ]
        );
    }

    public function list(Request $request)
    {
        $providerSlug = self::resolveProviderScopeSlug($request->user());
        $zoneSlugs = $this->zoneSlugsForProvider($providerSlug);

        $query = WasteHandoverRequest::query()
            ->with(['requester', 'acceptedProvider', 'driver', 'fleet'])
            ->where(function ($q) use ($providerSlug) {
                $q->where('requester_provider_slug', $providerSlug)
                    ->orWhere('target_provider_slug', $providerSlug);
            })
            ->when($zoneSlugs !== [], fn ($q) => $q->whereIn('zone_slug', $zoneSlugs))
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Waste handover requests retrieved successfully',
            status_code: self::API_SUCCESS,
            data: [
                'requests' => $query->get()->map(fn ($h) => $this->transformHandover($h))->values()->all(),
            ]
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

    public function accept(WasteHandoverRequest $handover, Request $request)
    {
        $providerSlug = self::resolveProviderScopeSlug($request->user());

        if ($handover->status !== 'pending') {
            return self::apiResponse(true, 'Action Failed', 'Request is not pending', self::API_FAIL, []);
        }

        if (! in_array($handover->zone_slug, $this->zoneSlugsForProvider($providerSlug), true)) {
            return self::apiResponse(true, 'Action Failed', 'Request is outside your zone', self::API_FAIL, []);
        }

        if ($handover->target_provider_slug && $handover->target_provider_slug !== $providerSlug) {
            return self::apiResponse(true, 'Action Failed', 'This request is assigned to another provider', self::API_FAIL, []);
        }

        $driver = $handover->driver;
        if ($driver && (string) $driver->provider_slug !== (string) $providerSlug) {
            return self::apiResponse(true, 'Action Failed', 'Only the driver provider can accept this request', self::API_FAIL, []);
        }

        $handover->target_provider_slug = $providerSlug;
        $handover->status = 'accepted';
        $handover->accepted_at = now();
        $handover->save();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Waste handover request accepted successfully',
            status_code: self::API_SUCCESS,
            data: $this->transformHandover($handover->fresh()->load(['requester', 'acceptedProvider', 'driver', 'fleet']))
        );
    }

    public function decline(WasteHandoverRequest $handover, Request $request)
    {
        $providerSlug = self::resolveProviderScopeSlug($request->user());

        if ($handover->status !== 'pending') {
            return self::apiResponse(true, 'Action Failed', 'Request is not pending', self::API_FAIL, []);
        }

        if ($handover->target_provider_slug && $handover->target_provider_slug !== $providerSlug) {
            return self::apiResponse(true, 'Action Failed', 'This request is assigned to another provider', self::API_FAIL, []);
        }

        $handover->target_provider_slug = $providerSlug;
        $handover->status = 'declined';
        $handover->save();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Waste handover request declined successfully',
            status_code: self::API_SUCCESS,
            data: $this->transformHandover($handover->fresh())
        );
    }

    public function confirmPayment(WasteHandoverRequest $handover, Request $request)
    {
        $requesterSlug = self::resolveProviderScopeSlug($request->user());

        if ((string) $handover->requester_provider_slug !== (string) $requesterSlug) {
            return self::apiResponse(true, 'Action Failed', 'Only the requester can confirm payment', self::API_FAIL, []);
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

    private function primaryZoneSlugForProvider(string $providerSlug): ?string
    {
        return DB::table('provider_zones')
            ->where('provider_slug', $providerSlug)
            ->where('status', 'active')
            ->orderBy('assigned_at')
            ->value('zone_slug');
    }

    private function canAccessHandover(WasteHandoverRequest $handover, object $user): bool
    {
        $slug = self::resolveProviderScopeSlug($user);
        if ($handover->requester_provider_slug === $slug || $handover->target_provider_slug === $slug) {
            return true;
        }

        return in_array($handover->zone_slug, $this->zoneSlugsForProvider($slug), true);
    }

    private function notifyDriverOfHandover(WasteHandoverRequest $handover, Driver $driver): void
    {
        $requester = Provider::query()
            ->where('provider_slug', $handover->requester_provider_slug)
            ->first();

        $requesterName = $requester?->business_name
            ?: trim(($requester?->first_name ?? '').' '.($requester?->last_name ?? ''));

        $location = $handover->pickup_location
            ?: ($handover->latitude.','.$handover->longitude);

        if ($driver->email) {
            try {
                Mail::to($driver->email)->send(new HandoverDriverAlertMail(
                    driverName: trim($driver->first_name.' '.($driver->last_name ?? '')),
                    requestCode: $handover->code,
                    requesterName: $requesterName ?: 'Requester',
                    pickupLocation: $location,
                    feeAmount: (float) $handover->fee_amount,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($driver->phone_number) {
            app(SMSService::class)->queue(
                $driver->phone_number,
                'New handover '.$handover->code.' from '.$requesterName.'. Check the app to accept.',
                'handover_driver_alert'
            );
        }
    }
}
