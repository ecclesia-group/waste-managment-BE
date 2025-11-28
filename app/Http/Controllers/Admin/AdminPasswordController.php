<?php
namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminPasswordResetRequest;
use App\Http\Requests\Admin\AdminPasswordChangeRequest;

class AdminPasswordController extends Controller
{
    // Handles password change for vendor
    public function changePassword(AdminPasswordChangeRequest $http_request): JsonResponse
    {
        // Validate password from request
        $validated_password = $http_request->validated();
        // Update admin's password
        request()->user()->update([
            "password" => $validated_password['password'],
        ]);
        // Return success response
        return self::apiResponse(in_error: false, message: "Action Successful", reason: "Password changed successfully", status_code: self::API_SUCCESS, data: []);
    }

    // Sends password reset notification to admin
    public function sendResetPasswordNotification()
    {
        // Find admin by email or phone number
        $admin = Admin::where("email", request("emailOrPhone"))
            ->orWhere("phone_number", request("emailOrPhone"))
            ->first();

        // Send reset password notification
        return self::sendActorResetPasswordNotification(actor: $admin, guard: "admin");
    }

    // Handles password reset for admin
    public function resetPassword(AdminPasswordResetRequest $http_request)
    {
        // Validate request data
        $data = $http_request->validated();
        // Find admin by admin_slug
        $user = Admin::where("admin_slug", $data["admin_slug"])->first();
        // Reset admin's password using OTP
        return self::resetActorPassword(otp:$data["otp"], actor:$user, guard:"admin", password:$data["password"]);
    }
}
