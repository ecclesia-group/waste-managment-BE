<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminProviderRegisterRequest;

class AdminOnboardingController extends Controller
{
    public function registerProvider(AdminProviderRegisterRequest $request)
    {
        // Logic to register a provider goes here
        $data = $request->validated();

        dd($data);

        return self::apiResponse(in_error: false, message: "Action Successful", reason: "Provider registered successfully", status_code: 200, data: []);
    }
}
