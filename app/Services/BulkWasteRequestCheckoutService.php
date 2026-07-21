<?php

namespace App\Services;

use App\Models\BulkWasteRequest;
use App\Models\Client;
use App\Models\Payment;
use App\Services\CalPay\CalPayPaymentResolver;
use App\Services\CalPay\CalPayService;
use Illuminate\Support\Facades\DB;

class BulkWasteRequestCheckoutService
{
    public function startCheckout(Client $client, BulkWasteRequest $bulkWasteRequest, array $checkoutData)
    {
        return DB::transaction(function () use ($client, $bulkWasteRequest, $checkoutData) {

            $ctx = app(CalPayPaymentResolver::class)->resolve(
                Payment::PAYMENT_TYPE_BULK_WASTE,
                $bulkWasteRequest->request_code,
                $client
            );

            // Create a new payment
            $payment = Payment::create([
                'client_slug' => $client->client_slug,
                'provider_slug' => $bulkWasteRequest->provider_slug,
                'payment_type' => Payment::PAYMENT_TYPE_BULK_WASTE,
                'payable_reference' => $bulkWasteRequest->request_code,
                'transaction_id' => $ctx->orderCode,
                'calpay_order_code' => $ctx->orderCode,
                'payment_method' => $checkoutData['payment_method'],
                'network' => $checkoutData['network'],
                'phone_number' => $checkoutData['customer_contact'] ?? $client->phone_number,
                'name' => $checkoutData['customer_name'] ?? trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')),
                'client_email' => $checkoutData['customer_email'] ?? $client->email,
                'amount' => $ctx->amount,
                'currency' => config('services.calpay.defaults.currency', 'GHS'),
                'status' => Payment::STATUS_PENDING,
                'purchase_id' => null,
            ]);

            $invoice = app(CalPayService::class)->createInvoice(
                $ctx,
                (string) ($checkoutData['customer_name'] ?? $payment->name),
                (string) ($checkoutData['customer_email'] ?? $payment->client_email),
                (string) ($checkoutData['customer_contact'] ?? $payment->phone_number),
                (string) $checkoutData['datacompleteurl'],
                (string) $checkoutData['datacancelurl'],
                null,
                (string) ($checkoutData['approveurl'] ?? $checkoutData['datacompleteurl']),
            );

            $payment->gateway_payload = [
                'raw' => $invoice['gateway_response'],
                'parsed' => $invoice['gateway_parsed'] ?? [],
                'checkout_url' => $invoice['checkout_url'],
                'request_order_code' => $invoice['order_code'],
                'result_order_code' => $invoice['calpay_order_code'] ?? null,
                'payment_token' => $invoice['payment_token'] ?? null,
                'payment_code' => $invoice['payment_code'] ?? null,
            ];
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
        );

        return [
            'payment' => $paymentData,
            'payment_link' => $checkoutUrl,
            'payment_url' => $checkoutUrl,
            'checkout_url' => $checkoutUrl,
            'order_code' => $orderCode,
            'payment_code' => $paymentCode,
        ];
    }
}
