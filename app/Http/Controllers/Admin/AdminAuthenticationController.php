<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthenticationController extends Controller
{
    public function login(): JsonResponse
    {
        // find admin by email
        $admin = Admin::where("email", request("emailOrPhone"))
            ->orWhere("phone_number", request("emailOrPhone"))
            ->first();

        // If admin exists
        if ($admin) {
            // Check if the provided password matches the stored password
            $bool = Hash::check(request('password'), $admin->password);

            // If password matches
            if ($bool) {
                if (($admin->status ?? 'active') !== 'active') {
                    return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Admin account is suspended or deactivated", status_code: self::API_FAIL, data: []);
                }

                if (! (bool) ($admin->is_main ?? true)) {
                    $ownerSlug = $admin->ownerSlug();
                    $role = Role::query()
                        ->where('role_slug', $admin->role_slug)
                        ->where('actor', 'admin')
                        ->where('actor_slug', $ownerSlug)
                        ->first();

                    if (! $role) {
                        return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Team member role is missing or invalid", status_code: self::API_FAIL, data: []);
                    }
                }

                // Generate API token for the admin
                $admin = self::apiToken($admin, "admin");
                $data = array_merge($admin->toArray(), $admin->rbacForFrontend());
                // Return success response
                return self::apiResponse(in_error: false, message: "Action Successful", reason: "Admin logged in successful", status_code: self::API_SUCCESS, data: $data);
            }

            // If password does not match
            return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Mismatched Password", status_code: self::API_FAIL, data: []);
        }

        // If admin does not exist, return failure response
        return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Admin cannot be found", status_code: self::API_NOT_FOUND, data: []);
    }

    // Handles admin logout
    public function logout()
    {
        // Revoke the current admin token
        request()->user()->token()->revoke();

        // Return success response
        return self::apiResponse(in_error: false, message: "Action Successful", reason: "Logout successful", status_code: self::API_SUCCESS, data: []);
    }
}
