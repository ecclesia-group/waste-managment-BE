<?php

use App\Http\Controllers\ActorRelatedDataController;
use App\Http\Controllers\Admin\AdminAuthenticationController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminPasswordController;
use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\Client\ClientAuthenticationController;
use App\Http\Controllers\Client\ClientController;
use App\Http\Controllers\Client\ClientPasswordController;
use App\Http\Controllers\Complaint\ComplaintmanagementController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\DistrictAssembley\DistrictAssembleyAuthenticationController;
use App\Http\Controllers\DistrictAssembley\DistrictAssembleyPasswordController;
use App\Http\Controllers\DistrictAssembley\DistrictAssemblyController;
use App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController;
use App\Http\Controllers\Driver\DriverController;
use App\Http\Controllers\Facility\FacilityAuthenticationController;
use App\Http\Controllers\Facility\FacilityController;
use App\Http\Controllers\Facility\FacilityPasswordController;
use App\Http\Controllers\Feedback\FeedbackController;
use App\Http\Controllers\Fleet\FleetManagementController;
use App\Http\Controllers\Group\GroupController;
use App\Http\Controllers\Handover\WasteHandoverController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Payment\CalPayCallbackController;
use App\Http\Controllers\Payment\CalPayPaymentController;
use App\Http\Controllers\Payment\ClientPaymentController;
use App\Http\Controllers\Payment\ProviderPaymentController;
use App\Http\Controllers\Pickup\PickupController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Provider\ProviderAuthenticationController;
use App\Http\Controllers\Provider\ProviderController;
use App\Http\Controllers\Provider\ProviderPasswordController;
use App\Http\Controllers\Purchase\PurchaseController;
use App\Http\Controllers\Reports\ReportsController;
use App\Http\Controllers\RoutePlanner\RoutePlannerManagement;
use App\Http\Controllers\Teams\RoleController;
use App\Http\Controllers\Teams\TeamMemberController;
use App\Http\Controllers\Violation\ViolationManagementController;
use App\Http\Controllers\WeighBridge\WeighBridgeController;
use App\Http\Controllers\ZoneManagementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get("yes", function () {
    return "yes yes";
});

/** CalPay server-to-server callback (no auth). */
Route::post('payment_callback', [CalPayCallbackController::class, 'handle']);

/** Registration fee checkout before client login. */
Route::post('payments/calpay/initiate-registration', [CalPayPaymentController::class, 'initiateRegistration']);

