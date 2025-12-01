<?php

use App\Http\Controllers\Admin\AdminAuthenticationController;
use App\Http\Controllers\Admin\AdminPasswordController;
use App\Http\Controllers\Admin\AdminZoneManagementController;
use App\Http\Controllers\Facility\FacilityAuthenticationController;
use App\Http\Controllers\Facility\FacilityController;
use App\Http\Controllers\Facility\FacilityPasswordController;
use App\Http\Controllers\Provider\ProviderAuthenticationController;
use App\Http\Controllers\Provider\ProviderController;
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
    // Route::put("update_provider_details/{provider_slug}", [ProviderController::class, "updateProfile"]);
    // });
});

Route::prefix("facility")->group(function () {
    Route::post("login", [FacilityAuthenticationController::class, "login"]);
    // Route::post("verify/account", [FacilityAuthenticationController::class, "verifyAccount"]);
    Route::post("email/resetpassword", [FacilityPasswordController::class, "sendResetPasswordNotification"]);
    Route::post("resetpassword", [FacilityPasswordController::class, "resetPassword"]);
    Route::post("resend/verificationCode", [FacilityAuthenticationController::class, "resendVerificationCode"]);

    // Route::middleware(["auth:user", "verified"])->group(function () {
    //     Route::apiResource("deals", UserDealController::class);
    // Route::put("update_provider_details/{provider_slug}", [ProviderController::class, "updateProfile"]);
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

        // Provider, Facility, District Assembly Onboarding Management

        // Provider Management
        Route::post("register_provider", [ProviderController::class, "register"]);
        Route::get("all_providers", [ProviderController::class, "index"]);
        Route::get("get_single_provider/{provider_slug}", [ProviderController::class, "show"]);
        Route::post("update_provider_status", [ProviderController::class, "updateStatus"]);
        Route::put("update_provider_details/{provider_slug}", [ProviderController::class, "updateProviderProfile"]);

        // Facility Management
        Route::post("register_facility", [FacilityController::class, "register"]);
        Route::get("all_facilities", [FacilityController::class, "index"]);
        Route::get("get_single_facility/{facility}", [FacilityController::class, "show"]);
        Route::post("update_facility_status", [FacilityController::class, "updateStatus"]);
        Route::put("update_facility_details/{facility_slug}", [FacilityController::class, "updateFacilityProfile"]);

        // Zone Management
        Route::get('all_zones', [AdminZoneManagementController::class, 'listZones']);
        Route::get('get_single_zone/{zone_slug}', [AdminZoneManagementController::class, 'getZoneDetails']);
        Route::post('create_zone', [AdminZoneManagementController::class, 'createZone']);
        Route::put('update_zone/{zone_slug}', [AdminZoneManagementController::class, 'updateZone']);
        Route::delete('delete_zone/{zone_slug}', [AdminZoneManagementController::class, 'deleteZone']);
    });
});
