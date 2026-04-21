<?php
namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProviderAuthenticationController extends Controller
{
    public function login(): JsonResponse
    {
        $provider = Provider::where("email", request("emailOrPhone"))
            ->orWhere("phone_number", request("emailOrPhone"))
            ->first();

        if ($provider) {
            $bool = Hash::check(request('password'), $provider->password);
            if ($bool) {
                if (($provider->status ?? 'active') !== 'active') {
                    return self::apiResponse(
                        in_error: true,
                        message: "Action Unsuccessful",
                        reason: "Provider account is suspended or deactivated",
                        status_code: self::API_FAIL,
                        data: [
                            'status' => $provider->status,
                            'popup' => [
                                'title' => 'Account suspended',
                                'reason' => $provider->suspension_reason,
                                'corrective_action' => $provider->corrective_action,
                                'suspended_at' => $provider->suspended_at?->toISOString(),
                            ],
                        ]
                    );
                }

                if (! (bool) ($provider->is_main ?? true)) {
                    $role = Role::query()
                        ->where('role_slug', $provider->role_slug)
                        ->where('actor', 'provider')
                        ->where('actor_slug', $provider->ownerSlug())
                        ->first();

                    if (! $role) {
                        return self::apiResponse(
                            in_error: true,
                            message: "Action Unsuccessful",
                            reason: "Team member role is missing or invalid",
                            status_code: self::API_FAIL,
                            data: []
                        );
                    }
                }
                $provider = self::apiToken($provider, "provider");
                $requiresRegistrationPayment = ! Payment::query()
                    ->where('provider_slug', (string) $provider->provider_slug)
                    ->where('pickup_id', 'provider_registration')
                    ->whereIn('status', ['successful', 'success'])
                    ->exists();

                $data = array_merge($provider->toArray(), $provider->rbacForFrontend(), [
                    'requires_registration_payment' => $requiresRegistrationPayment,
                ]);
                return self::apiResponse(
                    in_error: false,
                    message: "Action Successful",
                    reason: "Provider logged in successful",
                    status_code: self::API_SUCCESS,
                    data: $data
                );
            }
        }

        return self::apiResponse(
            in_error: true,
            message: "Action Unsuccessful",
            reason: "Provider cannot be found",
            status_code:
            self::API_FAIL,
            data: []
        );
    }

    public function logout()
    {
        request()->user()->token()->revoke();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Logout successful",
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}
