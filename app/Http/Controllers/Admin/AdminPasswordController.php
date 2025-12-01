<?php
namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PasswordResetRequest;
use App\Http\Requests\Admin\PasswordChangeRequest;

class AdminPasswordController extends Controller
{
    // Handles password change for vendor
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
        $admin = Admin::where("email", request("emailOrPhone"))
            ->orWhere("phone_number", request("emailOrPhone"))
            ->first();

        // Send reset password notification
        return self::sendActorResetPasswordNotification(actor: $admin, guard: "admin");
    }

    public function resetPassword(PasswordResetRequest $http_request)
    {
        $data = $http_request->validated();
        $user = Admin::where("admin_slug", $data["admin_slug"])->first();

        return self::resetActorPassword(
            otp:$data["otp"],
            actor:$user,
            guard:"admin",
            password:$data["password"]
        );
    }
}
