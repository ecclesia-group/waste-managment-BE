<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Bin;
use App\Models\Client;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientPaymentController extends Controller
{
    public function createRegistrationPayment(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string'],
            'network' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'name' => ['nullable', 'string'],
            'currency' => ['nullable', 'string'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'client_slug' => ['required', 'string', 'exists:clients,client_slug'],
        ]);

        $client = Client::query()->where('client_slug', $data['client_slug'])->first();

        if (! $client) {
            return self::apiResponse(true, 'Action Failed', 'Client not found', self::API_NOT_FOUND, []);
        }

        $expected = round((float) ($client->registration_fee ?? 0), 2);
        $submitted = round((float) $data['amount'], 2);
        if (abs($submitted - $expected) > 0.02) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Amount must match the registration fee set by your provider',
                status_code: self::API_FAIL,
                data: ['registration_fee' => $expected],
            );
        }

        $clientSlug = (string) $client->client_slug;
        $providerSlug = (string) $client->provider_slug;

        $payment = null;
        DB::transaction(function () use ($data, $client, $clientSlug, $providerSlug, $expected, &$payment) {
            $payment = Payment::create([
                'client_slug' => $clientSlug,
                'provider_slug' => $providerSlug,
                'payment_type' => Payment::PAYMENT_TYPE_REGISTRATION_FEE,
                'transaction_id' => $data['transaction_id'] ?? ('CREG-' . Str::upper(Str::random(12))),
                'payment_method' => $data['payment_method'],
                'network' => $data['network'] ?? 'unknown',
                'phone_number' => $data['phone_number'] ?? null,
                'name' => $data['name'] ?? trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')) ?: 'client',
                'client_email' => $client->email ?? null,
                'card_name' => null,
                'card_number' => null,
                'card_expiry' => null,
                'card_cvv' => null,
                'amount' => $expected,
                'currency' => $data['currency'] ?? 'GHS',
                'status' => Payment::STATUS_PENDING,
                'purchase_id' => null,
                'pickup_id' => null,
                'payment_type' => 'registration',
            ]);

            $client->registration_status = false;
            $client->save();

            if (! empty($client->bin_code)) {
                Bin::query()->updateOrCreate(
                    ['bin_code' => $client->bin_code],
                    [
                        'bin_slug' => (string) Str::uuid(),
                        'client_slug' => $clientSlug,
                        'provider_slug' => $providerSlug,
                        'product_slug' => null,
                        'source' => 'registration',
                        'status' => 'active',
                    ]
                );
            }
        });

        if (! $payment instanceof Payment) {
            return self::apiResponse(true, 'Action Failed', 'Could not record payment', self::API_FAIL, []);
        }

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Registration fee payment recorded successfully',
            status_code: self::API_CREATED,
            data: [
                'payment' => $payment->toArray(),
                'client' => $client->fresh()->load('group', 'groups', 'bins')->toArray(),
            ]
        );
    }

    public function registrationPaymentStatus(Request $request)
    {
        $client = Client::query()->where('client_slug', $request->user()->client_slug)->first();

        if (! $client) {
            return self::apiResponse(true, 'Action Failed', 'Client not found', self::API_NOT_FOUND, []);
        }

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Registration payment status retrieved successfully',
            status_code: self::API_SUCCESS,
            data: $client->load('group', 'groups', 'bins')->toArray(),
        );
    }
}
