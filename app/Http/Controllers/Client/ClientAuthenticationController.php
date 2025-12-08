<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientAuthenticationController extends Controller
{
    public function login(): JsonResponse
    {
        // find client by email
        $client = Client::where("email", request("emailOrPhone"))
            ->orWhere("phone_number", request("emailOrPhone"))
            ->first();

        // If client exists
        if ($client) {
            // Check if the provided password matches the stored password
            $bool = Hash::check(request('password'), $client->password);

            // If password matches
            if ($bool) {
                // Generate API token for the admin
                $client = self::apiToken($client, "client");
                // Return success response
                return self::apiResponse(in_error: false, message: "Action Successful", reason: "Client logged in successful", status_code: self::API_SUCCESS, data: $client->toArray());
            }

            // If password does not match
            return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Mismatched Password", status_code: self::API_FAIL, data: []);
        }

        // If client does not exist, return failure response
        return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Client cannot be found", status_code: self::API_NOT_FOUND, data: []);
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
