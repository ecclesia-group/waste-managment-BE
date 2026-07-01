<?php
namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class FacilityAuthenticationController extends Controller
{
    public function login(): JsonResponse
    {
        $facility = self::findActorByEmailOrPhone(Facility::class, (string) request('emailOrPhone'));

        if ($facility) {
            $bool = Hash::check(request('password'), $facility->password);
            if ($bool) {
                if (($facility->status ?? 'active') !== 'active') {
                    return self::apiResponse(
                        in_error: true,
                        message: "Action Unsuccessful",
                        reason: "Facility account is suspended or deactivated",
                        status_code: self::API_FAIL,
                        data: [
                            'status' => $facility->status,
                            'popup' => [
                                'title' => 'Account suspended',
                                'reason' => $facility->suspension_reason,
                                'corrective_action' => $facility->corrective_action,
                                'suspended_at' => $facility->suspended_at?->toISOString(),
                            ],
                        ]
                    );
                }

                if (! (bool) ($facility->is_main ?? true)) {
                    $role = Role::query()
                        ->where('role_slug', $facility->role_slug)
                        ->where('actor', 'facility')
                        ->where('actor_slug', $facility->ownerSlug())
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
                $facility = self::apiToken($facility, "facility");
                $data = array_merge($facility->toArray(), $facility->rbacForFrontend());
                return self::apiResponse(
                    in_error: false,
                    message: "Action Successful",
                    reason: "Facility logged in successful",
                    status_code: self::API_SUCCESS,
                    data: $data
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
