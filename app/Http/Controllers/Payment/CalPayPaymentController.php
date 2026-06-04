<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\CalPay\CalPayPaymentResolver;
use App\Services\CalPay\CalPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CalPayPaymentController extends Controller
{
    /** Public registration checkout (reference = client_slug). */
    public function initiateRegistration(Request $request)
    {
        $request->merge([
            'payment_type' => Payment::PAYMENT_TYPE_REGISTRATION_FEE,
            'reference' => $request->input('reference') ?? $request->input('client_slug'),
        ]);

        return $this->initiate($request);
    }

    /**
     * Start a CalPay invoice. Frontend redirects the user to checkout_url.
     * Callback: POST /api/payment_callback (server-to-server).
     */
    public function initiate(Request $request)
    {
        $data = $request->validate([
            'payment_type' => [
                'required',
                'string',
                Rule::in([
                    Payment::PAYMENT_TYPE_REGISTRATION_FEE,
                    Payment::PAYMENT_TYPE_BULK_WASTE,
                    Payment::PAYMENT_TYPE_PICKUP,
                    Payment::PAYMENT_TYPE_PURCHASE,
                    Payment::PAYMENT_TYPE_HANDOVER,
                    Payment::PAYMENT_TYPE_WEIGHBRIDGE,
                ]),
            ],
            'reference' => [
                'required',
                'string',
                'max:128',
                Rule::when(
                    fn () => $request->input('payment_type') === Payment::PAYMENT_TYPE_REGISTRATION_FEE,
                    ['exists:clients,client_slug']
                ),
            ],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_contact' => ['required', 'string', 'max:50'],
            'datacompleteurl' => ['required', 'url', 'max:500'],
            'datacancelurl' => ['required', 'url', 'max:500'],
        ]);

        if ($data['payment_type'] === Payment::PAYMENT_TYPE_REGISTRATION_FEE && ! $request->user()) {
            // Public registration flow allowed.
        } elseif (! $request->user()) {
            return self::apiResponse(true, 'Action Failed', 'Unauthorized', self::API_FAIL, []);
        }

        try {
            $ctx = app(CalPayPaymentResolver::class)->resolve(
                $data['payment_type'],
                $data['reference'],
                $request->user()
            );
        } catch (\Throwable $e) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: $e->getMessage(),
                status_code: self::API_FAIL,
                data: []
            );
        }

        $existing = Payment::query()
            ->where('payment_type', $data['payment_type'])
            ->where('payable_reference', $ctx->payableReference)
            ->where('status', Payment::STATUS_PENDING)
            ->whereNotNull('calpay_order_code')
            ->latest()
            ->first();

        if ($existing) {
            return self::apiResponse(
                in_error: false,
                message: 'Action Successful',
                reason: 'Pending CalPay payment already exists',
                status_code: self::API_SUCCESS,
                data: [
                    'payment' => $existing->toArray(),
                    'order_code' => data_get($existing->gateway_payload, 'request_order_code') ?? $existing->calpay_order_code,
                    'checkout_url' => data_get($existing->gateway_payload, 'checkout_url'),
                    'payment_code' => data_get($existing->gateway_payload, 'payment_code'),
                    'payment_token' => data_get($existing->gateway_payload, 'payment_token'),
                ]
            );
        }

        try {
            $result = DB::transaction(function () use ($ctx, $data) {
                $payment = Payment::create([
                    'client_slug' => $ctx->clientSlug,
                    'provider_slug' => $ctx->providerSlug ?: 'platform',
                    'payment_type' => $ctx->paymentType,
                    'payable_reference' => $ctx->payableReference,
                    'transaction_id' => $ctx->orderCode,
                    'calpay_order_code' => $ctx->orderCode,
                    'payment_method' => 'calpay',
                    'network' => 'calpay',
                    'phone_number' => $data['customer_contact'],
                    'name' => $data['customer_name'],
                    'client_email' => $data['customer_email'],
                    'amount' => $ctx->amount,
                    'currency' => config('services.calpay.defaults.currency', 'GHS'),
                    'status' => Payment::STATUS_PENDING,
                    'purchase_id' => $ctx->purchaseId ? (string) $ctx->purchaseId : null,
                    'pickup_id' => $ctx->pickupId ? (string) $ctx->pickupId : null,
                ]);

                $invoice = app(CalPayService::class)->createInvoice(
                    $ctx,
                    $data['customer_name'],
                    $data['customer_email'],
                    $data['customer_contact'],
                    $data['datacompleteurl'],
                    $data['datacancelurl'],
                );

                $gatewayPayload = array_merge(
                    ['raw' => $invoice['gateway_response']],
                    ['parsed' => $invoice['gateway_parsed'] ?? []],
                    [
                        'checkout_url' => $invoice['checkout_url'],
                        'request_order_code' => $invoice['order_code'],
                        'result_order_code' => $invoice['calpay_order_code'] ?? null,
                        'payment_token' => $invoice['payment_token'] ?? null,
                        'payment_code' => $invoice['payment_code'] ?? null,
                        'short_pay_code' => $invoice['short_pay_code'] ?? null,
                        'qr_code_url' => $invoice['qr_code_url'] ?? null,
                    ]
                );

                $payment->gateway_payload = $gatewayPayload;
                $payment->save();

                return [
                    'payment' => $payment->fresh(),
                    'order_code' => $invoice['order_code'],
                    'calpay_order_code' => $invoice['calpay_order_code'] ?? $invoice['order_code'],
                    'checkout_url' => $invoice['checkout_url'],
                    'payment_token' => $invoice['payment_token'] ?? null,
                    'payment_code' => $invoice['payment_code'] ?? null,
                    'qr_code_url' => $invoice['qr_code_url'] ?? null,
                ];
            });
        } catch (\Throwable $e) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: $e->getMessage(),
                status_code: self::API_FAIL,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'CalPay invoice created — redirect user to checkout_url',
            status_code: self::API_CREATED,
            data: $result
        );
    }

    public function status(Request $request)
    {
        $data = $request->validate([
            'order_code' => ['required', 'string', 'max:64'],
        ]);

        $payment = Payment::query()
            ->where('calpay_order_code', $data['order_code'])
            ->orWhere('transaction_id', $data['order_code'])
            ->latest()
            ->first();

        if (! $payment) {
            return self::apiResponse(true, 'Action Failed', 'Payment not found', self::API_NOT_FOUND, []);
        }

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Payment status retrieved',
            status_code: self::API_SUCCESS,
            data: ['payment' => $payment->toArray()]
        );
    }
}
