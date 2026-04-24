# Waste Management API Reference

Generated from current backend routes and request validators.

## Standard Response Envelope
All API responses follow this shape:

{"data":{"status_code":"string","message":"Action Successful|Action Failed","in_error":false,"reason":"human readable reason","data":{},"point_in_time":"timestamp"}}

## Client APIs

### `GET|HEAD` `/api/client/banners`
- Controller: `App\Http\Controllers\Content\BannerController@listForAudience`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/bulk_waste_requests`
- Controller: `App\Http\Controllers\Pickup\PickupController@clientBulkWasteRequests`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/bulk_waste_requests/{requestCode}`
- Controller: `App\Http\Controllers\Pickup\PickupController@clientBulkWasteRequestShow`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/cart`
- Controller: `App\Http\Controllers\Cart\CartController@getCart`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/cart/add_item`
- Controller: `App\Http\Controllers\Cart\CartController@addItem`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/cart/checkout`
- Controller: `App\Http\Controllers\Cart\CartController@checkout`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/client/cart/remove_item/{product_slug}`
- Controller: `App\Http\Controllers\Cart\CartController@removeItem`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/client/cart/update_item/{product_slug}`
- Controller: `App\Http\Controllers\Cart\CartController@updateItem`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/change_password`
- Controller: `App\Http\Controllers\Client\ClientPasswordController@changePassword`
- Middleware: `api, auth:client`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/create_bulk_waste_request`
- Controller: `App\Http\Controllers\Pickup\PickupController@bulkWasteRequest`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/create_complaint`
- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@createComplaint`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/create_feedback`
- Controller: `App\Http\Controllers\Feedback\FeedbackController@createFeedback`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/create_purchase`
- Controller: `App\Http\Controllers\Purchase\PurchaseController@createPurchase`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/dashboard`
- Controller: `App\Http\Controllers\Dashboard\DashboardController@clientDashboard`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/client/delete_bulk_waste_request/{requestCode}`
- Controller: `App\Http\Controllers\Pickup\PickupController@deleteBulkWasteRequest`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/client/delete_complaint/{complaint}`
- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@deleteComplaint`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/client/delete_feedback/{feedback}`
- Controller: `App\Http\Controllers\Feedback\FeedbackController@deleteFeedback`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/client/delete_pickup/{pickup}`
- Controller: `App\Http\Controllers\Pickup\PickupController@deletePickup`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_all_notifications`
- Controller: `App\Http\Controllers\Notification\NotificationController@getAllNotifications`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_client_pickups`
- Controller: `App\Http\Controllers\Pickup\PickupController@getClientPickups`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_complaints`
- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@listComplaints`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_completed_pickups`
- Controller: `App\Http\Controllers\Pickup\PickupController@getCompletedPickups`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_feedbacks`
- Controller: `App\Http\Controllers\Feedback\FeedbackController@listFeedbacks`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_payment_history`
- Controller: `App\Http\Controllers\Purchase\PurchaseController@getPaymentHistory`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_pickup_dates`
- Controller: `App\Http\Controllers\Pickup\PickupController@getPickupDates`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_products`
- Controller: `App\Http\Controllers\Product\ProductController@listProducts`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_purchases`
- Controller: `App\Http\Controllers\Purchase\PurchaseController@listPurchases`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_single_complaint/{complaint}`
- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@getComplaintDetails`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_single_feedback/{feedback}`
- Controller: `App\Http\Controllers\Feedback\FeedbackController@getFeedbackDetails`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_single_pickup/{pickup}`
- Controller: `App\Http\Controllers\Pickup\PickupController@getSinglePickup`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_single_product/{product}`
- Controller: `App\Http\Controllers\Product\ProductController@getProductDetails`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_single_purchase/{purchase}`
- Controller: `App\Http\Controllers\Purchase\PurchaseController@getPurchaseDetails`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_single_violation/{violation}`
- Controller: `App\Http\Controllers\Violation\ViolationManagementController@getViolationDetails`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/get_violations`
- Controller: `App\Http\Controllers\Violation\ViolationManagementController@listViolations`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/guides`
- Controller: `App\Http\Controllers\Content\GuideController@listForAudience`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/login`
- Controller: `App\Http\Controllers\Client\ClientAuthenticationController@login`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/logout`
- Controller: `App\Http\Controllers\Client\ClientAuthenticationController@logout`
- Middleware: `api, auth:client`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/payments/registration`
- Controller: `App\Http\Controllers\Payment\ClientPaymentController@createRegistrationPayment`
- Middleware: `api, auth:client`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/client/payments/registration/status`
- Controller: `App\Http\Controllers\Payment\ClientPaymentController@registrationPaymentStatus`
- Middleware: `api, auth:client`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/process_payment/{purchase}`
- Controller: `App\Http\Controllers\Purchase\PurchaseController@processPayment`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/reschedule_pickup`
- Controller: `App\Http\Controllers\Pickup\PickupController@reschedulePickup`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/resend_verificationCode`
- Controller: `App\Http\Controllers\Client\ClientPasswordController@sendResetPasswordNotification`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/reset_password`
- Controller: `App\Http\Controllers\Client\ClientPasswordController@resetPassword`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/reset_password_notification`
- Controller: `App\Http\Controllers\Client\ClientPasswordController@sendResetPasswordNotification`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/client/update_bulk_waste_request/{requestCode}`
- Controller: `App\Http\Controllers\Pickup\PickupController@updateBulkWasteRequest`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/client/update_complaint/{complaint}`
- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@updateComplaint`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/client/update_feedback/{feedback}`
- Controller: `App\Http\Controllers\Feedback\FeedbackController@updateFeedback`
- Middleware: `api, auth:client, client.registration_paid`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/client/update_profile/{client}`
- Controller: `App\Http\Controllers\Client\ClientController@updateClientProfile`
- Middleware: `api, auth:client`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

