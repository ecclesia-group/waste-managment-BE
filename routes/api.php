<?php

use App\Http\Controllers\Admin\AdminAuthenticationController;
use App\Http\Controllers\Admin\AdminOnboardingController;
use App\Http\Controllers\Admin\AdminPasswordController;
use App\Http\Controllers\Provider\ProviderAuthenticationController;
use App\Http\Controllers\Provider\ProviderPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get("yes", function () {
    return "yes yes";
});

Route::prefix("provider")->group(function () {
    Route::post("login", [ProviderAuthenticationController::class, "login"]);
    // Route::post("verify/account", [ProviderAuthenticationController::class, "verifyAccount"]);
    Route::post("email/resetpassword", [ProviderPasswordController::class, "sendResetPasswordNotification"]);
    Route::post("resetpassword", [ProviderPasswordController::class, "resetPassword"]);
    Route::post("resend/verificationCode", [ProviderAuthenticationController::class, "resendVerificationCode"]);

    // Route::middleware(["auth:user", "verified"])->group(function () {
    //     Route::apiResource("deals", UserDealController::class);
    // });
});

Route::prefix("admin")->group(function () {
    Route::post("login", [AdminAuthenticationController::class, "login"]);
    Route::post("reset_password_notification", [AdminPasswordController::class, "sendResetPasswordNotification"]);
    // Route::post("verify_account", [AdminAuthenticationController::class, "verifyAccount"]);
    Route::post("reset_password", [AdminPasswordController::class, "resetPassword"]);
    Route::post("resend_verificationCode", [AdminAuthenticationController::class, "resendVerificationCode"]);

    Route::middleware(["auth:admin", "verified"])->group(function () {
        // Route::apiResource("deals", UserDealController::class);

        Route::post("logout", [AdminAuthenticationController::class, "logout"]);
        Route::post("change_password", [AdminPasswordController::class, "changePassword"]);

        // Provider, Facility, MMDA Onboarding Management
        Route::post("register_provider", [AdminOnboardingController::class, "registerProvider"]);

    });
});
