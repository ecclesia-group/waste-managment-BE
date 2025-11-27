<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminOnboardingController extends Controller
{
    public function registerProvider(Request $request)
    {
        // Logic to register a provider goes here

        return self::apiResponse(in_error: false, message: "Action Successful", reason: "Provider registered successfully", status_code: 200, data: []);
    }
}