## Provider APIs

### `POST` `/api/provider/add_group`
- Controller: `App\Http\Controllers\Group\GroupController@register`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/all_clients`
- Controller: `App\Http\Controllers\Client\ClientController@allClients`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/all_complaints`
- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@listClientComplaints`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/all_drivers`
- Controller: `App\Http\Controllers\Driver\DriverController@allDrivers`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/all_fleets`
- Controller: `App\Http\Controllers\Fleet\FleetManagementController@allFleets`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/all_groups`
- Controller: `App\Http\Controllers\Group\GroupController@allGroups`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/all_plans`
- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@allPlans`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/all_products`
- Controller: `App\Http\Controllers\Product\ProductController@listProducts`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/all_violations`
- Controller: `App\Http\Controllers\Violation\ViolationManagementController@listClientViolations`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/assignment_logs`
- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@assignmentLogs`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/banners`
- Controller: `App\Http\Controllers\Content\BannerController@listForAudience`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/bulk_waste_requests`
- Controller: `App\Http\Controllers\Pickup\PickupController@providerBulkWasteRequests`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/bulk_waste_requests/{requestCode}`
- Controller: `App\Http\Controllers\Pickup\PickupController@providerBulkWasteRequestShow`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/bulk_waste_requests/{requestCode}/status`
- Controller: `App\Http\Controllers\Pickup\PickupController@updateBulkWasteRequestStatus`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/change_password`
- Controller: `App\Http\Controllers\Provider\ProviderPasswordController@changePassword`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/change_scan_status`
- Controller: `App\Http\Controllers\Pickup\PickupController@setScanStatus`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/create_complaint`
- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@createComplaint`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/create_plan`
- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@register`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/create_product`
- Controller: `App\Http\Controllers\Product\ProductController@createProduct`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/create_violation`
- Controller: `App\Http\Controllers\Violation\ViolationManagementController@createViolation`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/dashboard`
- Controller: `App\Http\Controllers\Dashboard\DashboardController@providerDashboard`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/provider/delete_client/{client}`
- Controller: `App\Http\Controllers\Client\ClientController@deleteClient`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/provider/delete_driver/{driver}`
- Controller: `App\Http\Controllers\Driver\DriverController@deleteDriver`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/provider/delete_fleet/{fleet}`
- Controller: `App\Http\Controllers\Fleet\FleetManagementController@deleteFleet`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/provider/delete_group/{group}`
- Controller: `App\Http\Controllers\Group\GroupController@deleteGroup`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/provider/delete_plan/{plan}`
- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@deletePlan`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/provider/delete_product/{product}`
- Controller: `App\Http\Controllers\Product\ProductController@deleteProduct`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/provider/delete_violation/{violation}`
- Controller: `App\Http\Controllers\Violation\ViolationManagementController@providerDeleteViolation`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/provider/delete_weighbridge_record/{record}`
- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@deleteRecord`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/get_all_client_pickups`
- Controller: `App\Http\Controllers\Pickup\PickupController@getAllPickups`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/get_single_client/{client}`
- Controller: `App\Http\Controllers\Client\ClientController@show`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/get_single_client_pickup/{pickup}`
- Controller: `App\Http\Controllers\Pickup\PickupController@getSinglePickup`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/get_single_complaint/{complaint}`
- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@getComplaintDetails`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/get_single_driver/{driver}`
- Controller: `App\Http\Controllers\Driver\DriverController@show`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/get_single_fleet/{fleet}`
- Controller: `App\Http\Controllers\Fleet\FleetManagementController@show`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/get_single_group/{group}`
- Controller: `App\Http\Controllers\Group\GroupController@show`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/get_single_payment/{payment}`
- Controller: `App\Http\Controllers\Payment\ProviderPaymentController@getPayment`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/get_single_plan/{plan}`
- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@show`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/get_single_product/{product}`
- Controller: `App\Http\Controllers\Product\ProductController@getProductDetails`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/get_single_violation/{violation}`
- Controller: `App\Http\Controllers\Violation\ViolationManagementController@getViolationDetails`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/get_single_weighbridge_record/{record}`
- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@showRecord`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/guides`
- Controller: `App\Http\Controllers\Content\GuideController@listForAudience`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/handover_requests`
- Controller: `App\Http\Controllers\Handover\WasteHandoverController@list`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/handover_requests`
- Controller: `App\Http\Controllers\Handover\WasteHandoverController@create`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/handover_requests/{handover}`
- Controller: `App\Http\Controllers\Handover\WasteHandoverController@show`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/handover_requests/{handover}/accept`
- Controller: `App\Http\Controllers\Handover\WasteHandoverController@accept`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/handover_requests/{handover}/complete`
- Controller: `App\Http\Controllers\Handover\WasteHandoverController@complete`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/handover_requests/{handover}/decline`
- Controller: `App\Http\Controllers\Handover\WasteHandoverController@decline`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/login`
- Controller: `App\Http\Controllers\Provider\ProviderAuthenticationController@login`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/logout`
- Controller: `App\Http\Controllers\Provider\ProviderAuthenticationController@logout`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/manual_bin_code_scan`
- Controller: `App\Http\Controllers\Pickup\PickupController@manualCodeScan`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/map_pickup_overview`
- Controller: `App\Http\Controllers\Dashboard\DashboardController@mapPickupOverview`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/payments`
- Controller: `App\Http\Controllers\Payment\ProviderPaymentController@listPayments`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/payments/bins`
- Controller: `App\Http\Controllers\Payment\ProviderPaymentController@binsPayments`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/payments/waste_handover_request`
- Controller: `App\Http\Controllers\Payment\ProviderPaymentController@wasteHandoverRequestPayments`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/payments/weighbridge_records`
- Controller: `App\Http\Controllers\Payment\ProviderPaymentController@weighbridgeRecords`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/permissions`
- Controller: `App\Http\Controllers\Teams\RoleController@permissions`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/provider/pickups/{pickupCode}`
- Controller: `App\Http\Controllers\Pickup\PickupController@providerDeletePickup`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/pickups/{pickupCode}`
- Controller: `App\Http\Controllers\Pickup\PickupController@providerUpdatePickup`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/provider_pickup_creation`
- Controller: `App\Http\Controllers\Pickup\PickupController@providerPickupCreation`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/register_client`
- Controller: `App\Http\Controllers\Client\ClientController@register`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/register_driver`
- Controller: `App\Http\Controllers\Driver\DriverController@register`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/register_fleet`
- Controller: `App\Http\Controllers\Fleet\FleetManagementController@register`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/reports`
- Controller: `App\Http\Controllers\Reports\ReportsController@providerReports`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/resend_verificationCode`
- Controller: `App\Http\Controllers\Provider\ProviderPasswordController@sendVerificationNotification`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/reset_password`
- Controller: `App\Http\Controllers\Provider\ProviderPasswordController@resetPassword`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/reset_password_notification`
- Controller: `App\Http\Controllers\Provider\ProviderPasswordController@sendResetPasswordNotification`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/roles`
- Controller: `App\Http\Controllers\Teams\RoleController@index`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/roles`
- Controller: `App\Http\Controllers\Teams\RoleController@store`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/provider/roles/{roleSlug}`
- Controller: `App\Http\Controllers\Teams\RoleController@destroy`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/roles/{roleSlug}`
- Controller: `App\Http\Controllers\Teams\RoleController@update`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/scan_qrcode`
- Controller: `App\Http\Controllers\Client\ClientController@scanQRCode`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/set_pickup_date`
- Controller: `App\Http\Controllers\Pickup\PickupController@setPickupDate`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/set_pickup_price`
- Controller: `App\Http\Controllers\Pickup\PickupController@setPickupPrice`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/team_members`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@index`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/team_members`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@store`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/provider/team_members/{memberSlug}`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@destroy`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/team_members/{memberSlug}`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@show`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/team_members/{memberSlug}`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@update`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/team_members/{memberSlug}/status`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@updateStatus`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/update_client_details/{client}`
- Controller: `App\Http\Controllers\Client\ClientController@updateClientProfile`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/update_client_status`
- Controller: `App\Http\Controllers\Client\ClientController@updateStatus`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/update_complaint_status/{complaint}`
- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@updateComplaintStatus`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/update_driver_details/{driver}`
- Controller: `App\Http\Controllers\Driver\DriverController@updateDriverProfile`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/update_driver_location`
- Controller: `App\Http\Controllers\Driver\DriverController@updateLocation`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/update_driver_status`
- Controller: `App\Http\Controllers\Driver\DriverController@updateStatus`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/update_fleet_details/{fleet}`
- Controller: `App\Http\Controllers\Fleet\FleetManagementController@updateFleet`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/update_fleet_status`
- Controller: `App\Http\Controllers\Fleet\FleetManagementController@updateStatus`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/update_group_details/{group}`
- Controller: `App\Http\Controllers\Group\GroupController@updateGroup`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/update_group_status`
- Controller: `App\Http\Controllers\Group\GroupController@updateGroupStatus`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/update_plan_details/{plan}`
- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@updatePlan`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/update_plan_status`
- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@updateStatus`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/update_product/{product}`
- Controller: `App\Http\Controllers\Product\ProductController@updateProduct`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/update_profile/{provider}`
- Controller: `App\Http\Controllers\Provider\ProviderController@updateProfile`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/update_violation/{violation}`
- Controller: `App\Http\Controllers\Violation\ViolationManagementController@providerUpdateViolation`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/update_violation_status/{violation}`
- Controller: `App\Http\Controllers\Violation\ViolationManagementController@updateViolationStatus`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/provider/update_weighbridge_record_details/{record}`
- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@updateRecord`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/update_weighbridge_record_status`
- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@updateRecordStatus`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/verify_account`
- Controller: `App\Http\Controllers\Provider\ProviderPasswordController@verifyAccount`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/provider/weighbridge_records`
- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@allRecords`
- Middleware: `api, auth:provider`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/provider/weighbridge_records`
- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@createRecord`
- Middleware: `api, auth:provider`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

## District Assembly APIs

### `GET|HEAD` `/api/district_assembly/assignment_logs`
- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@assignmentLogs`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/district_assembly/change_password`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssembleyPasswordController@changePassword`
- Middleware: `api, auth:district_assembly`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/district_assembly/complaints`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@listComplaints`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/district_assembly/dashboard`
- Controller: `App\Http\Controllers\Dashboard\DashboardController@districtAssemblyDashboard`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/district_assembly/facilities`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@listFacilities`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/district_assembly/get_single_complaint/{complaint}`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@getComplaint`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/district_assembly/get_single_facility/{facility}`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@getFacility`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/district_assembly/get_single_plan/{plan}`
- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@show`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/district_assembly/get_single_provider/{provider}`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@getProvider`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/district_assembly/login`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssembleyAuthenticationController@login`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/district_assembly/logout`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssembleyAuthenticationController@logout`
- Middleware: `api, auth:district_assembly`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/district_assembly/map_pickup_overview`
- Controller: `App\Http\Controllers\Dashboard\DashboardController@mapPickupOverview`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/district_assembly/permissions`
- Controller: `App\Http\Controllers\Teams\RoleController@permissions`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/district_assembly/providers`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@listProviders`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/district_assembly/register_facility`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@registerFacility`
- Middleware: `api, auth:district_assembly`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/district_assembly/register_provider`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@registerProvider`
- Middleware: `api, auth:district_assembly`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/district_assembly/reports`
- Controller: `App\Http\Controllers\Reports\ReportsController@districtAssemblyReports`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/district_assembly/resend_verificationCode`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssembleyPasswordController@sendResetPasswordNotification`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/district_assembly/reset_password`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssembleyPasswordController@resetPassword`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/district_assembly/reset_password_notification`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssembleyPasswordController@sendResetPasswordNotification`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/district_assembly/roles`
- Controller: `App\Http\Controllers\Teams\RoleController@index`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/district_assembly/roles`
- Controller: `App\Http\Controllers\Teams\RoleController@store`
- Middleware: `api, auth:district_assembly`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/district_assembly/roles/{roleSlug}`
- Controller: `App\Http\Controllers\Teams\RoleController@destroy`
- Middleware: `api, auth:district_assembly`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/district_assembly/roles/{roleSlug}`
- Controller: `App\Http\Controllers\Teams\RoleController@update`
- Middleware: `api, auth:district_assembly`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/district_assembly/team_members`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@index`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/district_assembly/team_members`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@store`
- Middleware: `api, auth:district_assembly`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/district_assembly/team_members/{memberSlug}`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@destroy`
- Middleware: `api, auth:district_assembly`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/district_assembly/team_members/{memberSlug}`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@show`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/district_assembly/team_members/{memberSlug}`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@update`
- Middleware: `api, auth:district_assembly`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/district_assembly/team_members/{memberSlug}/status`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@updateStatus`
- Middleware: `api, auth:district_assembly`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/district_assembly/update_complaint_status/{complaint}`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@updateComplaintStatus`
- Middleware: `api, auth:district_assembly`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/district_assembly/update_facility_status/{facility}`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@updateFacilityStatus`
- Middleware: `api, auth:district_assembly`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/district_assembly/update_provider_status/{provider}`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@updateProviderStatus`
- Middleware: `api, auth:district_assembly`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/district_assembly/zones`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@listZones`
- Middleware: `api, auth:district_assembly`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

