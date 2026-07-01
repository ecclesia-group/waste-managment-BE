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
                    'reason' => 'Registration fee payment is required before using the system',
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
