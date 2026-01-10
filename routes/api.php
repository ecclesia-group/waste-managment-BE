<?php

use App\Http\Controllers\Admin\AdminAuthenticationController;
use App\Http\Controllers\Admin\AdminPasswordController;
use App\Http\Controllers\Client\ClientAuthenticationController;
use App\Http\Controllers\Client\ClientController;
use App\Http\Controllers\Client\ClientPasswordController;
use App\Http\Controllers\Complaint\ComplaintmanagementController;
use App\Http\Controllers\DistrictAssembley\DistrictAssembleyAuthenticationController;
use App\Http\Controllers\DistrictAssembley\DistrictAssembleyPasswordController;
use App\Http\Controllers\DistrictAssembley\DistrictAssemblyController;
use App\Http\Controllers\Driver\DriverController;
use App\Http\Controllers\Facility\FacilityAuthenticationController;
use App\Http\Controllers\Facility\FacilityController;
use App\Http\Controllers\Facility\FacilityPasswordController;
use App\Http\Controllers\Feedback\FeedbackController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Pickup\PickupController;
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

Route::prefix("client")->group(function () {
    Route::post("login", [ClientAuthenticationController::class, "login"]);
    Route::post("reset_password_notification", [ClientPasswordController::class, "sendResetPasswordNotification"]);
    Route::post("reset_password", [ClientPasswordController::class, "resetPassword"]);
    Route::post("resend_verificationCode", [ClientPasswordController::class, "sendResetPasswordNotification"]);

    Route::middleware(["auth:client"])->group(function () {
        Route::post("change_password", [ClientPasswordController::class, "changePassword"]);
        Route::put("update_profile/{client}", [ClientController::class, "updateClientProfile"]);
        Route::post("logout", [ClientAuthenticationController::class, "logout"]);

        // Complaint Management
        Route::post('create_complaint', [ComplaintmanagementController::class, 'createComplaint']);
        Route::get('get_complaints', [ComplaintmanagementController::class, 'listComplaints']);
        Route::get('get_single_complaint/{complaint}', [ComplaintmanagementController::class, 'getComplaintDetails']);

        // Report Management
        Route::post('create_feedback', [FeedbackController::class, 'createFeedback']);

        // Pickup Management
        Route::post('create_bulk_waste_request', [PickupController::class, 'bulkWasteRequest']);
        Route::get('get_client_pickups', [PickupController::class, 'getClientPickups']);
        Route::get('get_single_pickup/{pickup}', [PickupController::class, 'getSinglePickup']);
        Route::get('get_pickup_dates', [PickupController::class, 'getPickupDates']);

        // Notification Management
        Route::get('get_all_notifications', [NotificationController::class, 'getAllNotifications']);
        Route::get('get_single_pickup/{pickup}', [PickupController::class, 'getSinglePickup']);
        Route::get('get_pickup_dates', [PickupController::class, 'getPickupDates']);
    });
});

Route::prefix("provider")->group(function () {
    Route::post("login", [ProviderAuthenticationController::class, "login"]);
    Route::post("reset_password_notification", [ProviderPasswordController::class, "sendResetPasswordNotification"]);
    Route::post("reset_password", [ProviderPasswordController::class, "resetPassword"]);
    Route::post("resend_verificationCode", [ProviderPasswordController::class, "sendResetPasswordNotification"]);

    Route::middleware(["auth:provider"])->group(function () {
        Route::post("change_password", [ProviderPasswordController::class, "changePassword"]);
        Route::put("update_profile/{provider}", [ProviderController::class, "updateProviderProfile"]);
        Route::post("logout", [ProviderAuthenticationController::class, "logout"]);

        // Clients Management
        Route::post("register_client", [ClientController::class, "register"]);
        Route::get("all_clients", [ClientController::class, "allClients"]);
        Route::get("get_single_client/{client}", [ClientController::class, "show"]);
        Route::post("update_client_status", [ClientController::class, "updateStatus"]);
        Route::put("update_client_details/{client}", [ClientController::class, "updateClientProfile"]);

        // Drivers Management
        Route::post("register_driver", [DriverController::class, "register"]);
        Route::get("all_drivers", [DriverController::class, "allDrivers"]);
        Route::get("get_single_driver/{driver}", [DriverController::class, "show"]);
        Route::post("update_driver_status", [DriverController::class, "updateStatus"]);
        Route::put("update_driver_details/{driver}", [DriverController::class, "updateDriverProfile"]);

        // Pickup Management
        Route::post("provider_pickup_creation", [PickupController::class, "providerPickupCreation"]);
        Route::get("get_all_client_pickups", [PickupController::class, "getAllPickups"]);
        Route::get("get_single_client_pickup/{pickup}", [PickupController::class, "getSinglePickup"]);
        Route::post("set_pickup_price", [PickupController::class, "setPickupPrice"]);
        Route::post("set_pickup_date", [PickupController::class, "setPickupDate"]);

        // Complaint Management
        Route::get("all_complaints", [ComplaintmanagementController::class, "listComplaints"]);
    });
});

