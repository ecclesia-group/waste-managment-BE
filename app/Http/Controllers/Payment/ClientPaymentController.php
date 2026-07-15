<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Services\ClientRegistrationCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientPaymentController extends Controller
{
    /**
     * One-step registration fee payment for the logged-in client.
     * After paying on CalPay, open the client success/cancel page, then call
     * GET payments/registration/status before unlocking the dashboard.
     */
    public function createRegistrationPayment(Request $request)
    {
        $data = $request->validate([
            'payment_method' => ['required', 'string', 'in:momo,card'],
            'network' => ['nullable', 'string', 'max:50'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_contact' => ['nullable', 'string', 'max:50'],
            // Optional — auto-built from CLIENT_URL if omitted
            'datacompleteurl' => ['nullable', 'url', 'max:500'],
            'datacancelurl' => ['nullable', 'url', 'max:500'],
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
            ->with(['fee'])
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

        // Browser returns MUST hit the backend first so we can finalize the payment
        // (CalPay server callback often never fires). Backend then redirects to CLIENT_URL.
        $backendBase = rtrim((string) (
            config('custom.urls.backend_url')
            ?: config('app.url')
        ), '/');

        // If APP_URL is local but CalPay callback is public, derive backend from callback host.
        $callbackUrl = (string) config('services.calpay.callback_url');
        if (
            $callbackUrl !== ''
            && (str_contains($backendBase, 'localhost') || str_contains($backendBase, '127.0.0.1'))
        ) {
            $backendBase = rtrim((string) preg_replace('#/api/payment_callback/?$#i', '', $callbackUrl), '/');
        }

        $completeUrl = $data['datacompleteurl'] ?? ($backendBase.'/payment/success');
        $cancelUrl = $data['datacancelurl'] ?? ($backendBase.'/payment/cancelled');

        Log::info('Registration payment redirect URLs', [
            'backend_base' => $backendBase,
            'client_base' => config('custom.urls.client_url'),
            'datacompleteurl' => $completeUrl,
            'datacancelurl' => $cancelUrl,
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
        ];

        try {
            $checkout = app(ClientRegistrationCheckoutService::class)->startCheckout($client, $checkoutData);
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
            reason: 'Registration payment created — redirect user to payment_url',
            status_code: self::API_CREATED,
            data: $checkout
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

        // Client success page can forward CalPay query params so we finalize
        // even when the server callback never arrives.
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
}
