<?php

namespace App\Services;

use App\Mail\HandoverRequesterAcceptedMail;
use App\Mail\HandoverZoneProviderAlertMail;
use App\Models\Notification;
use App\Models\Provider;
use App\Models\WasteHandoverRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * SMS + email + in-app alerts for waste handover (aboboya) requests.
 */
class HandoverNotificationService
{
    public function __construct(
        private readonly SMSService $sms,
    ) {}

    /** Notify every other active provider assigned to the same zone(s). */
    public function notifyProvidersInZones(WasteHandoverRequest $handover, array $zoneSlugs): void
    {
        $requesterSlug = (string) $handover->requester_provider_slug;
        $providers = $this->providersInZones($zoneSlugs, $requesterSlug);

        if ($providers->isEmpty()) {
            return;
        }

        $location = $this->formatLocation($handover);
        $typeLabel = $this->requesterTypeLabel($handover);
        $wasteLabel = $this->wasteTypesLabel($handover);
        $requesterName = (string) ($handover->requester_name ?? 'Requester');
        $requesterPhone = (string) ($handover->requester_phone ?? '');

        foreach ($providers as $provider) {
            $this->createInAppAlert($provider, $handover, $location, $typeLabel, $requesterName);

            $providerName = $this->providerDisplayName($provider);

            if ($provider->email) {
                try {
                    Mail::to($provider->email)->send(new HandoverZoneProviderAlertMail(
                        providerName: $providerName,
                        requestCode: $handover->code,
                        requesterType: $typeLabel,
                        requesterName: $requesterName,
                        requesterPhone: $requesterPhone,
                        pickupLocation: $location,
                        wasteTypes: $wasteLabel,
                        title: (string) $handover->title,
                    ));
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            if ($provider->phone_number) {
                $this->sms->queue(
                    $provider->phone_number,
                    "New {$typeLabel} handover {$handover->code} at {$location}. "
                    ."Contact: {$requesterName}".($requesterPhone !== '' ? " ({$requesterPhone})" : '')
                    .". Open the app to accept.",
                    'handover_zone_alert'
                );
            }
        }
    }

    /** Tell the aboboya/requester that a provider is on the way. */
    public function notifyRequesterAccepted(WasteHandoverRequest $handover, Provider $acceptingProvider): void
    {
        $handover->loadMissing(['fleet', 'driver']);

        $providerName = $this->providerDisplayName($acceptingProvider);
        $phone = (string) ($acceptingProvider->phone_number ?? '');
        $fleet = $handover->fleet;
        $fleetLabel = $fleet
            ? trim(($fleet->license_plate ?? '').' '.($fleet->vehicle_make ?? '').' '.($fleet->model ?? ''))
            : 'To be confirmed';
        $driver = $handover->driver;
        $driverName = $driver
            ? trim(($driver->first_name ?? '').' '.($driver->last_name ?? ''))
            : null;

        $message = "{$providerName} has accepted your handover request {$handover->code} "
            ."and is on the way. Call {$phone} if needed. Fleet: {$fleetLabel}.";

        $email = $handover->requester_email;
        if ($email) {
            try {
                Mail::to($email)->send(new HandoverRequesterAcceptedMail(
                    requesterName: (string) ($handover->requester_name ?? 'Customer'),
                    requestCode: $handover->code,
                    providerName: $providerName,
                    providerPhone: $phone,
                    fleetLabel: $fleetLabel,
                    driverName: $driverName,
                    pickupLocation: $this->formatLocation($handover),
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $smsPhone = $handover->requester_phone;
        if ($smsPhone) {
            $this->sms->queue($smsPhone, $message, 'handover_requester_accepted');
        }
    }

    /**
     * @return Collection<int, Provider>
     */
    private function providersInZones(array $zoneSlugs, string $excludeProviderSlug): Collection
    {
        if ($zoneSlugs === []) {
            return collect();
        }

        $slugs = DB::table('provider_zones')
            ->whereIn('zone_slug', $zoneSlugs)
            ->where('status', 'active')
            ->where('provider_slug', '!=', $excludeProviderSlug)
            ->pluck('provider_slug')
            ->unique()
            ->values()
            ->all();

        if ($slugs === []) {
            return collect();
        }

        return Provider::query()
            ->whereIn('provider_slug', $slugs)
            ->where('status', 'active')
            ->get();
    }

    private function createInAppAlert(
        Provider $provider,
        WasteHandoverRequest $handover,
        string $location,
        string $typeLabel,
        string $requesterName,
    ): void {
        Notification::create([
            'actor' => 'provider',
            'actor_id' => (string) $provider->id,
            'actor_slug' => $provider->provider_slug,
            'title' => "New {$typeLabel} handover request",
            'message' => "{$handover->code}: {$requesterName} at {$location}. Tap to view and accept.",
            'type' => 'handover_request',
        ]);
    }

    private function formatLocation(WasteHandoverRequest $handover): string
    {
        if ($handover->pickup_location) {
            return (string) $handover->pickup_location;
        }

        if ($handover->latitude !== null && $handover->longitude !== null) {
            return $handover->latitude.','.$handover->longitude;
        }

        return 'Location not specified';
    }

    private function requesterTypeLabel(WasteHandoverRequest $handover): string
    {
        return match ($handover->requester_type) {
            'provider' => 'Provider',
            default => 'Aboboya',
        };
    }

    private function wasteTypesLabel(WasteHandoverRequest $handover): string
    {
        $types = $handover->waste_types ?? [];

        return is_array($types) && $types !== []
            ? implode(', ', $types)
            : 'General waste';
    }

    private function providerDisplayName(Provider $provider): string
    {
        $name = trim(($provider->business_name ?? '')
            ?: trim(($provider->first_name ?? '').' '.($provider->last_name ?? '')));

        return $name !== '' ? $name : (string) $provider->provider_slug;
    }
}
