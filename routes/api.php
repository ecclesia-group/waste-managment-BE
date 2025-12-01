<?php

use App\Http\Controllers\Admin\AdminAuthenticationController;
use App\Http\Controllers\Admin\AdminPasswordController;
use App\Http\Controllers\Facility\FacilityAuthenticationController;
use App\Http\Controllers\Facility\FacilityController;
use App\Http\Controllers\Facility\FacilityPasswordController;
use App\Http\Controllers\Provider\ProviderAuthenticationController;
use App\Http\Controllers\Provider\ProviderController;
use App\Http\Controllers\Provider\ProviderPasswordController;
use App\Http\Controllers\ZoneManagementController;
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
    Route::post("reset_password_notification", [ProviderPasswordController::class, "sendResetPasswordNotification"]);
    Route::post("resetpassword", [ProviderPasswordController::class, "resetPassword"]);
    Route::post("resend_verificationCode", [ProviderPasswordController::class, "sendResetPasswordNotification"]);

    Route::middleware(["auth:provider"])->group(function () {
        Route::post("change_password", [ProviderPasswordController::class, "changePassword"]);
        Route::post("logout", [ProviderAuthenticationController::class, "logout"]);
    });
});

Route::prefix("facility")->group(function () {
    Route::post("login", [FacilityAuthenticationController::class, "login"]);
    Route::post("reset_password_notification", [FacilityPasswordController::class, "sendResetPasswordNotification"]);
    Route::post("resetpassword", [FacilityPasswordController::class, "resetPassword"]);
    Route::post("resend_verificationCode", [ProviderPasswordController::class, "sendResetPasswordNotification"]);

    Route::middleware(["auth:facility"])->group(function () {
        Route::post("change_password", [FacilityPasswordController::class, "changePassword"]);
        Route::post("logout", [FacilityAuthenticationController::class, "logout"]);
    });
});

Route::prefix("admin")->group(function () {
    Route::post("login", [AdminAuthenticationController::class, "login"]);
    Route::post("reset_password_notification", [AdminPasswordController::class, "sendResetPasswordNotification"]);
    Route::post("reset_password", [AdminPasswordController::class, "resetPassword"]);
    Route::post("resend_verificationCode", [AdminPasswordController::class, "sendResetPasswordNotification"]);

    Route::middleware(["auth:admin", "verified"])->group(function () {
        Route::post("logout", [AdminAuthenticationController::class, "logout"]);
        Route::post("change_password", [AdminPasswordController::class, "changePassword"]);

        // Provider Management
        Route::post("register_provider", [ProviderController::class, "register"]);
        Route::get("all_providers", [ProviderController::class, "index"]);
        Route::get("get_single_provider/{provider}", [ProviderController::class, "show"]);
        Route::post("update_provider_status", [ProviderController::class, "updateStatus"]);
        Route::put("update_provider_details/{provider}", [ProviderController::class, "updateProviderProfile"]);

        // Facility Management
        Route::post("register_facility", [FacilityController::class, "register"]);
        Route::get("all_facilities", [FacilityController::class, "index"]);
        Route::get("get_single_facility/{facility}", [FacilityController::class, "show"]);
        Route::post("update_facility_status", [FacilityController::class, "updateStatus"]);
        Route::put("update_facility_details/{facility_slug}", [FacilityController::class, "updateFacilityProfile"]);

        // Zone Management
        Route::get('all_zones', [ZoneManagementController::class, 'listZones']);
        Route::get('get_single_zone/{zone}', [ZoneManagementController::class, 'getZoneDetails']);
        Route::post('create_zone', [ZoneManagementController::class, 'createZone']);
        Route::put('update_zone/{zone}', [ZoneManagementController::class, 'updateZone']);
        Route::delete('delete_zone/{zone}', [ZoneManagementController::class, 'deleteZone']);
    });
});
