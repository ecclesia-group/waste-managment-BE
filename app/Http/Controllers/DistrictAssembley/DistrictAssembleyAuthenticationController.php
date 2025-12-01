<?php
namespace App\Http\Controllers\DistrictAssembley;

use App\Http\Controllers\Controller;
use App\Models\DistrictAssembly;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DistrictAssembleyAuthenticationController extends Controller
{
    public function login(): JsonResponse
    {
        $district_assembly = DistrictAssembly::where("email", request("emailOrPhone"))
            ->orWhere("phone_number", request("emailOrPhone"))
            ->first();

        if ($district_assembly) {
            $bool = Hash::check(request('password'), $district_assembly->password);
            if ($bool) {
                $district_assembly = self::apiToken($district_assembly, "district_assembly");
                return self::apiResponse(
                    in_error: false,
                    message: "Action Successful",
                    reason: "District Assembly logged in successful",
                    status_code: self::API_SUCCESS,
                    data: $district_assembly->toArray()
                );
            }
        }

        return self::apiResponse(
            in_error: true,
            message: "Action Unsuccessful",
            reason: "District Assembly cannot be found",
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
