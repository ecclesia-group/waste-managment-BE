<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
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
            'name' => ['nullable', 'string'],
            'currency' => ['nullable', 'string'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $client = Client::query()->where('client_slug', $user->client_slug)->first();

        if (! $client) {
            return self::apiResponse(true, 'Action Failed', 'Client not found', self::API_NOT_FOUND, []);
        }

        if (! $client->requiresRegistrationPayment()) {
            return self::apiResponse(
                in_error: false,
                message: 'Action Successful',
                reason: 'Registration fee already satisfied',
                status_code: self::API_SUCCESS,
                data: [
                    'client' => $client->fresh()->load('group')->toArray(),
                ]
            );
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
                'status' => Payment::STATUS_PAID,
                'purchase_id' => '0',
                'pickup_id' => '0',
            ]);

            $client->registration_status = true;
            $client->save();
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
                'client' => $client->fresh()->load('group')->toArray(),
            ]
        );
    }

    public function registrationPaymentStatus(Request $request)
    {
        $client = Client::query()->where('client_slug', $request->user()->client_slug)->first();

        if (! $client) {
            return self::apiResponse(true, 'Action Failed', 'Client not found', self::API_NOT_FOUND, []);
        }

        $client->syncRegistrationStatusFromPayments();
        $client->refresh();

        $paid = Payment::query()
            ->where('client_slug', $client->client_slug)
            ->where('payment_type', Payment::PAYMENT_TYPE_REGISTRATION_FEE)
            ->where('status', Payment::STATUS_PAID)
            ->latest()
            ->first();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Registration payment status retrieved successfully',
            status_code: self::API_SUCCESS,
            data: [
                'registration_fee' => (float) ($client->registration_fee ?? 0),
                'registration_status' => (bool) $client->registration_status,
                'is_paid' => ! $client->requiresRegistrationPayment(),
                'payment' => $paid?->toArray(),
            ]
        );
    }
}
