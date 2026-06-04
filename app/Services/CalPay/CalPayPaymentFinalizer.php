<?php

namespace App\Services\CalPay;

use App\Models\BulkWasteRequest;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Pickup;
use App\Models\Purchase;
use App\Models\WasteHandoverRequest;
use App\Models\WeighbridgeRecord;
use Illuminate\Support\Facades\DB;

class CalPayPaymentFinalizer
{
    public function apply(Payment $payment, string $normalizedStatus): void
    {
        DB::transaction(function () use ($payment, $normalizedStatus) {
            $payment->status = $normalizedStatus;
            $payment->save();

            if (! $this->isPaid($normalizedStatus)) {
                return;
            }

            match ($payment->payment_type) {
                Payment::PAYMENT_TYPE_REGISTRATION_FEE => $this->finalizeRegistration($payment),
                Payment::PAYMENT_TYPE_BULK_WASTE => $this->finalizeBulkWaste($payment),
                Payment::PAYMENT_TYPE_PICKUP => $this->finalizePickup($payment),
                Payment::PAYMENT_TYPE_PURCHASE => $this->finalizePurchase($payment),
                Payment::PAYMENT_TYPE_HANDOVER => $this->finalizeHandover($payment),
                Payment::PAYMENT_TYPE_WEIGHBRIDGE => $this->finalizeWeighbridge($payment),
                default => null,
            };
        });
    }

    public function normalizeStatus(mixed $raw): string
    {
        $value = strtolower((string) $raw);

        return match (true) {
            in_array($value, ['paid', 'success', 'successful', 'completed', 'complete', 'approved'], true) => Payment::STATUS_PAID,
            in_array($value, ['failed', 'fail', 'declined', 'rejected', 'error'], true) => Payment::STATUS_FAILED,
            in_array($value, ['cancelled', 'canceled', 'cancel'], true) => Payment::STATUS_CANCELLED,
            default => Payment::STATUS_PENDING,
        };
    }

    private function isPaid(string $status): bool
    {
        return in_array($status, [Payment::STATUS_PAID, Payment::STATUS_SUCCESSFUL], true);
    }

    private function finalizeRegistration(Payment $payment): void
    {
        $client = Client::query()
            ->where('client_slug', $payment->payable_reference ?? $payment->client_slug)
            ->first();

        if ($client) {
            $client->registration_status = true;
            $client->save();
        }
    }

    private function finalizeBulkWaste(Payment $payment): void
    {
        BulkWasteRequest::query()
            ->where('request_code', $payment->payable_reference)
            ->update(['payment_status' => 'paid']);
    }

    private function finalizePickup(Payment $payment): void
    {
        if ($payment->pickup_id) {
            Pickup::query()->where('id', $payment->pickup_id)->update(['status' => 'paid']);
        }
    }

    private function finalizePurchase(Payment $payment): void
    {
        if ($payment->purchase_id) {
            Purchase::query()
                ->where('id', $payment->purchase_id)
                ->update(['status' => 'confirmed']);
        }
    }

    private function finalizeHandover(Payment $payment): void
    {
        WasteHandoverRequest::query()
            ->where('code', $payment->payable_reference)
            ->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);
    }

    private function finalizeWeighbridge(Payment $payment): void
    {
        WeighbridgeRecord::query()
            ->where('code', $payment->payable_reference)
            ->update(['payment_status' => 'paid']);
    }
}