Route::prefix("client")->group(function () {
    Route::post("login", [ClientAuthenticationController::class, "login"]);
    Route::post("reset_password_notification", [ClientPasswordController::class, "sendResetPasswordNotification"]);
    Route::post("reset_password", [ClientPasswordController::class, "resetPassword"]);
    Route::post("resend_verificationCode", [ClientPasswordController::class, "sendResetPasswordNotification"]);

    Route::middleware(["auth:client"])->group(function () {
        Route::post("change_password", [ClientPasswordController::class, "changePassword"]);
        Route::put("update_profile/{client}", [ClientController::class, "updateClientProfile"]);
        Route::post("logout", [ClientAuthenticationController::class, "logout"]);

        // Bulk Waste Request Management
        Route::post('create_bulk_waste_request', [PickupController::class, 'bulkWasteRequest']);
        Route::get('bulk_waste_requests', [PickupController::class, 'clientBulkWasteRequests']);
        Route::get('bulk_waste_requests/{requestCode}', [PickupController::class, 'clientBulkWasteRequestShow']);
        Route::put('update_bulk_waste_request/{requestCode}', [PickupController::class, 'updateBulkWasteRequest']);
        Route::delete('delete_bulk_waste_request/{requestCode}', [PickupController::class, 'deleteBulkWasteRequest']);
        Route::post('bulk_waste_requests/{requestCode}/pay', [PickupController::class, 'payBulkWasteRequest']);

        Route::post("payments/registration", [ClientPaymentController::class, "createRegistrationPayment"]);
        Route::get("payments/registration/status", [ClientPaymentController::class, "registrationPaymentStatus"]);
        Route::post('payments/calpay/initiate', [CalPayPaymentController::class, 'initiate']);
        Route::get('payments/calpay/status', [CalPayPaymentController::class, 'status']);

        // Complaint Management
        Route::get('get_complaints', [ComplaintmanagementController::class, 'listComplaints']);
        Route::get('get_single_complaint/{complaint}', [ComplaintmanagementController::class, 'getComplaintDetails']);

        // Report Management
        Route::get('get_feedbacks', [FeedbackController::class, 'listFeedbacks']);
        Route::post('create_feedback', [FeedbackController::class, 'createFeedback']);
        Route::get('get_single_feedback/{feedback}', [FeedbackController::class, 'getFeedbackDetails']);
        Route::delete('delete_feedback/{feedback}', [FeedbackController::class, 'deleteFeedback']);
        Route::put('update_feedback/{feedback}', [FeedbackController::class, 'updateFeedback']);

        // Violation Management
        Route::get('get_violations', [ViolationManagementController::class, 'listViolations']);
        Route::get('get_single_violation/{violation}', [ViolationManagementController::class, 'getViolationDetails']);
        Route::post('create_violation', [ViolationManagementController::class, 'createClientViolation']);
        Route::put('update_violation/{violation}', [ViolationManagementController::class, 'updateViolation']);
        Route::delete('delete_violation/{violation}', [ViolationManagementController::class, 'deleteViolation']);
        // Clients only view violations (education). Providers record them during pickup.

        // Product Management (View products for purchase)
        Route::get('get_products', [ProductController::class, 'listProducts']);
        Route::get('cart', [CartController::class, 'getCart']);
        Route::post('cart/add_item', [CartController::class, 'addItem']);
        Route::put('cart/update_item/{product_slug}', [CartController::class, 'updateItem']);
        Route::delete('cart/remove_item/{product_slug}', [CartController::class, 'removeItem']);
        Route::post('cart/checkout', [CartController::class, 'checkout']);
        Route::get('get_single_product/{product}', [ProductController::class, 'getProductDetails']);

        // Purchase Management
        Route::post('create_purchase', [PurchaseController::class, 'createPurchase']);
        Route::get('get_purchases', [PurchaseController::class, 'listPurchases']);
        Route::get('get_single_purchase/{purchase}', [PurchaseController::class, 'getPurchaseDetails']);
        Route::post('process_payment/{purchase}', [PurchaseController::class, 'processPayment']);
        Route::get('get_payment_history', [PurchaseController::class, 'getPaymentHistory']);

        Route::get('get_client_pickups', [PickupController::class, 'getClientPickups']);
        Route::get('get_single_pickup/{pickupCode}', [PickupController::class, 'getSinglePickup']);
        Route::get('get_pickup_dates', [PickupController::class, 'getPickupDates']);

        // Notification Management
        Route::get('get_all_notifications', [NotificationController::class, 'getAllClientNotifications']);
    });
});

