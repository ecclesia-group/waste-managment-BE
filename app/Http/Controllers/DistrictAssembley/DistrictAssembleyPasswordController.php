<?php
namespace App\Http\Controllers\DistrictAssembley;

use App\Http\Controllers\Controller;
use App\Http\Requests\DistrictAssembley\PasswordChangeResetRequest;
use App\Http\Requests\DistrictAssembley\PasswordResetRequest;
use App\Models\DistrictAssembly;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistrictAssembleyPasswordController extends Controller
{
    // Handles password change for vendor
    public function changePassword(PasswordChangeResetRequest $http_request): JsonResponse
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
        $district_assembly = DistrictAssembly::where("email", request("emailOrPhone"))
            ->orWhere("phone_number", request("emailOrPhone"))
            ->first();

        // Send reset password notification
        return self::sendActorResetPasswordNotification(actor: $district_assembly, guard: "district_assembly");
    }

    public function resetPassword(PasswordResetRequest $http_request)
    {
        $data = $http_request->validated();
        $user = DistrictAssembly::where("district_assembly_slug", $data["district_assembly_slug"])->first();

        return self::resetActorPassword(
            otp: $data["otp"],
            actor: $user,
            guard: "district_assembly",
            password: $data["password"]
        );
    }
}
