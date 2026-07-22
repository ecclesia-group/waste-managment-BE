<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\CalPay\CalPayPaymentResolver;
use App\Services\CalPay\CalPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CalPayPaymentController extends Controller
{
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
            'reference' => ['required', 'string', 'max:128'],
            // required if payment_method is card
            'customer_name' => ['required_if:payment_method,card', 'string', 'max:255'],
            'customer_email' => ['required_if:payment_method,card', 'email', 'max:255'],
            'customer_contact' => ['required_if:payment_method,momo', 'string', 'max:50'],
            'datacompleteurl' => ['nullable', 'url', 'max:500'],
            'datacancelurl' => ['nullable', 'url', 'max:500'],
            'approveurl' => ['nullable', 'url', 'max:500'],
            'network' => ['required_if:payment_method,momo', 'string', 'max:50'],
            'payment_method' => ['required', 'string', 'max:50', Rule::in(['momo', 'card'])],
        ]);

        if (! $request->user()) {
            return self::apiResponse(true, 'Action Failed', 'Unauthorized', self::API_FAIL, []);
        }

        // CalPay browser returns hit BACKEND first (like friend's redirect_url → then app).
        $backendBase = rtrim((string) (
            config('custom.urls.backend_url')
            ?: config('app.url')
        ), '/');

        $callbackUrl = (string) config('services.calpay.callback_url');
        if (
            $callbackUrl !== ''
            && (str_contains($backendBase, 'localhost') || str_contains($backendBase, '127.0.0.1'))
        ) {
            $backendBase = rtrim((string) preg_replace('#/api/payment_callback/?$#i', '', $callbackUrl), '/');
        }

        $completeUrl = $data['datacompleteurl'] ?? ($backendBase . '/payment/success');
        $cancelUrl = $data['datacancelurl'] ?? ($backendBase . '/payment/cancelled');
        // Docs require approveurl — URL after successful payment (same as complete by default).
        $approveUrl = $data['approveurl'] ?? $completeUrl;

        Log::info('CalPay payment redirect URLs', [
            'backend_base' => $backendBase,
            'client_base' => config('custom.urls.client_url'),
            'datacompleteurl' => $completeUrl,
            'datacancelurl' => $cancelUrl,
            'approveurl' => $approveUrl,
            'callback_url' => $callbackUrl,
        ]);

        $checkoutData = [
            'payment_method' => $data['payment_method'],
            'network' => $data['network'] ?? ($data['payment_method'] === 'card' ? 'card' : null),
            'customer_name' => $data['customer_name'] ?? trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')),
            'customer_email' => $data['customer_email'] ?? $request->user()->email,
            'customer_contact' => $data['customer_contact'] ?? $request->user()->phone_number,
            'datacompleteurl' => $completeUrl,
            'datacancelurl' => $cancelUrl,
            'approveurl' => $approveUrl,
        ];

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
            $existing->update([
                'status' => Payment::STATUS_CANCELLED,
            ]);
        }

        try {
            $result = DB::transaction(function () use ($ctx, $checkoutData) {
                $payment = Payment::create([
                    'client_slug' => $ctx->clientSlug,
                    'provider_slug' => $ctx->providerSlug ?: 'platform',
                    'payment_type' => $ctx->paymentType,
                    'payable_reference' => $ctx->payableReference,
                    'transaction_id' => $ctx->orderCode,
                    'calpay_order_code' => $ctx->orderCode,
                    'payment_method' => $checkoutData['payment_method'] ?? 'calpay',
                    'network' => $checkoutData['network'] ?? 'calpay',
                    'phone_number' => $checkoutData['customer_contact'],
                    'name' => $checkoutData['customer_name'],
                    'client_email' => $checkoutData['customer_email'],
                    'amount' => $ctx->amount,
                    'currency' => config('services.calpay.defaults.currency', 'GHS'),
                    'status' => Payment::STATUS_PENDING,
                    'purchase_id' => $ctx->purchaseId ? (string) $ctx->purchaseId : null,
                    'pickup_id' => $ctx->pickupId ? (string) $ctx->pickupId : null,
                ]);

                $invoice = app(CalPayService::class)->createInvoice(
                    $ctx,
                    $checkoutData['customer_name'],
                    $checkoutData['customer_email'],
                    $checkoutData['customer_contact'],
                    $checkoutData['datacompleteurl'],
                    $checkoutData['datacancelurl'],
                    null,
                    $checkoutData['datacompleteurl'],
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
