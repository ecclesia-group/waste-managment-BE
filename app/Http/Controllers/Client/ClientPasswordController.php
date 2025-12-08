<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\PasswordChangeRequest;
use App\Http\Requests\Client\PasswordResetRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientPasswordController extends Controller
{
    public function changePassword(PasswordChangeRequest $http_request): JsonResponse
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
        $client = Client::where("email", request("emailOrPhone"))
            ->orWhere("phone_number", request("emailOrPhone"))
            ->first();

        // Send reset password notification
        return self::sendActorResetPasswordNotification(actor: $client, guard: "client");
    }

    public function resetPassword(PasswordResetRequest $http_request)
    {
        $data = $http_request->validated();
        $user = Client::where("client_slug", $data["client_slug"])->first();

        return self::resetActorPassword(
            otp: $data["otp"],
            actor: $user,
            guard: "client",
            password: $data["password"]
        );
    }
}
