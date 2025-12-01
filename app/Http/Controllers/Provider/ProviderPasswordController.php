<?php
namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\ProviderPasswordChangeResetRequest;
use App\Http\Requests\Provider\ProviderPasswordResetRequest;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderPasswordController extends Controller
{

    // Handles password change for vendor
    public function changePassword(ProviderPasswordChangeResetRequest $http_request): JsonResponse
    {
        $validated_password = $http_request->validated();
        request()->user()->update([
            "password" => $validated_password['password'],
        ]);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Password changed successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    public function sendResetPasswordNotification()
    {
        $provider = Provider::where("email", request("emailOrPhone"))
            ->orWhere("phone_number", request("emailOrPhone"))
            ->first();

        // Send reset password notification
        return self::sendActorResetPasswordNotification(actor: $provider, guard: "provider");
    }

    public function resetPassword(ProviderPasswordResetRequest $http_request)
    {
        $data = $http_request->validated();
        $user = Provider::where("provider_slug", $data["provider_slug"])->first();

        return self::resetActorPassword(
            otp: $data["otp"],
            actor: $user,
            guard: "provider",
            password: $data["password"]
        );
    }
}
