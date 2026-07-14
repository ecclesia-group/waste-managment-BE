<?php

namespace App\Http\Middleware;

use App\Traits\ApiTransformer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientRegistrationPayment
{
    use ApiTransformer;

    public function handle(Request $request, Closure $next): Response
    {
        $client = $request->user('client');

        if ($client && $client->requiresRegistrationPayment()) {
            return response()->json([
                'data' => [
                    'status_code' => self::API_FAIL,
                    'message' => 'Action Unsuccessful',
                    'in_error' => true,
                    'reason' => 'Registration fee payment is required. Please pay the registration fee to continue.',
                    // 'reason' => 'Registration fee payment is required. Use POST /api/client/payments/registration or POST /api/client/payments/calpay/initiate.',
                    'data' => [
                        'requires_registration_payment' => true,
                        'registration_fee' => (float) ($client->registration_fee ?? 0),
                        'registration_status' => false,
                        'client_slug' => $client->client_slug,
                    ],
                    'point_in_time' => now(),
                ],
            ], 402);
        }

        return $next($request);
    }
}
