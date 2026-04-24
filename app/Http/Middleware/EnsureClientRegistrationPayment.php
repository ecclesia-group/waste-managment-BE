<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientRegistrationPayment
{
    public function handle(Request $request, Closure $next): Response
    {
        $tokenClient = $request->user();
        $clientSlug = (string) ($tokenClient->client_slug ?? '');

        $path = trim($request->path(), '/');
        $isAllowedBeforeRegistrationPaid = in_array($path, [
            'api/client/logout',
            'api/client/change_password',
            'api/client/payments/registration',
            'api/client/payments/registration/status',
        ], true)
            || str_starts_with($path, 'api/client/update_profile/');

        if ($isAllowedBeforeRegistrationPaid) {
            return $next($request);
        }

        $client = Client::query()->where('client_slug', $clientSlug)->first();
        if (! $client) {
            return response()->json([
                'data' => [
                    'status_code' => 403,
                    'message' => 'Action Failed',
                    'in_error' => true,
                    'reason' => 'Client record not found',
                    'data' => [],
                ],
            ], 403);
        }

        $client->syncRegistrationStatusFromPayments();
        $client->refresh();

        if ($client->registration_status) {
            return $next($request);
        }

        return response()->json([
            'data' => [
                'status_code' => 403,
                'message' => 'Action Failed',
                'in_error' => true,
                'reason' => 'Registration fee payment is required before using the app',
                'data' => $client->load('group')->toArray(),
            ],
        ], 403);
    }
}
