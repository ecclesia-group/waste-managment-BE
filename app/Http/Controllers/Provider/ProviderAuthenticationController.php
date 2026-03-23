<?php
namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Provider;
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
                $provider = self::apiToken($provider, "provider");
                return self::apiResponse(
                    in_error: false,
                    message: "Action Successful",
                    reason: "Provider logged in successful",
                    status_code: self::API_SUCCESS,
                    data: $provider->toArray()
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
