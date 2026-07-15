<?php

namespace App\Services\CalPay;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalPayService
{
    public function createInvoice(
        CalPayPaymentContext $ctx,
        string $customerName,
        string $customerEmail,
        string $customerContact,
        string $completeUrl,
        string $cancelUrl,
        ?string $callbackUrl = null,
    ): array {
        $this->assertConfigured();

        $amount = round($ctx->amount, 2);
        $callback = rtrim($callbackUrl ?? config('services.calpay.callback_url'), '/ ');

        $payload = [
            'requestType' => 'CreateInvoice',
            'merchant' => [
                'emailOrMobileNumber' => config('services.calpay.merchant.email_or_mobile'),
                'apikey' => config('services.calpay.merchant.api_key'),
                'type' => config('services.calpay.merchant.type'),
                'env' => config('services.calpay.merchant.env'),
            ],
            'orderItems' => [
                [
                    'unitPrice' => $amount,
                    'itemName' => $ctx->itemName,
                    'quantity' => 1,
                    'itemCode' => $ctx->itemCode,
                    'discountAmount' => 0,
                    'subTotal' => $amount,
                ],
            ],
            'order' => [
                'customerAddressCity' => config('services.calpay.defaults.customer_city'),
                'otherData' => $ctx->otherData ?? $ctx->description,
                'datacompleteurl' => $completeUrl,
                'sendInvoice' => config('services.calpay.defaults.send_invoice'),
                'description' => $ctx->description,
                'tax' => 0,
                'customerName' => $customerName,
                'customerCountry' => config('services.calpay.defaults.customer_country'),
                'datacancelurl' => $cancelUrl,
                'totalAmount' => $amount,
                'shipping' => 0,
                'customerContact' => $customerContact,
                'trasactionCardMode' => config('services.calpay.defaults.card_mode'),
                'customerEmail' => $customerEmail,
                'payOption' => config('services.calpay.defaults.pay_option'),
                'currency' => config('services.calpay.defaults.currency'),
                'orderCode' => $ctx->orderCode,
                'callbackurl' => $callback,
                'fullDiscountAmount' => 0,
            ],
        ];

        $response = Http::timeout(60)
            ->withHeaders([
                'x-auth' => config('services.calpay.x_auth'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post(config('services.calpay.endpoint'), $payload);

        $rawBody = $response->json() ?? [];

        if (! $response->successful()) {
            $plainBody = trim((string) $response->body());

            Log::error('CalPay CreateInvoice HTTP error', [
                'status' => $response->status(),
                'body' => $rawBody ?: $plainBody,
                'order_code' => $ctx->orderCode,
            ]);

            $gatewayMessage = CalPayResponseParser::message($rawBody)
                ?? (strlen($plainBody) > 0 && strlen($plainBody) < 300 ? $plainBody : null)
                ?? 'CalPay payment gateway error (HTTP '.$response->status().')';

            throw new \RuntimeException($gatewayMessage);
        }

        if (! CalPayResponseParser::isSuccess($rawBody)) {
            Log::error('CalPay CreateInvoice rejected', [
                'body' => $rawBody,
                'parsed' => CalPayResponseParser::unwrap($rawBody),
                'order_code' => $ctx->orderCode,
            ]);

            throw new \RuntimeException(
                CalPayResponseParser::message($rawBody) ?? 'CalPay rejected the invoice request'
            );
        }

        $checkoutUrl = CalPayResponseParser::checkoutUrl($rawBody);
        if ($checkoutUrl === null) {
            throw new \RuntimeException('CalPay accepted invoice but no payment URL was returned');
        }

        $meta = CalPayResponseParser::paymentMeta($rawBody);

        return [
            'order_code' => $ctx->orderCode,
            'calpay_order_code' => $meta['order_code'] ?? $ctx->orderCode,
            'checkout_url' => $checkoutUrl,
            'payment_token' => $meta['payment_token'] ?? null,
            'payment_code' => $meta['payment_code'] ?? null,
            'short_pay_code' => $meta['short_pay_code'] ?? null,
            'qr_code_url' => $meta['qr_code_url'] ?? null,
            'gateway_response' => $rawBody,
            'gateway_parsed' => CalPayResponseParser::unwrap($rawBody),
        ];
    }

    private function assertConfigured(): void
    {
        if (empty(config('services.calpay.x_auth')) || empty(config('services.calpay.merchant.api_key'))) {
            throw new \RuntimeException('CalPay is not configured. Set CALPAY_X_AUTH and CALPAY_MERCHANT_API_KEY in .env');
        }
    }
}