Route::prefix("provider")->group(function () {
    Route::post("login", [ProviderAuthenticationController::class, "login"]);
    Route::post("reset_password_notification", [ProviderPasswordController::class, "sendResetPasswordNotification"]);
    Route::post("reset_password", [ProviderPasswordController::class, "resetPassword"]);
    Route::post("resend_verificationCode", [ProviderPasswordController::class, "sendVerificationNotification"]);
    Route::post("verify_account", [ProviderPasswordController::class, "verifyAccount"]);

    Route::middleware(["auth:provider"])->group(function () {
        Route::post("change_password", [ProviderPasswordController::class, "changePassword"]);
        Route::put("update_profile/{provider}", [ProviderController::class, "updateProfile"]);
        Route::post("logout", [ProviderAuthenticationController::class, "logout"]);

        // Provider reports/analytics
        Route::get("reports", [ReportsController::class, "providerReports"]);

        Route::get("dashboard", [DashboardController::class, "providerDashboard"]);
        Route::get("map_pickup_overview", [DashboardController::class, "mapPickupOverview"]);

        // Provider payment management
        Route::get("payments", [ProviderPaymentController::class, "listPayments"]);
        Route::get("get_single_payment/{payment}", [ProviderPaymentController::class, "getPayment"]);
        Route::get("payments/bins", [ProviderPaymentController::class, "binsPayments"]);
        Route::get("payments/waste_handover_request", [ProviderPaymentController::class, "wasteHandoverRequestPayments"]);
        Route::get("payments/weighbridge_records", [ProviderPaymentController::class, "weighbridgeRecords"]);
        Route::post('payments/calpay/initiate', [CalPayPaymentController::class, 'initiate']);
        Route::get('payments/calpay/status', [CalPayPaymentController::class, 'status']);

        // Store/Product Management (provider-owned)
        Route::post('create_product', [ProductController::class, 'createProduct']);
        Route::get('all_products', [ProductController::class, 'listProducts']);
        Route::get('get_single_product/{product}', [ProductController::class, 'getProductDetails']);
        Route::put('update_product/{product}', [ProductController::class, 'updateProduct']);
        Route::delete('delete_product/{product}', [ProductController::class, 'deleteProduct']);

        // Clients Management
        Route::post("register_client", [ClientController::class, "register"]);
        Route::get("all_clients", [ClientController::class, "allClients"]);
        Route::get("get_single_client/{client}", [ClientController::class, "show"]);
        Route::get("clients/{client}/pickups", [ActorRelatedDataController::class, "clientPickups"]);
        Route::get("clients/{client}/violations", [ActorRelatedDataController::class, "clientViolations"]);
        Route::get("clients/{client}/payments", [ActorRelatedDataController::class, "clientPayments"]);
        Route::post("update_client_status", [ClientController::class, "updateStatus"]);
        Route::put("update_client_details/{client}", [ClientController::class, "updateClientProfile"]);
        Route::delete("delete_client/{client}", [ClientController::class, "deleteClient"]);

        // Groups Management
        Route::post("add_group", [GroupController::class, "register"]);
        Route::get("all_groups", [GroupController::class, "allGroups"]);
        Route::get("get_single_group/{group}", [GroupController::class, "show"]);
        Route::post("update_group_status", [GroupController::class, "updateGroupStatus"]);
        Route::put("update_group_details/{group}", [GroupController::class, "updateGroup"]);
        Route::delete("delete_group/{group}", [GroupController::class, "deleteGroup"]);

        // Drivers Management
        Route::post("register_driver", [DriverController::class, "register"]);
        Route::get("all_drivers", [DriverController::class, "allDrivers"]);
        Route::get("get_single_driver/{driver}", [DriverController::class, "show"]);
        Route::post("update_driver_status", [DriverController::class, "updateStatus"]);
        Route::put("update_driver_details/{driver}", [DriverController::class, "updateDriverProfile"]);
        Route::delete("delete_driver/{driver}", [DriverController::class, "deleteDriver"]);
        Route::post("update_driver_location", [DriverController::class, "updateLocation"]);

        // Route Planner
        Route::get("plan_options", [RoutePlannerManagement::class, "planOptions"]);
        Route::post("create_plan", [RoutePlannerManagement::class, "register"]);
        Route::get("all_plans", [RoutePlannerManagement::class, "allPlans"]);
        Route::get("get_single_plan/{plan}", [RoutePlannerManagement::class, "show"]);
        Route::get("get_single_plan/{plan}/pickups", [RoutePlannerManagement::class, "planPickups"]);
        Route::get("clients/{client}/pickup_details", [RoutePlannerManagement::class, "clientPickupDetails"]);
        Route::get("assignment_logs", [RoutePlannerManagement::class, "assignmentLogs"]);
        Route::post("update_plan_status", [RoutePlannerManagement::class, "updateStatus"]);
        Route::put("update_plan_details/{plan}", [RoutePlannerManagement::class, "updatePlan"]);
        Route::delete("delete_plan/{plan}", [RoutePlannerManagement::class, "deletePlan"]);

        // Fleet Management
        Route::post("register_fleet", [FleetManagementController::class, "register"]);
        Route::get("all_fleets", [FleetManagementController::class, "allFleets"]);
        Route::get("get_single_fleet/{fleet}", [FleetManagementController::class, "show"]);
        Route::post("update_fleet_status", [FleetManagementController::class, "updateStatus"]);
        Route::put("update_fleet_details/{fleet}", [FleetManagementController::class, "updateFleet"]);
        Route::delete("delete_fleet/{fleet}", [FleetManagementController::class, "deleteFleet"]);

        // Complaint Management
        // Providers can file complaints to platform support from the provider dashboard.
        Route::post("create_complaint", [ComplaintmanagementController::class, "createComplaint"]);
        Route::get("all_complaints", [ComplaintmanagementController::class, "listClientComplaints"]);
        Route::get("get_single_complaint/{complaint}", [ComplaintmanagementController::class, "getComplaintDetails"]);
        Route::put("update_complaint_status/{complaint}", [ComplaintmanagementController::class, "updateComplaintStatus"]);

        // Violation Management
        Route::get("all_violations", [ViolationManagementController::class, "listClientViolations"]);
        Route::get("get_single_violation/{violation}", [ViolationManagementController::class, "getViolationDetails"]);
        Route::post("create_violation", [ViolationManagementController::class, "createViolation"]);
        Route::put("update_violation/{violation}", [ViolationManagementController::class, "providerUpdateViolation"]);
        Route::delete("delete_violation/{violation}", [ViolationManagementController::class, "providerDeleteViolation"]);
        Route::put("update_violation_status/{violation}", [ViolationManagementController::class, "updateViolationStatus"]);

        // QR Code Scanner (Scan bin QR code to get client details)
        Route::post("scan_qrcode", [ClientController::class, "scanQRCode"]);
        Route::post("manual_bin_code_scan", [PickupController::class, "manualCodeScan"]);
        Route::post("change_scan_status", [PickupController::class, "setScanStatus"]);

        // Bulk waste requests (provider review + route planner selection)
        Route::get("bulk_waste_requests", [PickupController::class, "providerBulkWasteRequests"]);
        Route::get("bulk_waste_requests/{requestCode}", [PickupController::class, "providerBulkWasteRequestShow"]);
        Route::put("bulk_waste_requests/{requestCode}/status", [PickupController::class, "updateBulkWasteRequestStatus"]);
        Route::put("bulk_waste_requests/{requestCode}/price", [PickupController::class, "setBulkWasteRequestPrice"]);

        // Waste Handover Requests
        Route::post("handover_requests", [WasteHandoverController::class, "create"]);
        Route::get("handover_requests", [WasteHandoverController::class, "list"]);
        Route::get("handover_requests/available", [WasteHandoverController::class, "availableInZone"]);
        Route::get("handover_requests/drivers/{driverSlug}/fleets", [WasteHandoverController::class, "fleetsForDriver"]);
        Route::get("handover_requests/{handover}", [WasteHandoverController::class, "show"]);
        Route::post("handover_requests/{handover}/accept", [WasteHandoverController::class, "accept"]);
        Route::post("handover_requests/{handover}/decline", [WasteHandoverController::class, "decline"]);
        Route::post("handover_requests/{handover}/confirm_payment", [WasteHandoverController::class, "confirmPayment"]);
        Route::post("handover_requests/{handover}/complete", [WasteHandoverController::class, "complete"]);

        // Team RBAC (provider only)
        Route::get("permissions", [RoleController::class, "permissions"]);
        Route::get("roles", [RoleController::class, "index"]);
        Route::post("roles", [RoleController::class, "store"]);
        Route::put("roles/{roleSlug}", [RoleController::class, "update"]);
        Route::delete("roles/{roleSlug}", [RoleController::class, "destroy"]);

        // Provider team members
        Route::get("team_members", [TeamMemberController::class, "index"]);
        Route::post("team_members", [TeamMemberController::class, "store"]);
        Route::get("team_members/{memberSlug}", [TeamMemberController::class, "show"]);
        Route::put("team_members/{memberSlug}", [TeamMemberController::class, "update"]);
        Route::delete("team_members/{memberSlug}", [TeamMemberController::class, "destroy"]);
        Route::put("team_members/{memberSlug}/status", [TeamMemberController::class, "updateStatus"]);

        // Weighbridge Records Management
        Route::post("weighbridge_records", [WeighBridgeController::class, "createRecord"]);
        Route::get("weighbridge_records", [WeighBridgeController::class, "allRecords"]);
        Route::get("get_single_weighbridge_record/{record}", [WeighBridgeController::class, "showRecord"]);
        Route::post("update_weighbridge_record_status", [WeighBridgeController::class, "updateRecordStatus"]);
        Route::put("update_weighbridge_record_details/{record}", [WeighBridgeController::class, "updateRecord"]);
        Route::delete("delete_weighbridge_record/{record}", [WeighBridgeController::class, "deleteRecord"]);
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

        // Facility reports/analytics
        Route::get("reports", [ReportsController::class, "facilityReports"]);

        // all zones
        Route::get("all_zones", [ZoneManagementController::class, "listZones"]);

        // all providers
        Route::get("all_providers", [ProviderController::class, "allProviders"]);

        // all fleets
        Route::get("all_fleets", [FleetManagementController::class, "getAllFleets"]);

        // Weigh Bridge Management
        Route::post("register_weigh_bridge_entry", [WeighBridgeController::class, "registerEntry"]);
        Route::post("verify_weigh_bridge_ticket", [WeighBridgeController::class, "verifyByTicketCode"]);
        Route::get("all_weigh_bridge_entries", [WeighBridgeController::class, "allEntries"]);
        Route::get("get_single_weigh_bridge_entry/{entry}", [WeighBridgeController::class, "show"]);
        Route::post("update_weigh_bridge_entry_status", [WeighBridgeController::class, "updateStatus"]);
        Route::put("update_weigh_bridge_entry_details/{entry}", [WeighBridgeController::class, "updateEntry"]);
        Route::delete("delete_weigh_bridge_entry/{entry}", [WeighBridgeController::class, "deleteEntry"]);
        Route::get("dashboard", [DashboardController::class, "facilityDashboard"]);

        // Team RBAC (facility only)
        Route::get("permissions", [RoleController::class, "permissions"]);
        Route::get("roles", [RoleController::class, "index"]);
        Route::post("roles", [RoleController::class, "store"]);
        Route::put("roles/{roleSlug}", [RoleController::class, "update"]);
        Route::delete("roles/{roleSlug}", [RoleController::class, "destroy"]);

        // Facility team members
        Route::get("team_members", [TeamMemberController::class, "index"]);
        Route::post("team_members", [TeamMemberController::class, "store"]);
        Route::get("team_members/{memberSlug}", [TeamMemberController::class, "show"]);
        Route::put("team_members/{memberSlug}", [TeamMemberController::class, "update"]);
        Route::delete("team_members/{memberSlug}", [TeamMemberController::class, "destroy"]);
        Route::put("team_members/{memberSlug}/status", [TeamMemberController::class, "updateStatus"]);
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

        // District Assembly reports/analytics (MMDA dashboard data)
        Route::get("reports", [ReportsController::class, "districtAssemblyReports"]);

        // District Assembly assignment logs + plan details for map/dashboard
        Route::get("assignment_logs", [RoutePlannerManagement::class, "assignmentLogs"]);
        Route::get("get_single_plan/{plan}", [RoutePlannerManagement::class, "show"]);

        Route::get("dashboard", [DashboardController::class, "districtAssemblyDashboard"]);
        Route::get("map_pickup_overview", [DashboardController::class, "mapPickupOverview"]);

        // District Assembly management (providers, facilities, zones, complaints)
        Route::get("providers", [DistrictAssemblyManagementController::class, "listProviders"]);
        Route::get("get_single_provider/{provider}", [DistrictAssemblyManagementController::class, "getProvider"]);

        Route::get("facilities", [DistrictAssemblyManagementController::class, "listFacilities"]);
        Route::get("get_single_facility/{facility}", [DistrictAssemblyManagementController::class, "getFacility"]);

        Route::get("zones", [DistrictAssemblyManagementController::class, "listZones"]);
        Route::get("providers/{provider}/zones", [DistrictAssemblyManagementController::class, "listProviderZones"]);
        Route::post("providers/{provider}/zones", [DistrictAssemblyManagementController::class, "assignProviderZones"]);
        Route::delete("providers/{provider}/zones/{zone}", [DistrictAssemblyManagementController::class, "revokeProviderZone"]);
        Route::get("facilities/{facility}/zones", [DistrictAssemblyManagementController::class, "listFacilityZones"]);
        Route::post("facilities/{facility}/zones", [DistrictAssemblyManagementController::class, "assignFacilityZones"]);
        Route::delete("facilities/{facility}/zones/{zone}", [DistrictAssemblyManagementController::class, "revokeFacilityZone"]);

        Route::get("complaints", [DistrictAssemblyManagementController::class, "listComplaints"]);
        Route::get("get_single_complaint/{complaint}", [DistrictAssemblyManagementController::class, "getComplaint"]);
        Route::put("update_complaint_status/{complaint}", [DistrictAssemblyManagementController::class, "updateComplaintStatus"]);

        Route::post("register_provider", [DistrictAssemblyManagementController::class, "registerProvider"]);
        Route::post("register_facility", [DistrictAssemblyManagementController::class, "registerFacility"]);

        Route::put("update_provider_status/{provider}", [DistrictAssemblyManagementController::class, "updateProviderStatus"]);
        Route::put("update_facility_status/{facility}", [DistrictAssemblyManagementController::class, "updateFacilityStatus"]);

        // Team RBAC (district assembly only)
        Route::get("permissions", [RoleController::class, "permissions"]);
        Route::get("roles", [RoleController::class, "index"]);
        Route::post("roles", [RoleController::class, "store"]);
        Route::put("roles/{roleSlug}", [RoleController::class, "update"]);
        Route::delete("roles/{roleSlug}", [RoleController::class, "destroy"]);

        // District Assembly/MMDA team members
        Route::get("team_members", [TeamMemberController::class, "index"]);
        Route::post("team_members", [TeamMemberController::class, "store"]);
        Route::get("team_members/{memberSlug}", [TeamMemberController::class, "show"]);
        Route::put("team_members/{memberSlug}", [TeamMemberController::class, "update"]);
        Route::delete("team_members/{memberSlug}", [TeamMemberController::class, "destroy"]);
        Route::put("team_members/{memberSlug}/status", [TeamMemberController::class, "updateStatus"]);
    });
});

