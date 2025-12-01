<?php
namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class FacilityAuthenticationController extends Controller
{
    public function login(): JsonResponse
    {
        $facility = Facility::where("email", request("emailOrPhone"))
            ->orWhere("phone_number", request("emailOrPhone"))
            ->first();

        if ($facility) {
            $bool = Hash::check(request('password'), $facility->password);
            if ($bool) {
                $facility = self::apiToken($facility, "facility");
                return self::apiResponse(
                    in_error: false,
                    message: "Action Successful",
                    reason: "Facility logged in successful",
                    status_code: self::API_SUCCESS,
                    data: $facility->toArray()
                );
            }
        }

        return self::apiResponse(
            in_error: true,
            message: "Action Unsuccessful",
            reason: "Facility cannot be found",
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
