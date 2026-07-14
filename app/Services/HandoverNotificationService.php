<?php

namespace App\Services;

use App\Mail\HandoverPaymentReceiptMail;
use App\Mail\HandoverRequesterAcceptedMail;
use App\Mail\HandoverZoneProviderAlertMail;
use App\Models\Notification;
use App\Models\Provider;
use App\Models\WasteHandoverRequest;
use App\Traits\AppNotifications;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SMS + email + in-app alerts for waste handover (aboboya) requests.
 */
class HandoverNotificationService
{
    /** Notify every other active provider assigned to the same zone(s). */
    public function notifyProvidersInZones(WasteHandoverRequest $handover, array $zoneIds): void
    {
        $handover->loadMissing('requester');
        $requesterSlug = (string) $handover->requester_provider_slug;
        $providers = $this->providersInZones($zoneIds, $requesterSlug);
        $handoverService = app(HandoverService::class);

        if ($providers->isEmpty()) {
            return;
        }

        $location = $this->formatLocation($handover);
        $requesterName = $handoverService->providerDisplayName($handover->requester) ?? 'Requester';
        $requesterPhone = (string) ($handover->requester?->phone_number ?? '');
        $title = $handoverService->handoverTitle($handover);
        $fleetTypeLabel = $handover->fleet_type
            ? $handoverService->fleetTypeLabel((string) $handover->fleet_type)
            : null;

        foreach ($providers as $provider) {
            $this->createInAppAlert($provider, $handover, $location, $requesterName);

            $providerName = $this->providerDisplayName($provider);

            if ($provider->email) {
                AppNotifications::sendEmail(
                    $provider->email,
                    [
                        $providerName,
                        $handover->code,
                        'Provider',
                        $requesterName,
                        $requesterPhone,
                        $location,
                        'General waste',
                        $title,
                        $fleetTypeLabel,
                        (float) ($handover->fee_amount ?? 0),
                    ],
                    HandoverZoneProviderAlertMail::class,
                    context: 'handover_zone_alert',
                );
            }

            if ($provider->phone_number) {
                $message = "New provider handover {$handover->code} at {$location}.\n"
                    ."Contact: {$requesterName}".($requesterPhone !== '' ? " ({$requesterPhone})" : '')."\n"
                    .'Open the app to accept.';
                AppNotifications::sendSms(
                    $provider->phone_number,
                    $message,
                    'WMS',
                    'handover_zone_alert',
                );
            }
        }
    }

    /** Tell the aboboya/requester that a provider is on the way. */
    public function notifyRequesterAccepted(WasteHandoverRequest $handover, Provider $acceptingProvider): void
    {
        $handover->loadMissing(['requester', 'fleet', 'driver']);

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

        $requesterName = app(HandoverService::class)->providerDisplayName($handover->requester) ?? 'Customer';
        $email = $handover->requester?->email;
        if ($email) {
            AppNotifications::sendEmail(
                $email,
                [
                    $requesterName,
                    $handover->code,
                    $providerName,
                    $phone,
                    $fleetLabel,
                    $driverName,
                    $this->formatLocation($handover),
                ],
                HandoverRequesterAcceptedMail::class,
                context: 'handover_requester_accepted',
            );
        }

        $smsPhone = $handover->requester?->phone_number;
        if ($smsPhone) {
            AppNotifications::sendSms($smsPhone, $message, 'WMS', 'handover_requester_accepted');
        }
    }

    /** Email receipt to the requester after payment is confirmed. */
    public function notifyPaymentReceipt(WasteHandoverRequest $handover, array $receipt): void
    {
        $handover->loadMissing('requester');
        $email = $handover->requester?->email;
        if (! $email) {
            return;
        }

        AppNotifications::sendEmail(
            $email,
            [
                app(HandoverService::class)->providerDisplayName($handover->requester) ?? 'Customer',
                $receipt,
            ],
            HandoverPaymentReceiptMail::class,
            context: 'handover_payment_receipt',
        );
    }

    /**
     * @return Collection<int, Provider>
     */
    private function providersInZones(array $zoneIds, string $excludeProviderSlug): Collection
    {
        if ($zoneIds === []) {
            return collect();
        }

        $slugs = DB::table('provider_zones')
            ->whereIn('zone_id', $zoneIds)
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
        string $requesterName,
    ): void {
        Notification::create([
            'actor' => 'provider',
            'admin_slug' => auth('admin')->user()->admin_slug ?? null,
            'actor_slug' => $provider->provider_slug,
            'title' => 'New provider handover request',
            'message' => "{$handover->code}: {$requesterName} at {$location}. Tap to view and accept.",
            'type' => 'handover_request',
        ]);
    }

    private function formatLocation(WasteHandoverRequest $handover): string
    {
        if ($handover->pickup_location) {
            return (string) $handover->pickup_location;
        }

        if ($handover->gps_address) {
            return (string) $handover->gps_address;
        }

        if ($handover->latitude !== null && $handover->longitude !== null) {
            return $handover->latitude.','.$handover->longitude;
        }

        return 'Location not specified';
    }

    private function providerDisplayName(Provider $provider): string
    {
        $name = trim(($provider->business_name ?? '')
            ?: trim(($provider->first_name ?? '').' '.($provider->last_name ?? '')));

        return $name !== '' ? $name : (string) $provider->provider_slug;
    }
}