## Facility APIs

### `GET|HEAD` `/api/facility/all_weigh_bridge_entries`
- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@allEntries`
- Middleware: `api, auth:facility`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/facility/change_password`
- Controller: `App\Http\Controllers\Facility\FacilityPasswordController@changePassword`
- Middleware: `api, auth:facility`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/facility/dashboard`
- Controller: `App\Http\Controllers\Dashboard\DashboardController@facilityDashboard`
- Middleware: `api, auth:facility`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/facility/delete_weigh_bridge_entry/{entry}`
- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@deleteEntry`
- Middleware: `api, auth:facility`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/facility/get_single_weigh_bridge_entry/{entry}`
- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@show`
- Middleware: `api, auth:facility`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/facility/login`
- Controller: `App\Http\Controllers\Facility\FacilityAuthenticationController@login`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/facility/logout`
- Controller: `App\Http\Controllers\Facility\FacilityAuthenticationController@logout`
- Middleware: `api, auth:facility`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/facility/permissions`
- Controller: `App\Http\Controllers\Teams\RoleController@permissions`
- Middleware: `api, auth:facility`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/facility/register_weigh_bridge_entry`
- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@registerEntry`
- Middleware: `api, auth:facility`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/facility/reports`
- Controller: `App\Http\Controllers\Reports\ReportsController@facilityReports`
- Middleware: `api, auth:facility`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/facility/resend_verificationCode`
- Controller: `App\Http\Controllers\Facility\FacilityPasswordController@sendResetPasswordNotification`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/facility/reset_password`
- Controller: `App\Http\Controllers\Facility\FacilityPasswordController@resetPassword`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/facility/reset_password_notification`
- Controller: `App\Http\Controllers\Facility\FacilityPasswordController@sendResetPasswordNotification`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/facility/roles`
- Controller: `App\Http\Controllers\Teams\RoleController@index`
- Middleware: `api, auth:facility`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/facility/roles`
- Controller: `App\Http\Controllers\Teams\RoleController@store`
- Middleware: `api, auth:facility`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/facility/roles/{roleSlug}`
- Controller: `App\Http\Controllers\Teams\RoleController@destroy`
- Middleware: `api, auth:facility`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/facility/roles/{roleSlug}`
- Controller: `App\Http\Controllers\Teams\RoleController@update`
- Middleware: `api, auth:facility`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/facility/team_members`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@index`
- Middleware: `api, auth:facility`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/facility/team_members`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@store`
- Middleware: `api, auth:facility`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/facility/team_members/{memberSlug}`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@destroy`
- Middleware: `api, auth:facility`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/facility/team_members/{memberSlug}`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@show`
- Middleware: `api, auth:facility`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/facility/team_members/{memberSlug}`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@update`
- Middleware: `api, auth:facility`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/facility/team_members/{memberSlug}/status`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@updateStatus`
- Middleware: `api, auth:facility`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/facility/update_weigh_bridge_entry_details/{entry}`
- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@updateEntry`
- Middleware: `api, auth:facility`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/facility/update_weigh_bridge_entry_status`
- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@updateStatus`
- Middleware: `api, auth:facility`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/facility/verify_weigh_bridge_ticket`
- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@verifyByTicketCode`
- Middleware: `api, auth:facility`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

