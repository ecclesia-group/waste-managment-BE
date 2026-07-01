<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\ClientRegistrationCheckoutService;
use Illuminate\Http\Request;

class ClientPaymentController extends Controller
{
    public function createRegistrationPayment(Request $request)
    {
        $data = $request->validate([
            'datacompleteurl' => ['required', 'url', 'max:500'],
            'datacancelurl' => ['required', 'url', 'max:500'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_contact' => ['nullable', 'string', 'max:50'],
        ]);

        /** @var Client $client */
        $client = Client::query()
            ->where('client_slug', $request->user()->client_slug)
            ->with(['fee', 'bins.product'])
            ->firstOrFail();

        if (! $client->requiresRegistrationPayment()) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Registration payment is not required for this account',
                status_code: self::API_FAIL,
                data: [
                    'registration_status' => (bool) $client->registration_status,
                ]
            );
        }

        try {
            $checkout = app(ClientRegistrationCheckoutService::class)->startCheckout($client, $data);
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
            reason: 'Registration checkout created — redirect user to payment_url',
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
