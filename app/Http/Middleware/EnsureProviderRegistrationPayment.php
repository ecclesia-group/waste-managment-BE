<?php

namespace App\Http\Middleware;

use App\Models\Payment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProviderRegistrationPayment
{
    public function handle(Request $request, Closure $next): Response
    {
        $provider = $request->user();
        $providerSlug = (string) ($provider->provider_slug ?? '');

        // Allow access to verification/profile/logout/basic payment routes while onboarding is incomplete.
        $path = trim($request->path(), '/');
        $isAllowedDuringOnboarding = in_array($path, [
            'api/provider/logout',
            'api/provider/change_password',
            'api/provider/payments',
            'api/provider/payments/registration',
            'api/provider/payments/registration/status',
        ], true)
            || str_starts_with($path, 'api/provider/get_single_payment/')
            || str_starts_with($path, 'api/provider/update_profile/');

        if ($isAllowedDuringOnboarding) {
            return $next($request);
        }

        $hasRegistrationPayment = Payment::query()
            ->where('provider_slug', $providerSlug)
            ->where('pickup_id', 'provider_registration')
            ->whereIn('status', ['successful', 'success'])
            ->exists();

        if (! $hasRegistrationPayment) {
            return response()->json([
                'data' => [
                    'status_code' => 403,
                    'message' => 'Action Failed',
                    'in_error' => true,
                    'reason' => 'Provider registration payment is required before using the system',
                    'data' => [
                        'requires_registration_payment' => true,
                    ],
                ],
            ], 403);
        }

        return $next($request);
    }
}
