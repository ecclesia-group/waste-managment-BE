<?php

namespace App\Traits;

use App\Models\Client;
use App\Models\Pickup;

trait HasClientMapPayload
{
    /**
     * @return array{latitude: ?float, longitude: ?float, map_ready: bool}
     */
    protected static function clientCoordinatesForMap(?Client $client): array
    {
        if ($client === null) {
            return ['latitude' => null, 'longitude' => null, 'map_ready' => false];
        }

        $lat = $client->latitude;
        $lng = $client->longitude;

        if ($lat === null || $lat === '' || $lng === null || $lng === '') {
            return ['latitude' => null, 'longitude' => null, 'map_ready' => false];
        }

        return [
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
            'map_ready' => true,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function clientBriefForManualScan(?Client $client): ?array
    {
        if ($client === null) {
            return null;
        }

        $coords = static::clientCoordinatesForMap($client);
        $group = $client->group;

        return [
            'client_slug' => $client->client_slug,
            'name' => trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')),
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'phone_number' => $client->phone_number,
            'email' => $client->email,
            'gps_address' => $client->gps_address,
            'pickup_location' => $client->pickup_location,
            'category' => $client->type,
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'item_code' => $client->item_code,
            'bin_code' => $client->item_code,
            'group_slug' => $client->group_slug,
            'group_name' => $group?->name,
        ];
    }

    /**
     * Manual item scan + change_scan_status: pickup_id, item_code, pickup record, and client.
     */
    protected static function manualScanPickupPayload(Pickup $pickup, ?string $itemCode = null): array
    {
        $pickup->loadMissing(['client.group']);
        $client = $pickup->client;
        $resolvedItemCode = $itemCode ?? $client?->item_code;

        $routePlanner = $pickup->routePlanner;

        return [
            'pickup_id' => $pickup->id,
            'item_code' => $resolvedItemCode,
            'bin_code' => $resolvedItemCode,
            'id' => $pickup->id,
            'code' => $pickup->code,
            'route_planner_id' => $pickup->route_planner_id,
            'route_planner_status' => $routePlanner?->status,
            'pickup_type' => $routePlanner?->pickup_type,
            'scan_status' => $pickup->scan_status ?? 'unscanned',
            'status' => $pickup->status,
            'pickup_date' => $pickup->pickup_date,
            'amount' => $pickup->amount,
            'bulk_waste_request_code' => $pickup->bulk_waste_request_code,
            'requires_payment_before_pickup' => ! empty($pickup->bulk_waste_request_code),
            'scanned_at' => $pickup->scanned_at,
            'unscanned_at' => $pickup->unscanned_at,
            'description' => $pickup->description,
            'client' => static::clientBriefForManualScan($client),
        ];
    }

    /**
     * Client pickup schedules / list UI: flat readable pickup without route_planner nest.
     */
    protected static function enrichPickupForPickupUi(Pickup $pickup): array
    {
        $pickup->loadMissing(['client.group']);
        $client = $pickup->client;
        // $coords = static::clientCoordinatesForMap($client);
        $isBulk = ! empty($pickup->bulk_waste_request_code);
        $pickupType = $isBulk ? 'bulk_waste_request' : 'normal';

        // $payment = \App\Models\Payment::query()
        //     ->where('pickup_id', (string) $pickup->id)
        //     ->where('payment_type', \App\Models\Payment::PAYMENT_TYPE_PICKUP)
        //     ->latest()
        //     ->first();

        // $bulkPaymentStatus = null;
        // if ($isBulk) {
        //     $bulkPaymentStatus = \App\Models\BulkWasteRequest::query()
        //         ->where('request_code', $pickup->bulk_waste_request_code)
        //         ->value('payment_status');
        // }

        // $paymentStatus = $payment?->status ?? $bulkPaymentStatus;
        // $isPaid = $pickup->status === 'paid'
        //     || in_array($payment?->status, [\App\Models\Payment::STATUS_PAID, \App\Models\Payment::STATUS_SUCCESSFUL], true)
        //     || ($isBulk && $bulkPaymentStatus === 'paid');

        return [
            'id' => $pickup->id,
            'code' => $pickup->code,
            'title' => $pickup->title,
            'description' => $pickup->description,
            'category' => $pickup->category,
            'pickup_type' => $pickupType,
            'amount' => $pickup->amount !== null ? (float) $pickup->amount : null,
            'status' => $pickup->status,
            'scan_status' => $pickup->scan_status,
            'location' => $pickup->location,
            'images' => $pickup->images ?? [],
            'pickup_date' => $pickup->pickup_date,
            'bulk_waste_request_code' => $pickup->bulk_waste_request_code,
            // 'group_slug' => $pickup->group_slug ?? $client?->group_slug,
            // 'group_name' => $client?->group?->name,
            // 'requires_payment' => (float) ($pickup->amount ?? 0) > 0 && ! $isPaid,
            // 'requires_payment_before_pickup' => $isBulk && $bulkPaymentStatus !== 'paid',
            // 'payment_status' => $paymentStatus,
            // 'is_paid' => $isPaid,
            // 'scanned_at' => $pickup->scanned_at,
            // 'client' => static::clientBriefForManualScan($client)
            // 'map' => [
            //     'coordinates' => $coords,
            //     'gps_address' => $client?->gps_address,
            //     'pickup_location' => $client?->pickup_location ?? $pickup->location,
            // ],
        ];
    }
}
