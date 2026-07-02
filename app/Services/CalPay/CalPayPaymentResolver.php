<?php

namespace App\Services\CalPay;

use App\Models\BulkWasteRequest;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Pickup;
use App\Models\Purchase;
use App\Models\WasteHandoverRequest;
use App\Models\WeighbridgeRecord;
use Illuminate\Support\Str;

class CalPayPaymentResolver
{
    public function resolve(string $paymentType, string $reference, ?object $user): CalPayPaymentContext
    {
        return match ($paymentType) {
            Payment::PAYMENT_TYPE_REGISTRATION_FEE => $this->registration($reference),
            Payment::PAYMENT_TYPE_BULK_WASTE => $this->bulkWaste($reference, $user),
            Payment::PAYMENT_TYPE_PICKUP => $this->pickup($reference, $user),
            Payment::PAYMENT_TYPE_PURCHASE => $this->purchase($reference, $user),
            Payment::PAYMENT_TYPE_HANDOVER => $this->handover($reference, $user),
            Payment::PAYMENT_TYPE_WEIGHBRIDGE => $this->weighbridge($reference, $user),
            default => throw new \InvalidArgumentException('Unsupported payment type'),
        };
    }

    private function registration(string $clientSlug): CalPayPaymentContext
    {
        $client = Client::query()->where('client_slug', $clientSlug)->firstOrFail();
        $amount = round((float) ($client->registration_fee ?? 0), 2);

        if ($amount <= 0) {
            throw new \RuntimeException('Registration fee is not configured for this client');
        }

        $orderCode = $this->orderCode('REG');

        return new CalPayPaymentContext(
            amount: $amount,
            orderCode: $orderCode,
            description: 'Client registration fee',
            itemName: 'Registration fee',
            itemCode: 'REG-'.$client->client_slug,
            clientSlug: $client->client_slug,
            providerSlug: (string) $client->provider_slug,
            paymentType: Payment::PAYMENT_TYPE_REGISTRATION_FEE,
            payableReference: $client->client_slug,
            otherData: 'WMS registration payment',
        );
    }

    private function bulkWaste(string $requestCode, ?object $user): CalPayPaymentContext
    {
        $query = BulkWasteRequest::query()->where('request_code', $requestCode);
        if ($user && isset($user->client_slug)) {
            $query->where('client_slug', $user->client_slug);
        }
        $bulk = $query->firstOrFail();
        $amount = round((float) ($bulk->amount ?? 0), 2);

        if ($amount <= 0) {
            throw new \RuntimeException('Bulk waste request has no price set');
        }

        if (($bulk->payment_status ?? 'unpaid') === 'paid') {
            throw new \RuntimeException('Bulk waste request is already paid');
        }

        return new CalPayPaymentContext(
            amount: $amount,
            orderCode: $this->orderCode('BLK'),
            description: 'Bulk waste pickup — '.$bulk->title,
            itemName: $bulk->title ?? 'Bulk waste',
            itemCode: $bulk->request_code,
            clientSlug: $bulk->client_slug,
            providerSlug: $bulk->provider_slug,
            paymentType: Payment::PAYMENT_TYPE_BULK_WASTE,
            payableReference: $bulk->request_code,
            otherData: 'Bulk waste request '.$bulk->request_code,
        );
    }

    private function pickup(string $pickupCode, ?object $user): CalPayPaymentContext
    {
        $query = Pickup::query()->where('code', $pickupCode);
        if ($user && isset($user->client_slug)) {
            $query->where('client_slug', $user->client_slug);
        }
        $pickup = $query->firstOrFail();
        $amount = round((float) ($pickup->amount ?? 0), 2);

        if ($amount <= 0) {
            throw new \RuntimeException('Pickup has no payable amount');
        }

        return new CalPayPaymentContext(
            amount: $amount,
            orderCode: $this->orderCode('PUP'),
            description: 'Pickup payment — '.($pickup->title ?? $pickup->code),
            itemName: $pickup->title ?? 'Pickup',
            itemCode: $pickup->code,
            clientSlug: $pickup->client_slug,
            providerSlug: $pickup->provider_slug,
            paymentType: Payment::PAYMENT_TYPE_PICKUP,
            payableReference: $pickup->code,
            pickupId: $pickup->id,
            otherData: 'Pickup '.$pickup->code,
        );
    }