Route::prefix("facility")->group(function () {
    Route::post("login", [FacilityAuthenticationController::class, "login"]);
    Route::post("reset_password_notification", [FacilityPasswordController::class, "sendResetPasswordNotification"]);
    Route::post("reset_password", [FacilityPasswordController::class, "resetPassword"]);
    Route::post("resend_verificationCode", [FacilityPasswordController::class, "sendResetPasswordNotification"]);

    Route::middleware(["auth:facility"])->group(function () {
        Route::post("change_password", [FacilityPasswordController::class, "changePassword"]);
        Route::post("logout", [FacilityAuthenticationController::class, "logout"]);
    });
});

Route::prefix("district_assembly")->group(function () {
    Route::post("login", [DistrictAssembleyAuthenticationController::class, "login"]);
    Route::post("reset_password_notification", [DistrictAssembleyPasswordController::class, "sendResetPasswordNotification"]);
    Route::post("reset_password", [DistrictAssembleyPasswordController::class, "resetPassword"]);
    Route::post("resend_verificationCode", [DistrictAssembleyPasswordController::class, "sendResetPasswordNotification"]);

    Route::middleware(["auth:district_assembly"])->group(function () {
        Route::post("change_password", [DistrictAssembleyPasswordController::class, "changePassword"]);
        Route::post("logout", [DistrictAssembleyAuthenticationController::class, "logout"]);
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
        Route::delete("delete_provider/{provider}", [ProviderController::class, "deleteProvider"]);

        // Facility Management
        Route::post("register_facility", [FacilityController::class, "register"]);
        Route::get("all_facilities", [FacilityController::class, "index"]);
        Route::get("get_single_facility/{facility}", [FacilityController::class, "show"]);
        Route::post("update_facility_status", [FacilityController::class, "updateStatus"]);
        Route::put("update_facility_details/{facility}", [FacilityController::class, "updateFacilityProfile"]);
        Route::delete("delete_facility/{facility}", [FacilityController::class, "deleteFacility"]);
        
        // District Assembly Management
        Route::post("register_district_assembly", [DistrictAssemblyController::class, "register"]);
        Route::get("all_district_assemblies", [DistrictAssemblyController::class, "index"]);
        Route::get("get_single_district_assembly/{district_assembly}", [DistrictAssemblyController::class, "show"]);
        Route::post("update_district_assembly_status", [DistrictAssemblyController::class, "updateStatus"]);
        Route::put("update_district_assembly_details/{district_assembly}", [DistrictAssemblyController::class, "updateDistrictAssemblyProfile"]);
        Route::delete("delete_district_assembly/{district_assembly}", [DistrictAssemblyController::class, "deleteDistrictAssembly"]);

        // Zone Management
        Route::get('all_zones', [ZoneManagementController::class, 'listZones']);
        Route::get('get_single_zone/{zone}', [ZoneManagementController::class, 'getZoneDetails']);
        Route::post('create_zone', [ZoneManagementController::class, 'createZone']);
        Route::put('update_zone/{zone}', [ZoneManagementController::class, 'updateZone']);
        Route::post('update_zone_status', [ZoneManagementController::class, 'updateZoneStatus']);
        Route::delete('delete_zone/{zone}', [ZoneManagementController::class, 'deleteZone']);

        // Complaint Management
        Route::get('all_complaints', [ComplaintmanagementController::class, 'listComplaints']);
        Route::get('get_single_complaint/{complaint}', [ComplaintmanagementController::class, 'getComplaintDetails']);
        Route::put('update_complaint_status/{complaint}', [ComplaintmanagementController::class, 'updateComplaintStatus']);
        // Route::post('create_complaint', [ZoneManagementController::class, 'createComplaint']);
        // Route::delete('delete_complaint/{complaint}', [ZoneManagementController::class, 'deleteComplaint']);
    });
});
