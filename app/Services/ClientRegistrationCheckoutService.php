<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\CalPay\CalPayPaymentResolver;
use App\Services\CalPay\CalPayService;
use Illuminate\Support\Facades\DB;

class ClientRegistrationCheckoutService
{
    public function startCheckout(Client $client, array $checkoutData): array
    {
        if (! $client->requiresRegistrationPayment()) {
            throw new \RuntimeException('Registration payment is not required for this client');
        }

        $existing = Payment::query()
            ->where('client_slug', $client->client_slug)
            ->where('payment_type', Payment::PAYMENT_TYPE_REGISTRATION_FEE)
            ->where('status', Payment::STATUS_PENDING)
            ->whereNotNull('calpay_order_code')
            ->latest()
            ->first();

        if ($existing) {
            $existing->fill([
                'payment_method' => $checkoutData['payment_method'] ?? $existing->payment_method,
                'network' => $checkoutData['network'] ?? $existing->network,
                'phone_number' => $checkoutData['customer_contact'] ?? $existing->phone_number,
                'name' => $checkoutData['customer_name'] ?? $existing->name,
                'client_email' => $checkoutData['customer_email'] ?? $existing->client_email,
            ])->save();

            return $this->formatCheckoutResponse($existing->fresh());
        }

        return DB::transaction(function () use ($client, $checkoutData) {
            $amount = round((float) $client->registration_fee, 2);
            $feeName = $client->fee?->name ?? 'Registration fee';

            $purchase = Purchase::create([
                'client_slug' => $client->client_slug,
                'number_of_items' => 1,
                'total_price' => $amount,
                'status' => 'pending',
            ]);

            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_slug' => 'registration-fee',
                'name' => $feeName,
                'price' => $amount,
                'quantity' => 1,
            ]);

            $ctx = app(CalPayPaymentResolver::class)->resolve(
                Payment::PAYMENT_TYPE_REGISTRATION_FEE,
                $client->client_slug,
                $client
            );

            $payment = Payment::create([
                'client_slug' => $client->client_slug,
                'provider_slug' => (string) $client->provider_slug,
                'payment_type' => Payment::PAYMENT_TYPE_REGISTRATION_FEE,
                'payable_reference' => $client->client_slug,
                'transaction_id' => $ctx->orderCode,
                'calpay_order_code' => $ctx->orderCode,
                'payment_method' => $checkoutData['payment_method'],
                'network' => $checkoutData['network'],
                'phone_number' => $checkoutData['customer_contact'] ?? $client->phone_number,
                'name' => $checkoutData['customer_name'] ?? trim(($client->first_name ?? '').' '.($client->last_name ?? '')),
                'client_email' => $checkoutData['customer_email'] ?? $client->email,
                'amount' => $amount,
                'currency' => config('services.calpay.defaults.currency', 'GHS'),
                'status' => Payment::STATUS_PENDING,
                'purchase_id' => (string) $purchase->id,
            ]);

            $invoice = app(CalPayService::class)->createInvoice(
                $ctx,
                (string) ($checkoutData['customer_name'] ?? $payment->name),
                (string) ($checkoutData['customer_email'] ?? $payment->client_email),
                (string) ($checkoutData['customer_contact'] ?? $payment->phone_number),
                (string) $checkoutData['datacompleteurl'],
                (string) $checkoutData['datacancelurl'],
            );

            $payment->gateway_payload = array_merge(
                ['raw' => $invoice['gateway_response']],
                ['parsed' => $invoice['gateway_parsed'] ?? []],
                [
                    'checkout_url' => $invoice['checkout_url'],
                    'request_order_code' => $invoice['order_code'],
                    'result_order_code' => $invoice['calpay_order_code'] ?? null,
                    'payment_token' => $invoice['payment_token'] ?? null,
                    'payment_code' => $invoice['payment_code'] ?? null,
                ]
            );
            $payment->save();

            return $this->formatCheckoutResponse($payment->fresh());
        });
    }

    private function formatCheckoutResponse(Payment $payment): array
    {
        $checkoutUrl = data_get($payment->gateway_payload, 'checkout_url');
        $orderCode = data_get($payment->gateway_payload, 'request_order_code') ?? $payment->calpay_order_code;
        $paymentCode = data_get($payment->gateway_payload, 'payment_code');

        $payment->unsetRelations();
        $paymentData = $payment->makeHidden([
            'gateway_payload',
            'callback_payload',
        ])->toArray();

        unset(
            $paymentData['gateway_payload'],
            $paymentData['callback_payload'],
            $paymentData['client'],
            $paymentData['purchase'],
            $paymentData['pickup'],
            $paymentData['provider'],
        );

        return [
            'payment' => $paymentData,
            'payment_url' => $checkoutUrl,
            'checkout_url' => $checkoutUrl,
            'order_code' => $orderCode,
            'payment_code' => $paymentCode,
        ];
    }
}
