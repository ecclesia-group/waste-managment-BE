<?php
namespace App\Http\Controllers\DistrictAssembley;

use App\Http\Controllers\Controller;
use App\Models\DistrictAssembly;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DistrictAssembleyAuthenticationController extends Controller
{
    public function login(): JsonResponse
    {
        $district_assembly = self::findActorByEmailOrPhone(DistrictAssembly::class, (string) request('emailOrPhone'));

        if ($district_assembly) {
            $bool = Hash::check(request('password'), $district_assembly->password);
            if ($bool) {
                if (($district_assembly->status ?? 'active') !== 'active') {
                    return self::apiResponse(
                        in_error: true,
                        message: "Action Unsuccessful",
                        reason: "District Assembly account is suspended or deactivated",
                        status_code: self::API_FAIL,
                        data: [
                            'status' => $district_assembly->status,
                            'popup' => [
                                'title' => 'Account suspended',
                                'reason' => $district_assembly->suspension_reason,
                                'corrective_action' => $district_assembly->corrective_action,
                                'suspended_at' => $district_assembly->suspended_at?->toISOString(),
                            ],
                        ]
                    );
                }

                if (! (bool) ($district_assembly->is_main ?? true)) {
                    $role = Role::query()
                        ->where('role_slug', $district_assembly->role_slug)
                        ->where('actor', 'district_assembly')
                        ->where('actor_slug', $district_assembly->ownerSlug())
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
                $district_assembly = self::apiToken($district_assembly, "district_assembly");
                $data = array_merge($district_assembly->toArray(), $district_assembly->rbacForFrontend());
                return self::apiResponse(
                    in_error: false,
                    message: "Action Successful",
                    reason: "District Assembly logged in successful",
                    status_code: self::API_SUCCESS,
                    data: $data
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
