<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\ClientRegistrationCheckoutService;
use Illuminate\Http\Request;

class ClientPaymentController extends Controller
{
    /**
     * One-step registration fee payment for the logged-in client.
     * payment_type + reference are set on the server — client only sends optional contact/redirect overrides.
     */
    public function createRegistrationPayment(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_contact' => ['nullable', 'string', 'max:50'],
            // Optional — auto-built from CLIENT_URL if omitted
            'datacompleteurl' => ['nullable', 'url', 'max:500'],
            'datacancelurl' => ['nullable', 'url', 'max:500'],
        ]);

        /** @var Client $client */
        $client = Client::query()
            ->where('client_slug', $request->user()->client_slug)
            ->with(['fee', 'bins.product', 'group'])
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

        $clientBase = rtrim((string) (config('custom.urls.backend_url') ?: config('app.url')), '/');

        $checkoutData = [
            'customer_name' => $data['customer_name'] ?? trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')),
            'customer_email' => $data['customer_email'] ?? $client->email,
            'customer_contact' => $data['customer_contact'] ?? $client->phone_number,
            'datacompleteurl' => $data['datacompleteurl'] ?? ($clientBase . '/payment/success'),
            'datacancelurl' => $data['datacancelurl'] ?? ($clientBase . '/payment/cancelled'),
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
            data: array_merge($checkout, [
                'client' => $client->fresh(['group', 'bins.product', 'fee'])->toArray(),
            ])
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

        $client->syncRegistrationStatusFromPayments();
        $client->refresh();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Registration payment status retrieved successfully',
            status_code: self::API_SUCCESS,
            data: array_merge($client->load('group', 'bins.product', 'fee')->toArray(), [
                'requires_registration_payment' => $client->requiresRegistrationPayment(),
            ]),
        );
    }
}
