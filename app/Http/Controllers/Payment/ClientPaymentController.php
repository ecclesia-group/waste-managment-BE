<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Services\CalPay\CalPayPaymentFinalizer;
use App\Services\CalPay\CalPayService;
use App\Services\ClientRegistrationCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientPaymentController extends Controller
{
    /**
     * One-step registration fee payment for the logged-in client.
     * After paying on CalPay, land on backend /payment/success (approveurl),
     * then call GET payments/registration/status (polls GetInvoiceDetails).
     */
    public function createRegistrationPayment(Request $request)
    {
        $data = $request->validate([
            'payment_method' => ['required', 'string', 'in:momo,card'],
            'network' => ['nullable', 'string', 'max:50'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_contact' => ['nullable', 'string', 'max:50'],
            'datacompleteurl' => ['nullable', 'url', 'max:500'],
            'datacancelurl' => ['nullable', 'url', 'max:500'],
            'approveurl' => ['nullable', 'url', 'max:500'],
        ]);

        if ($data['payment_method'] === 'momo' && empty($data['network'])) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'network is required when payment_method is momo',
                status_code: self::API_FAIL,
                data: []
            );
        }

        /** @var Client $client */
        $client = Client::query()
            ->where('client_slug', $request->user()->client_slug)
            ->with([])
            ->firstOrFail();

        if (! $client->requiresRegistrationPayment()) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Registration payment is not required for this account',
                status_code: self::API_FAIL,
                data: [
                    'registration_status' => (bool) $client->registration_status,
                    'requires_registration_payment' => false,
                ]
            );
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

        $completeUrl = $data['datacompleteurl'] ?? ($backendBase.'/payment/success');
        $cancelUrl = $data['datacancelurl'] ?? ($backendBase.'/payment/cancelled');
        // Docs require approveurl — URL after successful payment (same as complete by default).
        $approveUrl = $data['approveurl'] ?? $completeUrl;

        Log::info('Registration payment redirect URLs', [
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
            'customer_email' => $data['customer_email'] ?? $client->email,
            'customer_contact' => $data['customer_contact'] ?? $client->phone_number,
            'datacompleteurl' => $completeUrl,
            'datacancelurl' => $cancelUrl,
            'approveurl' => $approveUrl,
        ];

        try {
            $checkout = app(ClientRegistrationCheckoutService::class)->startCheckout($client, $checkoutData);
        } catch (\Throwable $e) {
            Log::error('Registration payment failed', ['error' => $e->getMessage()]);

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
            reason: 'Payment initiated successfully',
            status_code: self::API_CREATED,
            data: [
                'payment_link' => $checkout['payment_link'] ?? $checkout['payment_url'] ?? null,
                'payment_url' => $checkout['payment_url'] ?? null,
                'order_code' => $checkout['order_code'] ?? null,
                'payment_code' => $checkout['payment_code'] ?? null,
                'payment' => $checkout['payment'] ?? null,
            ]
        );
    }

    public function registrationPaymentStatus(Request $request)
    {
        $client = Client::query()
            ->where('client_slug', $request->user()->client_slug)
            ->first();

        if (! $client) {
            return self::apiResponse(true, 'Action Failed', 'Client not found', self::API_NOT_FOUND, []);
        }

        // Optional: client forwards CalPay return query params.
        $confirm = array_filter([
            'orderCode' => $request->query('ordercode') ?? $request->query('orderCode') ?? $request->input('order_code'),
            'paytoken' => $request->query('paytoken') ?? $request->input('paytoken'),
            'status' => $request->query('status') ?? $request->input('status'),
            'PAYMENTCODE' => $request->query('paymentcode') ?? $request->input('payment_code'),
        ], fn ($v) => $v !== null && $v !== '');

        if ($confirm !== []) {
            Log::info('Registration status confirm from client', [
                'client_slug' => $client->client_slug,
                'confirm' => $confirm,
            ]);
            app(CalPayCallbackController::class)->confirmFromClient($confirm);
        }

        // Official docs §3: poll GetInvoiceDetails when server callback is missing.
        $this->syncPendingRegistrationFromCalPay($client);

        $client->syncRegistrationStatusFromPayments();
        $client->refresh();

        $paidPayment = Payment::query()
            ->where('client_slug', $client->client_slug)
            ->where('payment_type', Payment::PAYMENT_TYPE_REGISTRATION_FEE)
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_SUCCESSFUL])
            ->latest('id')
            ->first();

        $latestPayment = $paidPayment ?? Payment::query()
            ->where('client_slug', $client->client_slug)
            ->where('payment_type', Payment::PAYMENT_TYPE_REGISTRATION_FEE)
            ->latest('id')
            ->first();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Registration payment status retrieved successfully',
            status_code: self::API_SUCCESS,
            data: [
                'client_slug' => $client->client_slug,
                'registration_status' => (bool) $client->registration_status,
                'requires_registration_payment' => $client->requiresRegistrationPayment(),
                'payment_status' => $latestPayment?->status,
                'order_code' => $latestPayment?->calpay_order_code,
            ],
        );
    }

    private function syncPendingRegistrationFromCalPay(Client $client): void
    {
        $pending = Payment::query()
            ->where('client_slug', $client->client_slug)
            ->where('payment_type', Payment::PAYMENT_TYPE_REGISTRATION_FEE)
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_CANCELLED])
            ->whereNotNull('gateway_payload')
            ->latest('id')
            ->limit(5)
            ->get();

        $finalizer = app(CalPayPaymentFinalizer::class);
        $calpay = app(CalPayService::class);

        foreach ($pending as $payment) {
            $token = data_get($payment->gateway_payload, 'payment_token');
            if (! is_string($token) || $token === '') {
                continue;
            }

            try {
                $details = $calpay->getInvoiceDetails($token);
            } catch (\Throwable $e) {
                Log::warning('GetInvoiceDetails failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            $payment->callback_payload = array_merge(
                is_array($payment->callback_payload) ? $payment->callback_payload : [],
                [
                    'source' => 'get_invoice_details',
                    'received_at' => now()->toIso8601String(),
                    'payload' => $details['result'] ?? [],
                ]
            );
            $payment->save();

            if (! empty($details['paid'])) {
                $finalizer->apply($payment->fresh(), Payment::STATUS_PAID);
                Log::info('Registration payment marked paid via GetInvoiceDetails', [
                    'payment_id' => $payment->id,
                    'order_code' => $payment->calpay_order_code,
                    'calpay_status' => $details['status'] ?? null,
                ]);
                break;
            }

            if (! empty($details['status'])) {
                $normalized = $finalizer->normalizeStatus($details['status']);
                if ($normalized !== Payment::STATUS_PENDING) {
                    $finalizer->apply($payment->fresh(), $normalized);
                }
            }
        }
    }
}
