<?php

namespace App\Services\CalPay;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalPayService
{
    /**
     * CreateInvoice — CalBank CalPay API (July 2025 docs).
     */
    public function createInvoice(
        CalPayPaymentContext $ctx,
        string $customerName,
        string $customerEmail,
        string $customerContact,
        string $completeUrl,
        string $cancelUrl,
        ?string $callbackUrl = null,
        ?string $approveUrl = null,
    ): array {
        $this->assertConfigured();

        $amount = round($ctx->amount, 2);
        $callback = rtrim((string) ($callbackUrl ?? config('services.calpay.callback_url')), '/ ');
        $approve = rtrim((string) ($approveUrl ?? $completeUrl), '/ ');

        $payload = [
            'requestType' => 'CreateInvoice',
            'merchant' => $this->merchantBlock(),
            'payment' => [
                'accounttype' => (string) config('services.calpay.payment.account_type', ''),
                'accountnumber' => (string) config('services.calpay.payment.account_number', ''),
                'mode' => (string) config('services.calpay.payment.mode', ''),
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
                // Official docs: transactionCardMode (older samples used a typo)
                'transactionCardMode' => config('services.calpay.defaults.card_mode'),
                'trasactionCardMode' => config('services.calpay.defaults.card_mode'),
                'customerEmail' => $customerEmail,
                'payOption' => config('services.calpay.defaults.pay_option'),
                'approveurl' => $approve,
                'currency' => config('services.calpay.defaults.currency'),
                'orderCode' => $ctx->orderCode,
                'callbackurl' => $callback,
                'fullDiscountAmount' => 0,
            ],
        ];

        Log::info('CalPay CreateInvoice request', [
            'order_code' => $ctx->orderCode,
            'amount' => $amount,
            'callbackurl' => $callback,
            'approveurl' => $approve,
            'datacompleteurl' => $completeUrl,
            'datacancelurl' => $cancelUrl,
        ]);

        $rawBody = $this->post($payload);

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

        Log::info('CalPay CreateInvoice response', [
            'order_code' => $ctx->orderCode,
            'payment_token' => $meta['payment_token'] ?? null,
            'payment_code' => $meta['payment_code'] ?? null,
            'checkout_url' => $checkoutUrl,
        ]);

        return [
            'order_code' => $ctx->orderCode,
            'calpay_order_code' => $meta['order_code'] ?? $ctx->orderCode,
            'checkout_url' => $checkoutUrl,
            'payment_token' => $meta['payment_token'] ?? $meta['pay_token'] ?? null,
            'payment_code' => $meta['payment_code'] ?? null,
            'short_pay_code' => $meta['short_pay_code'] ?? null,
            'qr_code_url' => $meta['qr_code_url'] ?? null,
            'gateway_response' => $rawBody,
            'gateway_parsed' => CalPayResponseParser::unwrap($rawBody),
        ];
    }

    /**
     * GetInvoiceDetails — poll live status by paymentToken (docs §3).
     */
    public function getInvoiceDetails(string $paymentToken): array
    {
        $this->assertConfigured();

        $payload = [
            'requestType' => 'GetInvoiceDetails',
            'paymentToken' => $paymentToken,
            'merchant' => $this->merchantBlock(),
        ];

        Log::info('CalPay GetInvoiceDetails request', ['payment_token' => $paymentToken]);

        $rawBody = $this->post($payload);
        $row = CalPayResponseParser::firstResult($rawBody) ?? [];

        Log::info('CalPay GetInvoiceDetails response', [
            'payment_token' => $paymentToken,
            'message' => CalPayResponseParser::message($rawBody),
            'success' => CalPayResponseParser::isSuccess($rawBody),
            'status' => $row['STATUS'] ?? $row['status'] ?? null,
            'final_status' => $row['FINALSTATUS'] ?? $row['GSTATUS'] ?? $row['TRFINALSTATUS'] ?? null,
            'order_code' => $row['ORDERCODE'] ?? null,
        ]);

        return [
            'raw' => $rawBody,
            'parsed' => CalPayResponseParser::unwrap($rawBody),
            'result' => $row,
            'paid' => CalPayResponseParser::invoiceLooksPaid($rawBody),
            'status' => CalPayResponseParser::invoiceStatus($rawBody),
        ];
    }

    private function merchantBlock(): array
    {
        return [
            'emailOrMobileNumber' => config('services.calpay.merchant.email_or_mobile'),
            'apikey' => config('services.calpay.merchant.api_key'),
            'type' => config('services.calpay.merchant.type'),
            'env' => config('services.calpay.merchant.env'),
            'destinationaccount' => (string) config('services.calpay.merchant.destination_account', ''),
            'sbmerchantid' => (string) config('services.calpay.merchant.sb_merchant_id', ''),
        ];
    }

    private function post(array $payload): array
    {
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

            Log::error('CalPay HTTP error', [
                'status' => $response->status(),
                'body' => $rawBody ?: $plainBody,
                'request_type' => $payload['requestType'] ?? null,
            ]);

            $gatewayMessage = CalPayResponseParser::message($rawBody)
                ?? (strlen($plainBody) > 0 && strlen($plainBody) < 300 ? $plainBody : null)
                ?? 'CalPay payment gateway error (HTTP '.$response->status().')';

            throw new \RuntimeException($gatewayMessage);
        }

        return $rawBody;
    }

    private function assertConfigured(): void
    {
        if (empty(config('services.calpay.x_auth')) || empty(config('services.calpay.merchant.api_key'))) {
            throw new \RuntimeException('CalPay is not configured. Set CALPAY_X_AUTH and CALPAY_MERCHANT_API_KEY in .env');
        }
    }
}