Route::prefix("admin")->group(function () {
    Route::post("login", [AdminAuthenticationController::class, "login"]);
    Route::post("reset_password_notification", [AdminPasswordController::class, "sendResetPasswordNotification"]);
    Route::post("reset_password", [AdminPasswordController::class, "resetPassword"]);
    Route::post("resend_verificationCode", [AdminPasswordController::class, "sendResetPasswordNotification"]);
    Route::post("register_admin", [AdminController::class, "register"]);

    Route::middleware(["auth:admin", "verified"])->group(function () {
        Route::post("logout", [AdminAuthenticationController::class, "logout"]);
        Route::post("change_password", [AdminPasswordController::class, "changePassword"]);

        // Admin reports/analytics (Super Admin)
        Route::get("reports", [ReportsController::class, "adminReports"]);
        Route::get("map_pickup_overview", [DashboardController::class, "mapPickupOverview"]);

        // Provider Management
        Route::post("register_provider", [ProviderController::class, "register"]);
        Route::get("all_providers", [ProviderController::class, "index"]);
        Route::get("get_single_provider/{provider}", [ProviderController::class, "show"]);
        Route::post("update_provider_status", [ProviderController::class, "updateStatus"]);
        Route::put("update_provider_details/{provider}", [ProviderController::class, "updateProviderProfile"]);
        Route::delete("delete_provider/{provider}", [ProviderController::class, "deleteProvider"]);
        Route::get("providers/{provider}/clients", [ActorRelatedDataController::class, "providerClients"]);
        Route::get("providers/{provider}/clients/{client}", [ActorRelatedDataController::class, "providerClient"]);
        // get provider drivers and single driver
        Route::get("providers/{provider}/drivers", [ActorRelatedDataController::class, "providerDrivers"]);
        Route::get("providers/{provider}/drivers/{driver}", [ActorRelatedDataController::class, "providerDriver"]);
        // get provider fleets and single fleet
        Route::get("providers/{provider}/fleets", [ActorRelatedDataController::class, "providerFleets"]);
        Route::get("providers/{provider}/fleets/{fleet}", [ActorRelatedDataController::class, "providerFleet"]);
        // get provider weighbridge records and single weighbridge record
        Route::get("providers/{provider}/weighbridge_records", [ActorRelatedDataController::class, "providerWeighbridgeRecords"]);
        Route::get("providers/{provider}/weighbridge_records/{weighbridge}", [ActorRelatedDataController::class, "providerWeighbridgeRecord"]);
        // Get provider groups and single group
        Route::get("providers/{provider}/groups", [ActorRelatedDataController::class, "providerGroups"]);
        Route::get("providers/{provider}/groups/{group}", [ActorRelatedDataController::class, "providerGroup"]);
        Route::get("providers/{provider}/violations", [ActorRelatedDataController::class, "providerViolations"]);
        Route::get("providers/{provider}/violations/{violation}", [ActorRelatedDataController::class, "providerViolation"]);
        Route::get("providers/{provider}/payments", [ActorRelatedDataController::class, "providerPayments"]);
        Route::get("providers/{provider}/payments/{transaction_id}", [ActorRelatedDataController::class, "providerPayment"]);
        // reassing zones to a provider
        Route::post("providers/{provider}/reassign_zones", [ProviderController::class, "reassignZones"]);
        // reassign mmda to a provider
        Route::post("providers/{provider}/reassign_mmda", [ProviderController::class, "reassignMmda"]);
        // get all routerplanner records for a provider
        Route::get("providers/{provider}/routerplanner_records", [RoutePlannerManagement::class, "routerplannerRecords"]);
        Route::get("providers/{provider}/routerplanner_records/{routerplanner}", [RoutePlannerManagement::class, "routerplannerRecord"]);
        // get all pickup for a single routeplanner record
        Route::get("providers/{provider}/routerplanner_records/{routerplanner}/pickups", [RoutePlannerManagement::class, "routerplannerPickups"]);


        // Facility Management
        Route::post("register_facility", [FacilityController::class, "register"]);
        Route::get("all_facilities", [FacilityController::class, "index"]);
        Route::get("get_single_facility/{facility}", [FacilityController::class, "show"]);
        Route::get("facilities/{facility}/weighbridge_records", [ActorRelatedDataController::class, "facilityWeighbridgeRecords"]);
        Route::post("update_facility_status", [FacilityController::class, "updateStatus"]);
        Route::put("update_facility_details/{facility}", [FacilityController::class, "updateFacilityProfile"]);
        Route::delete("delete_facility/{facility}", [FacilityController::class, "deleteFacility"]);

        // District Assembly Management
        Route::post("register_district_assembly", [DistrictAssemblyController::class, "register"]);
        Route::get("all_district_assemblies", [DistrictAssemblyController::class, "index"]);
        Route::get("get_single_district_assembly/{district_assembly}", [DistrictAssemblyController::class, "show"]);
        Route::post("update_district_assembly_status", [DistrictAssemblyController::class, "updateStatus"]);
        Route::put("update_district_assembly_details/{district_assembly}", [DistrictAssemblyController::class, "updateDistrictAssemblyProfile"]);
        Route::get("district_assemblies/{district_assembly}/providers", [ActorRelatedDataController::class, "districtAssemblyProviders"]);
        Route::get("district_assemblies/{district_assembly}/facilities", [ActorRelatedDataController::class, "districtAssemblyFacilities"]);
        Route::delete("delete_district_assembly/{district_assembly}", [DistrictAssemblyController::class, "deleteDistrictAssembly"]);
        // Get zones for a district assembly
        Route::get("district_assemblies/{district_assembly}/zones", [ActorRelatedDataController::class, "districtAssemblyZones"]);

        // Zone Management
        Route::get('all_zones', [ZoneManagementController::class, 'listZones']);
        Route::get('get_single_zone/{zone}', [ZoneManagementController::class, 'zoneOverview']);
        Route::get('zones/{zone}/providers', [ActorRelatedDataController::class, 'zoneProviders']);
        Route::get('zones/{zone}/facilities', [ActorRelatedDataController::class, 'zoneFacilities']);
        Route::get('zones/{zone}/clients', [ActorRelatedDataController::class, 'zoneClients']);
        Route::get('zones/{zone}/pickups', [ActorRelatedDataController::class, 'zonePickups']);
        Route::post('create_zone', [ZoneManagementController::class, 'createZone']);
        Route::put('update_zone/{zone}', [ZoneManagementController::class, 'updateZone']);
        Route::post('update_zone_status', [ZoneManagementController::class, 'updateZoneStatus']);
        Route::delete('delete_zone/{zone}', [ZoneManagementController::class, 'deleteZone']);

        // Provider <-> Zone assignments (multi-zone support) (Super Admin)
        Route::get('providers/{provider}/zones', [ZoneManagementController::class, 'listProviderZones']);
        Route::post('providers/{provider}/zones', [ZoneManagementController::class, 'assignProviderZones']);
        Route::delete('providers/{provider}/zones/{zone}', [ZoneManagementController::class, 'revokeProviderZone']);
        Route::get('facilities/{facility}/zones', [ZoneManagementController::class, 'listFacilityZones']);
        Route::post('facilities/{facility}/zones', [ZoneManagementController::class, 'assignFacilityZones']);
        Route::delete('facilities/{facility}/zones/{zone}', [ZoneManagementController::class, 'revokeFacilityZone']);

        // Complaint Management
        Route::get('all_complaints', [ComplaintmanagementController::class, 'listComplaints']);
        Route::get('get_single_complaint/{complaint}', [ComplaintmanagementController::class, 'getComplaintDetails']);
        Route::put('update_complaint_status/{complaint}', [ComplaintmanagementController::class, 'updateComplaintStatus']);

        // Violation Management
        Route::get('all_violations', [ViolationManagementController::class, 'listViolations']);
        Route::get('get_single_violation/{violation}', [ViolationManagementController::class, 'getViolationDetails']);
        Route::put('update_violation_status/{violation}', [ViolationManagementController::class, 'updateViolationStatus']);

        // Statictics Management
        Route::get('actors_statistics', [AdminController::class, 'getStatisticsOverview']);

        // Store order management
        Route::put('update_purchase_status/{purchase}', [PurchaseController::class, 'updatePurchaseStatus']);

        // Assignment logs (super admin map/dashboard filtering)
        Route::get('assignment_logs', [RoutePlannerManagement::class, 'assignmentLogs']);
        Route::get('get_single_plan/{plan}', [RoutePlannerManagement::class, 'show']);

        // Team RBAC (admin only)
        Route::get("permissions", [RoleController::class, "permissions"]);
        Route::get("roles", [RoleController::class, "index"]);
        Route::post("roles", [RoleController::class, "store"]);
        Route::put("roles/{roleSlug}", [RoleController::class, "update"]);
        Route::delete("roles/{roleSlug}", [RoleController::class, "destroy"]);

        // Admin team members
        Route::get("team_members", [TeamMemberController::class, "index"]);
        Route::post("team_members", [TeamMemberController::class, "store"]);
        Route::get("team_members/{memberSlug}", [TeamMemberController::class, "show"]);
        Route::put("team_members/{memberSlug}", [TeamMemberController::class, "update"]);
        Route::delete("team_members/{memberSlug}", [TeamMemberController::class, "destroy"]);
        Route::put("team_members/{memberSlug}/status", [TeamMemberController::class, "updateStatus"]);
    });
});