    private function purchase(string $purchaseId, ?object $user): CalPayPaymentContext
    {
        $purchase = Purchase::query()->findOrFail((int) $purchaseId);

        if ($user && isset($user->client_slug) && $purchase->client_slug !== $user->client_slug) {
            throw new \RuntimeException('Unauthorized purchase');
        }

        $amount = round((float) ($purchase->total_price ?? 0), 2);

        if ($amount <= 0) {
            throw new \RuntimeException('Purchase has no payable amount');
        }

        $client = Client::query()->where('client_slug', $purchase->client_slug)->first();

        return new CalPayPaymentContext(
            amount: $amount,
            orderCode: $this->orderCode('ORD'),
            description: 'Store purchase #'.$purchase->id,
            itemName: 'Order #'.$purchase->id,
            itemCode: 'PUR-'.$purchase->id,
            clientSlug: $purchase->client_slug,
            providerSlug: (string) ($client?->provider_slug ?? ''),
            paymentType: Payment::PAYMENT_TYPE_PURCHASE,
            payableReference: (string) $purchase->id,
            purchaseId: $purchase->id,
            otherData: 'Purchase '.$purchase->id,
        );
    }

    private function handover(string $handoverCode, ?object $user): CalPayPaymentContext
    {
        $handover = WasteHandoverRequest::query()->where('code', $handoverCode)->firstOrFail();

        if ($handover->status !== 'accepted') {
            throw new \RuntimeException('Handover must be accepted before payment');
        }

        $amount = round((float) ($handover->fee_amount ?? 0), 2);

        if ($amount <= 0) {
            throw new \RuntimeException('Handover has no fee to pay');
        }

        if ($handover->payment_status === 'paid') {
            throw new \RuntimeException('Handover is already paid');
        }

        $title = app(\App\Services\HandoverService::class)->handoverTitle($handover);

        return new CalPayPaymentContext(
            amount: $amount,
            orderCode: $this->orderCode('HND'),
            description: $title,
            itemName: $title,
            itemCode: $handover->code,
            clientSlug: 'handover:'.$handover->code,
            providerSlug: (string) ($handover->target_provider_slug ?? $handover->requester_provider_slug),
            paymentType: Payment::PAYMENT_TYPE_HANDOVER,
            payableReference: $handover->code,
            otherData: 'Handover '.$handover->code,
        );
    }

    private function weighbridge(string $recordCode, ?object $user): CalPayPaymentContext
    {
        $entry = WeighbridgeRecord::query()->where('code', $recordCode)->firstOrFail();

        if ($user && isset($user->provider_slug)) {
            if ((string) $entry->provider_slug !== (string) ($user->provider_slug ?? '')) {
                throw new \RuntimeException('Unauthorized weighbridge record');
            }
        }

        if ($entry->payment_status === 'paid') {
            throw new \RuntimeException('Weighbridge ticket is already paid');
        }

        if ($entry->payment_status === 'credit') {
            throw new \RuntimeException('Weighbridge ticket is on credit; payment not required');
        }

        $amount = round((float) ($entry->amount ?? 0), 2);

        if ($amount <= 0) {
            throw new \RuntimeException('Weighbridge record has no payable amount');
        }

        return new CalPayPaymentContext(
            amount: $amount,
            orderCode: $this->orderCode('WBR'),
            description: 'Weighbridge ticket '.$entry->code,
            itemName: 'Weighbridge '.$entry->code,
            itemCode: $entry->code,
            clientSlug: 'weighbridge:'.$entry->code,
            providerSlug: $entry->provider_slug,
            paymentType: Payment::PAYMENT_TYPE_WEIGHBRIDGE,
            payableReference: $entry->code,
            otherData: 'Weighbridge '.$entry->code,
        );
    }

    private function orderCode(string $prefix): string
    {
        return 'WMS-'.$prefix.'-'.Str::upper(Str::random(10));
    }
}