## Admin APIs

### `GET|HEAD` `/api/admin/actors_statistics`
- Controller: `App\Http\Controllers\Admin\AdminController@getStatisticsOverview`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/all_complaints`
- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@listComplaints`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/all_district_assemblies`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyController@index`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/all_facilities`
- Controller: `App\Http\Controllers\Facility\FacilityController@index`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/all_providers`
- Controller: `App\Http\Controllers\Provider\ProviderController@index`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/all_violations`
- Controller: `App\Http\Controllers\Violation\ViolationManagementController@listViolations`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/all_zones`
- Controller: `App\Http\Controllers\ZoneManagementController@listZones`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/assignment_logs`
- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@assignmentLogs`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/banners`
- Controller: `App\Http\Controllers\Content\BannerController@adminList`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/banners`
- Controller: `App\Http\Controllers\Content\BannerController@adminCreate`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/admin/banners/{banner}`
- Controller: `App\Http\Controllers\Content\BannerController@adminDelete`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/admin/banners/{banner}`
- Controller: `App\Http\Controllers\Content\BannerController@adminUpdate`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/change_password`
- Controller: `App\Http\Controllers\Admin\AdminPasswordController@changePassword`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/create_zone`
- Controller: `App\Http\Controllers\ZoneManagementController@createZone`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/admin/delete_district_assembly/{district_assembly}`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyController@deleteDistrictAssembly`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/admin/delete_facility/{facility}`
- Controller: `App\Http\Controllers\Facility\FacilityController@deleteFacility`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/admin/delete_provider/{provider}`
- Controller: `App\Http\Controllers\Provider\ProviderController@deleteProvider`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/admin/delete_zone/{zone}`
- Controller: `App\Http\Controllers\ZoneManagementController@deleteZone`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/get_single_complaint/{complaint}`
- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@getComplaintDetails`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/get_single_district_assembly/{district_assembly}`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyController@show`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/get_single_facility/{facility}`
- Controller: `App\Http\Controllers\Facility\FacilityController@show`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/get_single_plan/{plan}`
- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@show`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/get_single_provider/{provider}`
- Controller: `App\Http\Controllers\Provider\ProviderController@show`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/get_single_violation/{violation}`
- Controller: `App\Http\Controllers\Violation\ViolationManagementController@getViolationDetails`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/get_single_zone/{zone}`
- Controller: `App\Http\Controllers\ZoneManagementController@getZoneDetails`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/guides`
- Controller: `App\Http\Controllers\Content\GuideController@adminList`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/guides`
- Controller: `App\Http\Controllers\Content\GuideController@adminCreate`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/admin/guides/{guide}`
- Controller: `App\Http\Controllers\Content\GuideController@adminDelete`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/admin/guides/{guide}`
- Controller: `App\Http\Controllers\Content\GuideController@adminUpdate`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/login`
- Controller: `App\Http\Controllers\Admin\AdminAuthenticationController@login`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/logout`
- Controller: `App\Http\Controllers\Admin\AdminAuthenticationController@logout`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/map_pickup_overview`
- Controller: `App\Http\Controllers\Dashboard\DashboardController@mapPickupOverview`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/permissions`
- Controller: `App\Http\Controllers\Teams\RoleController@permissions`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/providers/{provider}/zones`
- Controller: `App\Http\Controllers\ZoneManagementController@listProviderZones`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/providers/{provider}/zones`
- Controller: `App\Http\Controllers\ZoneManagementController@assignProviderZones`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/admin/providers/{provider}/zones/{zone}`
- Controller: `App\Http\Controllers\ZoneManagementController@revokeProviderZone`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/register_admin`
- Controller: `App\Http\Controllers\Admin\AdminController@register`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/register_district_assembly`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyController@register`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/register_facility`
- Controller: `App\Http\Controllers\Facility\FacilityController@register`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/register_provider`
- Controller: `App\Http\Controllers\Provider\ProviderController@register`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/reports`
- Controller: `App\Http\Controllers\Reports\ReportsController@adminReports`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/resend_verificationCode`
- Controller: `App\Http\Controllers\Admin\AdminPasswordController@sendResetPasswordNotification`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/reset_password`
- Controller: `App\Http\Controllers\Admin\AdminPasswordController@resetPassword`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/reset_password_notification`
- Controller: `App\Http\Controllers\Admin\AdminPasswordController@sendResetPasswordNotification`
- Middleware: `api`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/roles`
- Controller: `App\Http\Controllers\Teams\RoleController@index`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/roles`
- Controller: `App\Http\Controllers\Teams\RoleController@store`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/admin/roles/{roleSlug}`
- Controller: `App\Http\Controllers\Teams\RoleController@destroy`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/admin/roles/{roleSlug}`
- Controller: `App\Http\Controllers\Teams\RoleController@update`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/team_members`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@index`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/team_members`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@store`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `DELETE` `/api/admin/team_members/{memberSlug}`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@destroy`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `GET|HEAD` `/api/admin/team_members/{memberSlug}`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@show`
- Middleware: `api, auth:admin, verified`
- Payload: none (except query/path params)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/admin/team_members/{memberSlug}`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@update`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/admin/team_members/{memberSlug}/status`
- Controller: `App\Http\Controllers\Teams\TeamMemberController@updateStatus`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/admin/update_complaint_status/{complaint}`
- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@updateComplaintStatus`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/admin/update_district_assembly_details/{district_assembly}`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyController@updateDistrictAssemblyProfile`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/update_district_assembly_status`
- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyController@updateStatus`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/admin/update_facility_details/{facility}`
- Controller: `App\Http\Controllers\Facility\FacilityController@updateFacilityProfile`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/update_facility_status`
- Controller: `App\Http\Controllers\Facility\FacilityController@updateStatus`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/admin/update_provider_details/{provider}`
- Controller: `App\Http\Controllers\Provider\ProviderController@updateProviderProfile`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/update_provider_status`
- Controller: `App\Http\Controllers\Provider\ProviderController@updateStatus`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/admin/update_purchase_status/{purchase}`
- Controller: `App\Http\Controllers\Purchase\PurchaseController@updatePurchaseStatus`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/admin/update_violation_status/{violation}`
- Controller: `App\Http\Controllers\Violation\ViolationManagementController@updateViolationStatus`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `PUT` `/api/admin/update_zone/{zone}`
- Controller: `App\Http\Controllers\ZoneManagementController@updateZone`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason

### `POST` `/api/admin/update_zone_status`
- Controller: `App\Http\Controllers\ZoneManagementController@updateZoneStatus`
- Middleware: `api, auth:admin, verified`
- Payload: controller-validated fields (no FormRequest auto-detected)
- Success Response: standard envelope with endpoint-specific data
- Error Response: standard envelope with in_error=true and reason
