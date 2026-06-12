<?php

namespace App\Services;

use App\Models\HandoverDecline;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\WasteHandoverRequest;
use InvalidArgumentException;

class HandoverService
{
    public function __construct(
        private readonly GoogleMapsGeocodingService $googleMaps,
        private readonly GhanaPostGpsService $ghanaPostGps,
    ) {}

    /**
     * Resolve pickup coordinates from device GPS, Ghana Post GPS code, or text address.
     *
     * @return array{latitude: float, longitude: float, source: string}
     */
    public function resolvePickupCoordinates(
        ?float $latitude,
        ?float $longitude,
        ?string $gpsAddress,
        ?string $pickupLocation,
    ): array {
        if ($latitude !== null && $longitude !== null) {
            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'source' => 'coordinates',
            ];
        }

        $gpsAddress = trim((string) ($gpsAddress ?? ''));
        if ($gpsAddress !== '') {
            $coords = $this->ghanaPostGps->resolveCoordinates($gpsAddress);
            if ($coords !== null) {
                return array_merge($coords, ['source' => 'ghana_post_gps']);
            }
        }

        $pickupLocation = trim((string) ($pickupLocation ?? ''));
        if ($pickupLocation !== '') {
            $coords = $this->googleMaps->resolveCoordinates($pickupLocation);
            if ($coords !== null) {
                return array_merge($coords, ['source' => 'google_maps']);
            }
        }

        if ($gpsAddress !== '' && $pickupLocation === '') {
            throw new InvalidArgumentException('Could not resolve coordinates from the Ghana Post GPS address provided');
        }

        if ($pickupLocation !== '') {
            throw new InvalidArgumentException('Could not resolve coordinates from the pickup location provided');
        }

        throw new InvalidArgumentException(
            'Provide latitude and longitude, a Ghana Post GPS address, or a pickup location'
        );
    }

    /** @return list<string> */
    public function fleetTypeKeys(): array
    {
        return array_keys(config('handover.fleet_types', []));
    }

    /** @return array<string, array{label: string, fee: float}> */
    public function fleetTypeOptions(): array
    {
        return config('handover.fleet_types', []);
    }

    public function feeForFleetType(string $fleetType): float
    {
        $options = $this->fleetTypeOptions();

        if (! isset($options[$fleetType])) {
            throw new InvalidArgumentException('Invalid fleet_type. Allowed: '.implode(', ', array_keys($options)));
        }

        return round((float) $options[$fleetType]['fee'], 2);
    }

    public function fleetTypeLabel(string $fleetType): string
    {
        return (string) ($this->fleetTypeOptions()[$fleetType]['label'] ?? $fleetType);
    }

    public function handoverTitle(WasteHandoverRequest $handover): string
    {
        return $handover->fleet_type
            ? 'Waste handover — '.$this->fleetTypeLabel((string) $handover->fleet_type)
            : 'Waste handover — '.$handover->code;
    }

    /** @return list<string> */
    public function zoneSlugsForOwner(string $ownerProviderSlug): array
    {
        return \Illuminate\Support\Facades\DB::table('provider_zones')
            ->where('provider_slug', $ownerProviderSlug)
            ->where('status', 'active')
            ->pluck('zone_slug')
            ->all();
    }

    public function sharesZoneWithRequester(WasteHandoverRequest $handover, array $viewerZoneSlugs): bool
    {
        if ($viewerZoneSlugs === []) {
            return false;
        }

        $requesterZones = $this->zoneSlugsForOwner((string) $handover->requester_provider_slug);

        return count(array_intersect($requesterZones, $viewerZoneSlugs)) > 0;
    }

    public function assertValidFleetType(string $fleetType): void
    {
        if (! isset($this->fleetTypeOptions()[$fleetType])) {
            throw new InvalidArgumentException('Invalid fleet_type. Allowed: '.implode(', ', $this->fleetTypeKeys()));
        }
    }

    public function providerDisplayName(?Provider $provider): ?string
    {
        if ($provider === null) {
            return null;
        }

        $name = trim((string) ($provider->business_name ?? ''));
        if ($name === '') {
            $name = trim(($provider->first_name ?? '').' '.($provider->last_name ?? ''));
        }

        return $name !== '' ? $name : (string) $provider->provider_slug;
    }

    /** @return array<string, mixed> */
    public function providerContactBrief(?Provider $provider): ?array
    {
        if ($provider === null) {
            return null;
        }

        return [
            'provider_slug' => $provider->provider_slug,
            'business_name' => $provider->business_name,
            'name' => $this->providerDisplayName($provider),
            'first_name' => $provider->first_name,
            'last_name' => $provider->last_name,
            'phone_number' => $provider->phone_number,
            'email' => $provider->email,
            'gps_address' => $provider->gps_address,
        ];
    }

    public function recordDecline(WasteHandoverRequest $handover, string $providerSlug): void
    {
        HandoverDecline::query()->firstOrCreate([
            'waste_handover_request_id' => $handover->id,
            'provider_slug' => $providerSlug,
        ]);
    }

    public function paymentForHandover(WasteHandoverRequest $handover): ?Payment
    {
        return Payment::query()
            ->where('payment_type', Payment::PAYMENT_TYPE_HANDOVER)
            ->where('payable_reference', $handover->code)
            ->latest()
            ->first();
    }

    /** @return array<string, mixed> */
    public function buildReceipt(WasteHandoverRequest $handover, ?Payment $payment = null): array
    {
        $handover->loadMissing(['requester', 'acceptedProvider']);
        $payment ??= $this->paymentForHandover($handover);

        return [
            'receipt_number' => $payment?->transaction_id ?? ('RCPT-'.$handover->code),
            'handover_code' => $handover->code,
            'title' => $this->handoverTitle($handover),
            'fleet_type' => $handover->fleet_type,
            'fleet_type_label' => $handover->fleet_type
                ? $this->fleetTypeLabel((string) $handover->fleet_type)
                : null,
            'amount' => (float) ($payment?->amount ?? $handover->fee_amount ?? 0),
            'currency' => $payment?->currency ?? 'GHS',
            'payment_status' => $handover->payment_status,
            'payment_method' => $payment?->payment_method,
            'paid_at' => $handover->paid_at?->toISOString(),
            'requester' => $this->providerContactBrief($handover->requester),
            'accepted_provider' => $this->providerContactBrief($handover->acceptedProvider),
            'pickup_location' => $handover->pickup_location,
            'gps_address' => $handover->gps_address,
            'coordinates' => [
                'latitude' => $handover->latitude !== null ? (float) $handover->latitude : null,
                'longitude' => $handover->longitude !== null ? (float) $handover->longitude : null,
            ],
        ];
    }
}
