<?php
namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Http\Requests\Facility\FacilityPasswordChangeResetRequest;
use App\Http\Requests\Facility\FacilityPasswordResetRequest;
use App\Models\Facility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityPasswordController extends Controller
{
    // Handles password change for vendor
    public function changePassword(FacilityPasswordChangeResetRequest $http_request): JsonResponse
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
        dd("here");
        $facility = Facility::where("email", request("emailOrPhone"))
            ->orWhere("phone_number", request("emailOrPhone"))
            ->first();
        dd($facility, request("emailOrPhone"));

        // Send reset password notification
        return self::sendActorResetPasswordNotification(actor: $facility, guard: "facility");
    }

    public function resetPassword(FacilityPasswordResetRequest $http_request)
    {
        $data = $http_request->validated();
        $user = Facility::where("facility_slug", $data["facility_slug"])->first();

        return self::resetActorPassword(
            otp: $data["otp"],
            actor: $user,
            guard: "facility",
            password: $data["password"]
        );
    }
}
