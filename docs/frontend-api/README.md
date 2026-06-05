# Waste Management API — Frontend Reference

Generated: `2026-06-05T12:35:15+00:00`

## Base URL

`http://localhost/api`

## Demo credentials

Password for all demo accounts: `Password@123`

- **Admin**: `demo.admin@waste.test` — login `/api/admin/login`
- **District Assembly**: `demo.mmda@waste.test` — login `/api/district_assembly/login`
- **Provider**: `demo.provider@waste.test` — login `/api/provider/login`
- **Facility**: `demo.facility@waste.test` — login `/api/facility/login`
- **Client**: `demo.client@waste.test` — login `/api/client/login`

## Authentication

```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Login payload

```json
{
    "emailOrPhone": "demo.client@waste.test",
    "password": "Password@123"
}
```

### Response envelope

```json
{
    "data": {
        "status_code": 200,
        "message": "Action Successful",
        "in_error": false,
        "reason": "Resource retrieved successfully",
        "data": [
            "..."
        ],
        "point_in_time": "2026-06-05T12:35:15+00:00"
    }
}
```

## Seeded models (IDs for route params)

```json
{
    "admin": {
        "guard": "admin",
        "email": "demo.admin@waste.test",
        "phone_number": "233201000000",
        "slug": "admin-demo-001",
        "login_endpoint": "/api/admin/login",
        "login_payload": {
            "emailOrPhone": "demo.admin@waste.test",
            "password": "Password@123"
        }
    },
    "zones": [
        {
            "zone_slug": "zone-demo-accra-central",
            "name": "Demo Accra Central",
            "region": "Greater Accra"
        },
        {
            "zone_slug": "zone-demo-accra-east",
            "name": "Demo Accra East",
            "region": "Greater Accra"
        }
    ],
    "district_assembly": {
        "guard": "district_assembly",
        "email": "demo.mmda@waste.test",
        "phone_number": "233201000001",
        "slug": "mmda-demo-accra-metro",
        "login_endpoint": "/api/district_assembly/login",
        "login_payload": {
            "emailOrPhone": "demo.mmda@waste.test",
            "password": "Password@123"
        }
    },
    "provider": {
        "guard": "provider",
        "email": "demo.provider@waste.test",
        "phone_number": "233201000002",
        "slug": "provider-demo-001",
        "login_endpoint": "/api/provider/login",
        "login_payload": {
            "emailOrPhone": "demo.provider@waste.test",
            "password": "Password@123"
        }
    },
    "facility": {
        "guard": "facility",
        "email": "demo.facility@waste.test",
        "phone_number": "233201000003",
        "slug": "facility-demo-001",
        "login_endpoint": "/api/facility/login",
        "login_payload": {
            "emailOrPhone": "demo.facility@waste.test",
            "password": "Password@123"
        }
    },
    "client": {
        "guard": "client",
        "email": "demo.client@waste.test",
        "phone_number": "233201000004",
        "slug": "client-demo-001",
        "login_endpoint": "/api/client/login",
        "login_payload": {
            "emailOrPhone": "demo.client@waste.test",
            "password": "Password@123"
        }
    },
    "group": {
        "group_slug": "group-demo-residential",
        "name": "Demo Residential Block A"
    },
    "product": {
        "product_slug": "product-demo-bin-120l",
        "name": "120L Waste Bin"
    },
    "driver": {
        "driver_slug": "driver-demo-001",
        "email": "demo.driver@waste.test"
    },
    "fleet": {
        "fleet_slug": "fleet-demo-001",
        "license_plate": "GR-DEMO-001"
    },
    "pickup": {
        "code": "PKP-DEMO-001",
        "client_slug": "client-demo-001"
    },
    "bulk_waste_request": {
        "request_code": "BWR-DEMO-001"
    },
    "route_planner": {
        "id": 1
    },
    "waste_handover_request": {
        "id": 1,
        "code": "HND-DEMO-001"
    },
    "weighbridge_record": {
        "code": "WBR-DEMO-001"
    },
    "complaint": {
        "code": "CMP-DEMO-001"
    },
    "violation": {
        "code": "VIO-DEMO-001"
    },
    "feedback": {
        "code": "FDB-DEMO-001"
    },
    "cart": {
        "id": 1,
        "client_slug": "client-demo-001"
    },
    "purchase": {
        "id": 1
    },
    "payment": {
        "id": 1
    }
}
```

## Models catalog (32 Eloquent models)

See `models-catalog.json` for fillable fields and route keys used in API path parameters.

## Endpoints (271)

Full machine-readable spec: `api-reference.json`

### Guard: `admin`

#### `GET /api/admin/actors_statistics`

- Controller: `App\Http\Controllers\Admin\AdminController@getStatisticsOverview`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/all_complaints`

- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@listComplaints`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/all_district_assemblies`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyController@index`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/all_facilities`

- Controller: `App\Http\Controllers\Facility\FacilityController@index`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/all_providers`

- Controller: `App\Http\Controllers\Provider\ProviderController@index`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/all_violations`

- Controller: `App\Http\Controllers\Violation\ViolationManagementController@listViolations`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/all_zones`

- Controller: `App\Http\Controllers\ZoneManagementController@listZones`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/assignment_logs`

- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@assignmentLogs`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `POST /api/admin/change_password`

- Controller: `App\Http\Controllers\Admin\AdminPasswordController@changePassword`
- Auth required: `yes`
- Form request: `App\Http\Requests\Admin\PasswordChangeRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Admin\\PasswordChangeRequest",
    "rules": {
        "old_password": [
            "current_password:admin"
        ],
        "password": [
            "required",
            {},
            "confirmed",
            "bail"
        ]
    }
}
```

#### `POST /api/admin/create_zone`

- Controller: `App\Http\Controllers\ZoneManagementController@createZone`
- Auth required: `yes`
- Form request: `App\Http\Requests\Zone\ZoneCreationRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Zone\\ZoneCreationRequest",
    "rules": {
        "name": "required|string|unique:zones,name",
        "region": "required|string",
        "description": "nullable|string",
        "locations": "required|array"
    }
}
```

#### `DELETE /api/admin/delete_district_assembly/{district_assembly}`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyController@deleteDistrictAssembly`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `DELETE /api/admin/delete_facility/{facility}`

- Controller: `App\Http\Controllers\Facility\FacilityController@deleteFacility`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `DELETE /api/admin/delete_provider/{provider}`

- Controller: `App\Http\Controllers\Provider\ProviderController@deleteProvider`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `DELETE /api/admin/delete_zone/{zone}`

- Controller: `App\Http\Controllers\ZoneManagementController@deleteZone`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `GET /api/admin/facilities/{facility}/zones`

- Controller: `App\Http\Controllers\ZoneManagementController@listFacilityZones`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `POST /api/admin/facilities/{facility}/zones`

- Controller: `App\Http\Controllers\ZoneManagementController@assignFacilityZones`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `DELETE /api/admin/facilities/{facility}/zones/{zone}`

- Controller: `App\Http\Controllers\ZoneManagementController@revokeFacilityZone`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `GET /api/admin/get_single_complaint/{complaint}`

- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@getComplaintDetails`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/get_single_district_assembly/{district_assembly}`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyController@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/get_single_facility/{facility}`

- Controller: `App\Http\Controllers\Facility\FacilityController@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/get_single_plan/{plan}`

- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/get_single_provider/{provider}`

- Controller: `App\Http\Controllers\Provider\ProviderController@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/get_single_violation/{violation}`

- Controller: `App\Http\Controllers\Violation\ViolationManagementController@getViolationDetails`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/get_single_zone/{zone}`

- Controller: `App\Http\Controllers\ZoneManagementController@zoneOverview`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `POST /api/admin/logout`

- Controller: `App\Http\Controllers\Admin\AdminAuthenticationController@logout`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/admin/map_pickup_overview`

- Controller: `App\Http\Controllers\Dashboard\DashboardController@mapPickupOverview`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/permissions`

- Controller: `App\Http\Controllers\Teams\RoleController@permissions`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/providers/{provider}/zones`

- Controller: `App\Http\Controllers\ZoneManagementController@listProviderZones`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `POST /api/admin/providers/{provider}/zones`

- Controller: `App\Http\Controllers\ZoneManagementController@assignProviderZones`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `DELETE /api/admin/providers/{provider}/zones/{zone}`

- Controller: `App\Http\Controllers\ZoneManagementController@revokeProviderZone`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `POST /api/admin/register_district_assembly`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyController@register`
- Auth required: `yes`
- Form request: `App\Http\Requests\DistrictAssembley\OnboardingRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\DistrictAssembley\\OnboardingRequest",
    "rules": {
        "region": "required|string|max:100",
        "district": "required|string|max:255",
        "email": "required|string|email|max:255|unique:district_assemblies,email",
        "phone_number": "required|string|max:20|unique:district_assemblies,phone_number",
        "gps_address": "required|string|max:255",
        "first_name": "required|string|max:255",
        "last_name": "nullable|string|max:255",
        "profile_image": "nullable|starts_with:data:,http://,https://"
    }
}
```

#### `POST /api/admin/register_facility`

- Controller: `App\Http\Controllers\Facility\FacilityController@register`
- Auth required: `yes`
- Form request: `App\Http\Requests\Facility\FacilityOnboardingRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Facility\\FacilityOnboardingRequest",
    "rules": {
        "region": "required|string|max:100",
        "district": "required|string|max:255",
        "name": "required|string|max:255",
        "email": "required|string|email|max:255|unique:facilities,email",
        "phone_number": "required|string|max:20|unique:facilities,phone_number",
        "gps_address": "required|string|max:255",
        "first_name": "required|string|max:255",
        "last_name": "nullable|string|max:255",
        "business_registration_name": "nullable|string",
        "district_assembly": "nullable|string",
        "business_certificate_image": "nullable|starts_with:data:,http://,https://",
        "district_assembly_contract_image": "nullable|starts_with:data:,http://,https://",
        "tax_certificate_image": "nullable|starts_with:data:,http://,https://",
        "epa_permit_image": "nullable|starts_with:data:,http://,https://",
        "profile_image": "nullable|starts_with:data:,http://,https://",
        "type": "nullable|string",
        "ownership": "nullable|string",
        "zone_slugs": "nullable|array",
        "zone_slugs.*": "required|string|distinct|exists:zones,zone_slug"
    }
}
```

#### `POST /api/admin/register_provider`

- Controller: `App\Http\Controllers\Provider\ProviderController@register`
- Auth required: `yes`
- Form request: `App\Http\Requests\Provider\StoreProviderRegisterRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Provider\\StoreProviderRegisterRequest",
    "rules": {
        "first_name": "required|string",
        "last_name": "nullable|string",
        "email": "required|string|email|unique:providers,email",
        "phone_number": "required|string|unique:providers,phone_number",
        "business_name": "required|string",
        "district_assembly": "nullable|string",
        "business_registration_number": "required|string|unique:providers,business_registration_number",
        "gps_address": "required|string",
        "business_certificate_image": "nullable|starts_with:data:,http://,https://",
        "district_assembly_contract_image": "nullable|starts_with:data:,http://,https://",
        "tax_certificate_image": "nullable|starts_with:data:,http://,https://",
        "epa_permit_image": "nullable|starts_with:data:,http://,https://",
        "zone_slugs": "nullable|array",
        "zone_slugs.*": "required|string|distinct|exists:zones,zone_slug",
        "region": "required|string",
        "location": "required|string",
        "profile_image": "nullable|starts_with:data:,http://,https://"
    }
}
```

#### `GET /api/admin/reports`

- Controller: `App\Http\Controllers\Reports\ReportsController@adminReports`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `GET /api/admin/roles`

- Controller: `App\Http\Controllers\Teams\RoleController@index`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `POST /api/admin/roles`

- Controller: `App\Http\Controllers\Teams\RoleController@store`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `DELETE /api/admin/roles/{roleSlug}`

- Controller: `App\Http\Controllers\Teams\RoleController@destroy`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `PUT /api/admin/roles/{roleSlug}`

- Controller: `App\Http\Controllers\Teams\RoleController@update`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/admin/team_members`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@index`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `POST /api/admin/team_members`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@store`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `DELETE /api/admin/team_members/{memberSlug}`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@destroy`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `GET /api/admin/team_members/{memberSlug}`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard admin)

#### `PUT /api/admin/team_members/{memberSlug}`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@update`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/admin/team_members/{memberSlug}/status`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@updateStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/admin/update_complaint_status/{complaint}`

- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@updateComplaintStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/admin/update_district_assembly_details/{district_assembly}`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyController@updateDistrictAssemblyProfile`
- Auth required: `yes`
- Form request: `App\Http\Requests\DistrictAssembley\ProfileUpdateRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\DistrictAssembley\\ProfileUpdateRequest",
    "rules": {
        "region": "required|string|max:100",
        "district": "required|string|max:255",
        "email": [
            "required",
            "string",
            "email",
            "max:255",
            {}
        ],
        "phone_number": [
            "required",
            "string",
            "max:20",
            {}
        ],
        "gps_address": "required|string|max:255",
        "first_name": "required|string|max:255",
        "last_name": "nullable|string|max:255",
        "profile_image": "nullable|starts_with:data:,http://,https://"
    }
}
```

#### `POST /api/admin/update_district_assembly_status`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyController@updateStatus`
- Auth required: `yes`
- Form request: `App\Http\Requests\DistrictAssembley\AccountStatusRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\DistrictAssembley\\AccountStatusRequest",
    "rules": {
        "status": [
            "required",
            "string",
            "in:pending,deactivate,active",
            "bail"
        ],
        "district_assembly_slug": [
            "required",
            "string",
            "exists:district_assemblies,district_assembly_slug",
            "bail"
        ],
        "suspension_reason": [
            "nullable",
            "string",
            "max:1000"
        ],
        "corrective_action": [
            "nullable",
            "string",
            "max:1000"
        ]
    }
}
```

#### `PUT /api/admin/update_facility_details/{facility}`

- Controller: `App\Http\Controllers\Facility\FacilityController@updateFacilityProfile`
- Auth required: `yes`
- Form request: `App\Http\Requests\Facility\UpdateFacilityProfileRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Facility\\UpdateFacilityProfileRequest",
    "rules": {
        "district": "required|string|max:255",
        "name": "required|string|max:255",
        "email": [
            "required",
            "string",
            "email",
            "max:255",
            {}
        ],
        "phone_number": [
            "required",
            "string",
            "max:20",
            {}
        ],
        "gps_address": "required|string|max:255",
        "first_name": "required|string|max:255",
        "last_name": "nullable|string|max:255",
        "business_certificate_image": "nullable|starts_with:data:,http://,https://",
        "district_assembly_contract_image": "nullable|starts_with:data:,http://,https://",
        "tax_certificate_image": "nullable|starts_with:data:,http://,https://",
        "epa_permit_image": "nullable|starts_with:data:,http://,https://",
        "profile_image": "nullable|starts_with:data:,http://,https://",
        "type": "nullable|string|max:255",
        "ownership": "nullable|string|max:255"
    }
}
```

#### `POST /api/admin/update_facility_status`

- Controller: `App\Http\Controllers\Facility\FacilityController@updateStatus`
- Auth required: `yes`
- Form request: `App\Http\Requests\Facility\FacilityAccountStatusRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Facility\\FacilityAccountStatusRequest",
    "rules": {
        "status": [
            "required",
            "string",
            "in:pending,deactivate,active",
            "bail"
        ],
        "facility_slug": [
            "required",
            "string",
            "exists:facilities,facility_slug",
            "bail"
        ],
        "suspension_reason": [
            "nullable",
            "string",
            "max:1000"
        ],
        "corrective_action": [
            "nullable",
            "string",
            "max:1000"
        ]
    }
}
```

#### `PUT /api/admin/update_provider_details/{provider}`

- Controller: `App\Http\Controllers\Provider\ProviderController@updateProviderProfile`
- Auth required: `yes`
- Form request: `App\Http\Requests\Provider\UpdateProviderProfileRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Provider\\UpdateProviderProfileRequest",
    "rules": {
        "first_name": "required|string|max:255",
        "last_name": "nullable|string|max:255",
        "email": [
            "required",
            "string",
            "email",
            "max:255",
            {}
        ],
        "phone_number": [
            "required",
            "string",
            "max:20",
            {}
        ],
        "business_registration_number": [
            "required",
            "string",
            "max:100",
            {}
        ],
        "business_name": "nullable|string|max:255",
        "gps_address": "required|string|max:255",
        "district_assembly": "nullable|string|max:255",
        "business_certificate_image": "nullable|starts_with:data:,http://,https://",
        "district_assembly_contract_image": "nullable|starts_with:data:,http://,https://",
        "tax_certificate_image": "nullable|starts_with:data:,http://,https://",
        "epa_permit_image": "nullable|starts_with:data:,http://,https://",
        "region": "required|string|max:100",
        "location": "required|string|max:255",
        "profile_image": "nullable|starts_with:data:,http://,https://"
    }
}
```

#### `POST /api/admin/update_provider_status`

- Controller: `App\Http\Controllers\Provider\ProviderController@updateStatus`
- Auth required: `yes`
- Form request: `App\Http\Requests\Provider\ProviderStatusRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Provider\\ProviderStatusRequest",
    "rules": {
        "status": [
            "required",
            "string",
            "in:pending,deactivate,active",
            "bail"
        ],
        "provider_slug": [
            "required",
            "string",
            "exists:providers,provider_slug",
            "bail"
        ],
        "suspension_reason": [
            "nullable",
            "string",
            "max:1000"
        ],
        "corrective_action": [
            "nullable",
            "string",
            "max:1000"
        ]
    }
}
```

#### `PUT /api/admin/update_purchase_status/{purchase}`

- Controller: `App\Http\Controllers\Purchase\PurchaseController@updatePurchaseStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/admin/update_violation_status/{violation}`

- Controller: `App\Http\Controllers\Violation\ViolationManagementController@updateViolationStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/admin/update_zone/{zone}`

- Controller: `App\Http\Controllers\ZoneManagementController@updateZone`
- Auth required: `yes`
- Form request: `App\Http\Requests\Zone\ZoneUpdationRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Zone\\ZoneUpdationRequest",
    "rules": {
        "name": [
            "sometimes",
            {}
        ],
        "region": "sometimes|string",
        "description": "nullable|string",
        "locations": "nullable|array"
    }
}
```

#### `POST /api/admin/update_zone_status`

- Controller: `App\Http\Controllers\ZoneManagementController@updateZoneStatus`
- Auth required: `yes`
- Form request: `App\Http\Requests\Zone\ZoneStatusUpdateRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Zone\\ZoneStatusUpdateRequest",
    "rules": {
        "zone_slug": "required|exists:zones,zone_slug",
        "status": "required|in:active,revoke"
    }
}
```

### Guard: `client`

#### `GET /api/client/bulk_waste_requests`

- Controller: `App\Http\Controllers\Pickup\PickupController@clientBulkWasteRequests`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `GET /api/client/bulk_waste_requests/{requestCode}`

- Controller: `App\Http\Controllers\Pickup\PickupController@clientBulkWasteRequestShow`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `POST /api/client/bulk_waste_requests/{requestCode}/pay`

- Controller: `App\Http\Controllers\Pickup\PickupController@payBulkWasteRequest`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/client/cart`

- Controller: `App\Http\Controllers\Cart\CartController@getCart`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `POST /api/client/cart/add_item`

- Controller: `App\Http\Controllers\Cart\CartController@addItem`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/client/cart/checkout`

- Controller: `App\Http\Controllers\Cart\CartController@checkout`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `DELETE /api/client/cart/remove_item/{product_slug}`

- Controller: `App\Http\Controllers\Cart\CartController@removeItem`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `PUT /api/client/cart/update_item/{product_slug}`

- Controller: `App\Http\Controllers\Cart\CartController@updateItem`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/client/change_password`

- Controller: `App\Http\Controllers\Client\ClientPasswordController@changePassword`
- Auth required: `yes`
- Form request: `App\Http\Requests\Client\PasswordChangeRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Client\\PasswordChangeRequest",
    "rules": {
        "old_password": [
            "current_password:client"
        ],
        "password": [
            "required",
            {},
            "confirmed",
            "bail"
        ]
    }
}
```

#### `POST /api/client/create_bulk_waste_request`

- Controller: `App\Http\Controllers\Pickup\PickupController@bulkWasteRequest`
- Auth required: `yes`
- Form request: `App\Http\Requests\Pickup\PickupCreationRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Pickup\\PickupCreationRequest",
    "rules": {
        "title": "required|string",
        "category": "required|string",
        "description": "nullable|string",
        "location": "required|string",
        "images": [
            "nullable",
            "array"
        ],
        "images.*": [
            "string",
            "starts_with:data:,http://,https://"
        ]
    }
}
```

#### `POST /api/client/create_feedback`

- Controller: `App\Http\Controllers\Feedback\FeedbackController@createFeedback`
- Auth required: `yes`
- Form request: `App\Http\Requests\Feedback\CreateClientFeedbackRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Feedback\\CreateClientFeedbackRequest",
    "rules": {
        "ratings": [
            "required",
            "integer",
            "min:1",
            "max:5"
        ],
        "comments": [
            "nullable",
            "string"
        ],
        "score": [
            "nullable",
            "integer",
            "min:0",
            "max:10"
        ]
    }
}
```

#### `POST /api/client/create_purchase`

- Controller: `App\Http\Controllers\Purchase\PurchaseController@createPurchase`
- Auth required: `yes`
- Form request: `App\Http\Requests\Purchase\PurchaseCreationRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Purchase\\PurchaseCreationRequest",
    "rules": {
        "items": [
            "required",
            "array",
            "min:1"
        ],
        "items.*.product_slug": [
            "required",
            "string",
            "exists:products,product_slug"
        ],
        "items.*.quantity": [
            "required",
            "integer",
            "min:1"
        ]
    }
}
```

#### `POST /api/client/create_violation`

- Controller: `App\Http\Controllers\Violation\ViolationManagementController@createClientViolation`
- Auth required: `yes`
- Form request: `App\Http\Requests\Violation\ViolationCreationRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Violation\\ViolationCreationRequest",
    "rules": {
        "client_slug": [
            "nullable",
            "string"
        ],
        "type": [
            "required",
            "string"
        ],
        "location": [
            "required",
            "string"
        ],
        "description": [
            "nullable",
            "string"
        ],
        "images": [
            "nullable",
            "array"
        ],
        "images.*": [
            "nullable",
            "starts_with:data:,http://,https://"
        ]
    }
}
```

#### `DELETE /api/client/delete_bulk_waste_request/{requestCode}`

- Controller: `App\Http\Controllers\Pickup\PickupController@deleteBulkWasteRequest`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `DELETE /api/client/delete_feedback/{feedback}`

- Controller: `App\Http\Controllers\Feedback\FeedbackController@deleteFeedback`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `DELETE /api/client/delete_violation/{violation}`

- Controller: `App\Http\Controllers\Violation\ViolationManagementController@deleteViolation`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `GET /api/client/get_all_notifications`

- Controller: `App\Http\Controllers\Notification\NotificationController@getAllClientNotifications`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `GET /api/client/get_client_pickups`

- Controller: `App\Http\Controllers\Pickup\PickupController@getClientPickups`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `GET /api/client/get_complaints`

- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@listComplaints`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `GET /api/client/get_feedbacks`

- Controller: `App\Http\Controllers\Feedback\FeedbackController@listFeedbacks`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `GET /api/client/get_payment_history`

- Controller: `App\Http\Controllers\Purchase\PurchaseController@getPaymentHistory`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `GET /api/client/get_pickup_dates`

- Controller: `App\Http\Controllers\Pickup\PickupController@getPickupDates`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `GET /api/client/get_products`

- Controller: `App\Http\Controllers\Product\ProductController@listProducts`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `GET /api/client/get_purchases`

- Controller: `App\Http\Controllers\Purchase\PurchaseController@listPurchases`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `GET /api/client/get_single_complaint/{complaint}`

- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@getComplaintDetails`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `GET /api/client/get_single_feedback/{feedback}`

- Controller: `App\Http\Controllers\Feedback\FeedbackController@getFeedbackDetails`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `GET /api/client/get_single_pickup/{pickupCode}`

- Controller: `App\Http\Controllers\Pickup\PickupController@getSinglePickup`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `GET /api/client/get_single_product/{product}`

- Controller: `App\Http\Controllers\Product\ProductController@getProductDetails`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `GET /api/client/get_single_purchase/{purchase}`

- Controller: `App\Http\Controllers\Purchase\PurchaseController@getPurchaseDetails`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `GET /api/client/get_single_violation/{violation}`

- Controller: `App\Http\Controllers\Violation\ViolationManagementController@getViolationDetails`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `GET /api/client/get_violations`

- Controller: `App\Http\Controllers\Violation\ViolationManagementController@listViolations`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `POST /api/client/logout`

- Controller: `App\Http\Controllers\Client\ClientAuthenticationController@logout`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/client/payments/calpay/initiate`

- Controller: `App\Http\Controllers\Payment\CalPayPaymentController@initiate`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/client/payments/calpay/status`

- Controller: `App\Http\Controllers\Payment\CalPayPaymentController@status`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `POST /api/client/payments/registration`

- Controller: `App\Http\Controllers\Payment\ClientPaymentController@createRegistrationPayment`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/client/payments/registration/status`

- Controller: `App\Http\Controllers\Payment\ClientPaymentController@registrationPaymentStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard client)

#### `POST /api/client/process_payment/{purchase}`

- Controller: `App\Http\Controllers\Purchase\PurchaseController@processPayment`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/client/update_bulk_waste_request/{requestCode}`

- Controller: `App\Http\Controllers\Pickup\PickupController@updateBulkWasteRequest`
- Auth required: `yes`
- Form request: `App\Http\Requests\Pickup\UpdatePickupRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Pickup\\UpdatePickupRequest",
    "rules": {
        "title": [
            "sometimes",
            "string"
        ],
        "category": [
            "sometimes",
            "string"
        ],
        "description": [
            "sometimes",
            "string"
        ],
        "location": [
            "sometimes",
            "string"
        ],
        "images": [
            "sometimes",
            "array"
        ],
        "images.*": [
            "sometimes",
            "starts_with:data:,http://,https://"
        ]
    }
}
```

#### `PUT /api/client/update_feedback/{feedback}`

- Controller: `App\Http\Controllers\Feedback\FeedbackController@updateFeedback`
- Auth required: `yes`
- Form request: `App\Http\Requests\Feedback\UpdateClientFeedbackRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Feedback\\UpdateClientFeedbackRequest",
    "rules": {
        "ratings": "sometimes|integer|min:1|max:5",
        "comments": "sometimes|string|max:1000",
        "score": "sometimes|numeric|min:0|max:100",
        "status": "sometimes|string|in:pending,reviewed,resolved"
    }
}
```

#### `PUT /api/client/update_profile/{client}`

- Controller: `App\Http\Controllers\Client\ClientController@updateClientProfile`
- Auth required: `yes`
- Form request: `App\Http\Requests\Client\UpdateClientProfileRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Client\\UpdateClientProfileRequest",
    "rules": {
        "first_name": "required|string|max:255",
        "last_name": "nullable|string|max:255",
        "email": [
            "required",
            "string",
            "email",
            "max:255",
            {}
        ],
        "phone_number": [
            "required",
            "string",
            "max:20",
            {}
        ],
        "gps_address": "required|string|max:255",
        "latitude": "nullable|numeric|between:-90,90",
        "longitude": "nullable|numeric|between:-180,180",
        "type": "nullable|string|max:255",
        "bin_slug": "nullable|string|max:255",
        "group_slug": "nullable|string|exists:groups,group_slug",
        "registration_fee": "sometimes|nullable|numeric|min:0",
        "profile_image": "nullable|starts_with:data:,http://,https://"
    }
}
```

#### `PUT /api/client/update_violation/{violation}`

- Controller: `App\Http\Controllers\Violation\ViolationManagementController@updateViolation`
- Auth required: `yes`
- Form request: `App\Http\Requests\Violation\ViolationUpdateRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Violation\\ViolationUpdateRequest",
    "rules": {
        "type": [
            "sometimes",
            "string"
        ],
        "description": [
            "sometimes",
            "nullable",
            "string"
        ],
        "location": [
            "sometimes",
            "string"
        ],
        "status": [
            "sometimes",
            "string",
            "in:pending,open,in_progress,closed"
        ],
        "images": [
            "nullable"
        ],
        "images.*": [
            "nullable",
            "starts_with:data:,http://,https://"
        ]
    }
}
```

### Guard: `district_assembly`

#### `GET /api/district_assembly/assignment_logs`

- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@assignmentLogs`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `POST /api/district_assembly/change_password`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssembleyPasswordController@changePassword`
- Auth required: `yes`
- Form request: `App\Http\Requests\DistrictAssembley\PasswordChangeResetRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\DistrictAssembley\\PasswordChangeResetRequest",
    "rules": {
        "old_password": [
            "current_password:district_assembly"
        ],
        "password": [
            "required",
            {},
            "confirmed",
            "bail"
        ]
    }
}
```

#### `GET /api/district_assembly/complaints`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@listComplaints`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `GET /api/district_assembly/dashboard`

- Controller: `App\Http\Controllers\Dashboard\DashboardController@districtAssemblyDashboard`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `GET /api/district_assembly/facilities`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@listFacilities`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `GET /api/district_assembly/facilities/{facility}/zones`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@listFacilityZones`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `POST /api/district_assembly/facilities/{facility}/zones`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@assignFacilityZones`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `DELETE /api/district_assembly/facilities/{facility}/zones/{zone}`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@revokeFacilityZone`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `GET /api/district_assembly/get_single_complaint/{complaint}`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@getComplaint`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `GET /api/district_assembly/get_single_facility/{facility}`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@getFacility`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `GET /api/district_assembly/get_single_plan/{plan}`

- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `GET /api/district_assembly/get_single_provider/{provider}`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@getProvider`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `POST /api/district_assembly/logout`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssembleyAuthenticationController@logout`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/district_assembly/map_pickup_overview`

- Controller: `App\Http\Controllers\Dashboard\DashboardController@mapPickupOverview`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `GET /api/district_assembly/permissions`

- Controller: `App\Http\Controllers\Teams\RoleController@permissions`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `GET /api/district_assembly/providers`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@listProviders`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `GET /api/district_assembly/providers/{provider}/zones`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@listProviderZones`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `POST /api/district_assembly/providers/{provider}/zones`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@assignProviderZones`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `DELETE /api/district_assembly/providers/{provider}/zones/{zone}`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@revokeProviderZone`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `POST /api/district_assembly/register_facility`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@registerFacility`
- Auth required: `yes`
- Form request: `App\Http\Requests\Facility\FacilityOnboardingRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Facility\\FacilityOnboardingRequest",
    "rules": {
        "region": "required|string|max:100",
        "district": "required|string|max:255",
        "name": "required|string|max:255",
        "email": "required|string|email|max:255|unique:facilities,email",
        "phone_number": "required|string|max:20|unique:facilities,phone_number",
        "gps_address": "required|string|max:255",
        "first_name": "required|string|max:255",
        "last_name": "nullable|string|max:255",
        "business_registration_name": "nullable|string",
        "district_assembly": "nullable|string",
        "business_certificate_image": "nullable|starts_with:data:,http://,https://",
        "district_assembly_contract_image": "nullable|starts_with:data:,http://,https://",
        "tax_certificate_image": "nullable|starts_with:data:,http://,https://",
        "epa_permit_image": "nullable|starts_with:data:,http://,https://",
        "profile_image": "nullable|starts_with:data:,http://,https://",
        "type": "nullable|string",
        "ownership": "nullable|string",
        "zone_slugs": "nullable|array",
        "zone_slugs.*": "required|string|distinct|exists:zones,zone_slug"
    }
}
```

#### `POST /api/district_assembly/register_provider`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@registerProvider`
- Auth required: `yes`
- Form request: `App\Http\Requests\Provider\StoreProviderRegisterRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Provider\\StoreProviderRegisterRequest",
    "rules": {
        "first_name": "required|string",
        "last_name": "nullable|string",
        "email": "required|string|email|unique:providers,email",
        "phone_number": "required|string|unique:providers,phone_number",
        "business_name": "required|string",
        "district_assembly": "nullable|string",
        "business_registration_number": "required|string|unique:providers,business_registration_number",
        "gps_address": "required|string",
        "business_certificate_image": "nullable|starts_with:data:,http://,https://",
        "district_assembly_contract_image": "nullable|starts_with:data:,http://,https://",
        "tax_certificate_image": "nullable|starts_with:data:,http://,https://",
        "epa_permit_image": "nullable|starts_with:data:,http://,https://",
        "zone_slugs": "nullable|array",
        "zone_slugs.*": "required|string|distinct|exists:zones,zone_slug",
        "region": "required|string",
        "location": "required|string",
        "profile_image": "nullable|starts_with:data:,http://,https://"
    }
}
```

#### `GET /api/district_assembly/reports`

- Controller: `App\Http\Controllers\Reports\ReportsController@districtAssemblyReports`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `GET /api/district_assembly/roles`

- Controller: `App\Http\Controllers\Teams\RoleController@index`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `POST /api/district_assembly/roles`

- Controller: `App\Http\Controllers\Teams\RoleController@store`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `DELETE /api/district_assembly/roles/{roleSlug}`

- Controller: `App\Http\Controllers\Teams\RoleController@destroy`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `PUT /api/district_assembly/roles/{roleSlug}`

- Controller: `App\Http\Controllers\Teams\RoleController@update`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/district_assembly/team_members`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@index`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `POST /api/district_assembly/team_members`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@store`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `DELETE /api/district_assembly/team_members/{memberSlug}`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@destroy`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `GET /api/district_assembly/team_members/{memberSlug}`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

#### `PUT /api/district_assembly/team_members/{memberSlug}`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@update`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/district_assembly/team_members/{memberSlug}/status`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@updateStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/district_assembly/update_complaint_status/{complaint}`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@updateComplaintStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/district_assembly/update_facility_status/{facility}`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@updateFacilityStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/district_assembly/update_provider_status/{provider}`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@updateProviderStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/district_assembly/zones`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssemblyManagementController@listZones`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard district_assembly)

### Guard: `facility`

#### `GET /api/facility/all_fleets`

- Controller: `App\Http\Controllers\Fleet\FleetManagementController@getAllFleets`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard facility)

#### `GET /api/facility/all_providers`

- Controller: `App\Http\Controllers\Provider\ProviderController@allProviders`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard facility)

#### `GET /api/facility/all_weigh_bridge_entries`

- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@allEntries`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard facility)

#### `GET /api/facility/all_zones`

- Controller: `App\Http\Controllers\ZoneManagementController@listZones`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard facility)

#### `POST /api/facility/change_password`

- Controller: `App\Http\Controllers\Facility\FacilityPasswordController@changePassword`
- Auth required: `yes`
- Form request: `App\Http\Requests\Facility\FacilityPasswordChangeResetRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Facility\\FacilityPasswordChangeResetRequest",
    "rules": {
        "old_password": [
            "current_password:facility"
        ],
        "password": [
            "required",
            {},
            "confirmed",
            "bail"
        ]
    }
}
```

#### `GET /api/facility/dashboard`

- Controller: `App\Http\Controllers\Dashboard\DashboardController@facilityDashboard`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard facility)

#### `DELETE /api/facility/delete_weigh_bridge_entry/{entry}`

- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@deleteEntry`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `GET /api/facility/get_single_weigh_bridge_entry/{entry}`

- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard facility)

#### `POST /api/facility/logout`

- Controller: `App\Http\Controllers\Facility\FacilityAuthenticationController@logout`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/facility/permissions`

- Controller: `App\Http\Controllers\Teams\RoleController@permissions`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard facility)

#### `POST /api/facility/register_weigh_bridge_entry`

- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@registerEntry`
- Auth required: `yes`
- Form request: `App\Http\Requests\Weighbridge\CreateTicket`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Weighbridge\\CreateTicket",
    "rules": {
        "provider_slug": [
            "required",
            "string",
            "exists:providers,provider_slug"
        ],
        "fleet_slug": [
            "nullable",
            "string",
            "exists:fleets,fleet_slug"
        ],
        "zone_slug": [
            "nullable",
            "string",
            "exists:zones,zone_slug"
        ],
        "fleet_code": [
            "nullable",
            "string"
        ],
        "gross_weight": [
            "nullable",
            "numeric",
            "min:0"
        ],
        "amount": [
            "required",
            "numeric",
            "min:0"
        ],
        "payment_status": [
            "required",
            "string",
            "in:pending_payment,paid,credit"
        ],
        "scan_status": [
            "nullable",
            "string",
            "in:scanned,unscanned,handover"
        ],
        "notes": [
            "nullable",
            "string"
        ]
    }
}
```

#### `GET /api/facility/reports`

- Controller: `App\Http\Controllers\Reports\ReportsController@facilityReports`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard facility)

#### `GET /api/facility/roles`

- Controller: `App\Http\Controllers\Teams\RoleController@index`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard facility)

#### `POST /api/facility/roles`

- Controller: `App\Http\Controllers\Teams\RoleController@store`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `DELETE /api/facility/roles/{roleSlug}`

- Controller: `App\Http\Controllers\Teams\RoleController@destroy`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `PUT /api/facility/roles/{roleSlug}`

- Controller: `App\Http\Controllers\Teams\RoleController@update`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/facility/team_members`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@index`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard facility)

#### `POST /api/facility/team_members`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@store`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `DELETE /api/facility/team_members/{memberSlug}`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@destroy`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `GET /api/facility/team_members/{memberSlug}`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard facility)

#### `PUT /api/facility/team_members/{memberSlug}`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@update`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/facility/team_members/{memberSlug}/status`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@updateStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/facility/update_weigh_bridge_entry_details/{entry}`

- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@updateEntry`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/facility/update_weigh_bridge_entry_status`

- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@updateStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/facility/verify_weigh_bridge_ticket`

- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@verifyByTicketCode`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

### Guard: `provider`

#### `POST /api/provider/add_group`

- Controller: `App\Http\Controllers\Group\GroupController@register`
- Auth required: `yes`
- Form request: `App\Http\Requests\Group\GroupCreation`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Group\\GroupCreation",
    "rules": {
        "name": "required|string|unique:groups,name",
        "description": "nullable|string"
    }
}
```

#### `GET /api/provider/all_clients`

- Controller: `App\Http\Controllers\Client\ClientController@allClients`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/all_complaints`

- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@listClientComplaints`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/all_drivers`

- Controller: `App\Http\Controllers\Driver\DriverController@allDrivers`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/all_fleets`

- Controller: `App\Http\Controllers\Fleet\FleetManagementController@allFleets`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/all_groups`

- Controller: `App\Http\Controllers\Group\GroupController@allGroups`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/all_plans`

- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@allPlans`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/all_products`

- Controller: `App\Http\Controllers\Product\ProductController@listProducts`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/all_violations`

- Controller: `App\Http\Controllers\Violation\ViolationManagementController@listClientViolations`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/assignment_logs`

- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@assignmentLogs`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `POST /api/provider/change_password`

- Controller: `App\Http\Controllers\Provider\ProviderPasswordController@changePassword`
- Auth required: `yes`
- Form request: `App\Http\Requests\Provider\ProviderPasswordChangeResetRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Provider\\ProviderPasswordChangeResetRequest",
    "rules": {
        "old_password": [
            "current_password:provider"
        ],
        "password": [
            "required",
            {},
            "confirmed",
            "bail"
        ]
    }
}
```

#### `POST /api/provider/change_scan_status`

- Controller: `App\Http\Controllers\Pickup\PickupController@setScanStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/provider/create_complaint`

- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@createComplaint`
- Auth required: `yes`
- Form request: `App\Http\Requests\Complaint\ComplaintCreationRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Complaint\\ComplaintCreationRequest",
    "rules": {
        "location": [
            "required",
            "string"
        ],
        "description": [
            "nullable",
            "string"
        ],
        "images": [
            "nullable",
            "array"
        ],
        "images.*": [
            "nullable",
            "string"
        ]
    }
}
```

#### `POST /api/provider/create_plan`

- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@register`
- Auth required: `yes`
- Form request: `App\Http\Requests\RoutePlanner\RegisterRoute`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\RoutePlanner\\RegisterRoute",
    "rules": {
        "provider_slug": "nullable|string|exists:providers,provider_slug",
        "driver_slug": "required|string|exists:drivers,driver_slug",
        "fleet_slug": "required|string|exists:fleets,fleet_slug",
        "pickup_type": "required|string|in:bulk_waste_request,normal",
        "pickup_date": "nullable|date",
        "group_slugs": "required_if:pickup_type,normal|array|min:1",
        "group_slugs.*": "string|exists:groups,group_slug",
        "bulk_request_codes": "required_if:pickup_type,bulk_waste_request|array|min:1",
        "bulk_request_codes.*": "string|exists:bulk_waste_requests,request_code",
        "status": "nullable|string|in:pending,completed,cancelled,progress,in_progress"
    }
}
```

#### `POST /api/provider/create_product`

- Controller: `App\Http\Controllers\Product\ProductController@createProduct`
- Auth required: `yes`
- Form request: `App\Http\Requests\Product\ProductCreationRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Product\\ProductCreationRequest",
    "rules": {
        "name": [
            "required",
            "string"
        ],
        "category": [
            "nullable",
            "string",
            "max:255"
        ],
        "color": [
            "nullable",
            "string"
        ],
        "size": [
            "nullable",
            "string"
        ],
        "images": [
            "nullable",
            "array"
        ],
        "images.*": [
            "nullable",
            "starts_with:data:,http://,https://"
        ],
        "original_price": [
            "required",
            "numeric",
            "min:0"
        ],
        "discounted_price": [
            "nullable",
            "numeric",
            "min:0"
        ],
        "discount_percentage": [
            "nullable",
            "numeric",
            "min:0",
            "max:100"
        ],
        "quantity": [
            "required",
            "integer",
            "min:0"
        ]
    }
}
```

#### `POST /api/provider/create_violation`

- Controller: `App\Http\Controllers\Violation\ViolationManagementController@createViolation`
- Auth required: `yes`
- Form request: `App\Http\Requests\Violation\ViolationCreationRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Violation\\ViolationCreationRequest",
    "rules": {
        "client_slug": [
            "nullable",
            "string"
        ],
        "type": [
            "required",
            "string"
        ],
        "location": [
            "required",
            "string"
        ],
        "description": [
            "nullable",
            "string"
        ],
        "images": [
            "nullable",
            "array"
        ],
        "images.*": [
            "nullable",
            "starts_with:data:,http://,https://"
        ]
    }
}
```

#### `GET /api/provider/dashboard`

- Controller: `App\Http\Controllers\Dashboard\DashboardController@providerDashboard`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `DELETE /api/provider/delete_client/{client}`

- Controller: `App\Http\Controllers\Client\ClientController@deleteClient`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `DELETE /api/provider/delete_driver/{driver}`

- Controller: `App\Http\Controllers\Driver\DriverController@deleteDriver`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `DELETE /api/provider/delete_fleet/{fleet}`

- Controller: `App\Http\Controllers\Fleet\FleetManagementController@deleteFleet`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `DELETE /api/provider/delete_group/{group}`

- Controller: `App\Http\Controllers\Group\GroupController@deleteGroup`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `DELETE /api/provider/delete_plan/{plan}`

- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@deletePlan`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `DELETE /api/provider/delete_product/{product}`

- Controller: `App\Http\Controllers\Product\ProductController@deleteProduct`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `DELETE /api/provider/delete_violation/{violation}`

- Controller: `App\Http\Controllers\Violation\ViolationManagementController@providerDeleteViolation`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `DELETE /api/provider/delete_weighbridge_record/{record}`

- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@deleteRecord`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `GET /api/provider/get_single_client/{client}`

- Controller: `App\Http\Controllers\Client\ClientController@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/get_single_complaint/{complaint}`

- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@getComplaintDetails`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/get_single_driver/{driver}`

- Controller: `App\Http\Controllers\Driver\DriverController@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/get_single_fleet/{fleet}`

- Controller: `App\Http\Controllers\Fleet\FleetManagementController@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/get_single_group/{group}`

- Controller: `App\Http\Controllers\Group\GroupController@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/get_single_payment/{payment}`

- Controller: `App\Http\Controllers\Payment\ProviderPaymentController@getPayment`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/get_single_plan/{plan}`

- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/get_single_product/{product}`

- Controller: `App\Http\Controllers\Product\ProductController@getProductDetails`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/get_single_violation/{violation}`

- Controller: `App\Http\Controllers\Violation\ViolationManagementController@getViolationDetails`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/get_single_weighbridge_record/{record}`

- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@showRecord`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/handover_requests`

- Controller: `App\Http\Controllers\Handover\WasteHandoverController@list`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `POST /api/provider/handover_requests`

- Controller: `App\Http\Controllers\Handover\WasteHandoverController@create`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/provider/handover_requests/available`

- Controller: `App\Http\Controllers\Handover\WasteHandoverController@availableInZone`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/handover_requests/drivers/{driverSlug}/fleets`

- Controller: `App\Http\Controllers\Handover\WasteHandoverController@fleetsForDriver`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/handover_requests/{handover}`

- Controller: `App\Http\Controllers\Handover\WasteHandoverController@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `POST /api/provider/handover_requests/{handover}/accept`

- Controller: `App\Http\Controllers\Handover\WasteHandoverController@accept`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/provider/handover_requests/{handover}/complete`

- Controller: `App\Http\Controllers\Handover\WasteHandoverController@complete`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/provider/handover_requests/{handover}/confirm_payment`

- Controller: `App\Http\Controllers\Handover\WasteHandoverController@confirmPayment`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/provider/handover_requests/{handover}/decline`

- Controller: `App\Http\Controllers\Handover\WasteHandoverController@decline`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/provider/logout`

- Controller: `App\Http\Controllers\Provider\ProviderAuthenticationController@logout`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/provider/manual_bin_code_scan`

- Controller: `App\Http\Controllers\Pickup\PickupController@manualCodeScan`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/provider/map_pickup_overview`

- Controller: `App\Http\Controllers\Dashboard\DashboardController@mapPickupOverview`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/payments`

- Controller: `App\Http\Controllers\Payment\ProviderPaymentController@listPayments`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/payments/bins`

- Controller: `App\Http\Controllers\Payment\ProviderPaymentController@binsPayments`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `POST /api/provider/payments/calpay/initiate`

- Controller: `App\Http\Controllers\Payment\CalPayPaymentController@initiate`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/provider/payments/calpay/status`

- Controller: `App\Http\Controllers\Payment\CalPayPaymentController@status`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/payments/waste_handover_request`

- Controller: `App\Http\Controllers\Payment\ProviderPaymentController@wasteHandoverRequestPayments`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/payments/weighbridge_records`

- Controller: `App\Http\Controllers\Payment\ProviderPaymentController@weighbridgeRecords`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/permissions`

- Controller: `App\Http\Controllers\Teams\RoleController@permissions`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `POST /api/provider/register_client`

- Controller: `App\Http\Controllers\Client\ClientController@register`
- Auth required: `yes`
- Form request: `App\Http\Requests\Client\RegisterRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Client\\RegisterRequest",
    "rules": {
        "first_name": "required|string",
        "last_name": "nullable|string",
        "email": "required|string|email|unique:clients,email",
        "phone_number": "required|string|unique:clients,phone_number",
        "gps_address": "required|string",
        "latitude": "nullable|numeric|between:-90,90",
        "longitude": "nullable|numeric|between:-180,180",
        "type": "required|string",
        "bin_slug": "nullable|string",
        "group_slug": "nullable|string|exists:groups,group_slug",
        "registration_fee": "nullable|numeric|min:0",
        "registration_status": "nullable|boolean",
        "profile_image": "nullable|starts_with:data:,http://,https://"
    }
}
```

#### `POST /api/provider/register_driver`

- Controller: `App\Http\Controllers\Driver\DriverController@register`
- Auth required: `yes`
- Form request: `App\Http\Requests\Driver\RegisterRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Driver\\RegisterRequest",
    "rules": {
        "first_name": "required|string",
        "middle_name": "nullable|string",
        "last_name": "nullable|string",
        "date_of_birth": "required|date",
        "id_card_type": "required|string",
        "id_card_number": "required|string",
        "license_class": "required|string",
        "license_number": "required|string",
        "license_date_issued": "required|date",
        "license_expiry_issued": "required|date",
        "email": "required|string|email|unique:drivers,email",
        "phone_number": "required|string|unique:drivers,phone_number",
        "address": "required|string",
        "emergency_contact_name": "required|string",
        "emergency_phone_number": "required|string",
        "emergency_contract_address": "required|string",
        "license_front_image": "required|starts_with:data:,http://,https://",
        "license_back_image": "required|starts_with:data:,http://,https://",
        "profile_image": "required|starts_with:data:,http://,https://"
    }
}
```

#### `POST /api/provider/register_fleet`

- Controller: `App\Http\Controllers\Fleet\FleetManagementController@register`
- Auth required: `yes`
- Form request: `App\Http\Requests\Fleet\RegisterFleetRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Fleet\\RegisterFleetRequest",
    "rules": {
        "vehicle_make": "nullable|string",
        "model": "nullable|string",
        "manufacture_year": "nullable|integer",
        "license_plate": "nullable|string|unique:fleets,license_plate",
        "bin_capacity": "nullable|string",
        "color": "nullable|string",
        "owner_first_name": "nullable|string",
        "owner_last_name": "nullable|string",
        "owner_phone_number": "nullable|string",
        "owner_address": "nullable|string",
        "provider_slug": "nullable|string|exists:providers,provider_slug",
        "insurance_expiry_date": "nullable|date",
        "insurance_policy_number": "nullable|string|unique:fleets,insurance_policy_number",
        "vehicle_images": "nullable",
        "vehicle_registration_certificate_image": "nullable",
        "vehicle_insurance_certificate_image": "nullable",
        "vehicle_roadworthy_certificate_image": "nullable",
        "status": "nullable|string|in:active,inactive,maintenance"
    }
}
```

#### `GET /api/provider/reports`

- Controller: `App\Http\Controllers\Reports\ReportsController@providerReports`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `GET /api/provider/roles`

- Controller: `App\Http\Controllers\Teams\RoleController@index`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `POST /api/provider/roles`

- Controller: `App\Http\Controllers\Teams\RoleController@store`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `DELETE /api/provider/roles/{roleSlug}`

- Controller: `App\Http\Controllers\Teams\RoleController@destroy`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `PUT /api/provider/roles/{roleSlug}`

- Controller: `App\Http\Controllers\Teams\RoleController@update`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/provider/scan_qrcode`

- Controller: `App\Http\Controllers\Client\ClientController@scanQRCode`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/provider/team_members`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@index`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `POST /api/provider/team_members`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@store`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `DELETE /api/provider/team_members/{memberSlug}`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@destroy`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```

#### `GET /api/provider/team_members/{memberSlug}`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@show`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `PUT /api/provider/team_members/{memberSlug}`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@update`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/provider/team_members/{memberSlug}/status`

- Controller: `App\Http\Controllers\Teams\TeamMemberController@updateStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/provider/update_client_details/{client}`

- Controller: `App\Http\Controllers\Client\ClientController@updateClientProfile`
- Auth required: `yes`
- Form request: `App\Http\Requests\Client\UpdateClientProfileRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Client\\UpdateClientProfileRequest",
    "rules": {
        "first_name": "required|string|max:255",
        "last_name": "nullable|string|max:255",
        "email": [
            "required",
            "string",
            "email",
            "max:255",
            {}
        ],
        "phone_number": [
            "required",
            "string",
            "max:20",
            {}
        ],
        "gps_address": "required|string|max:255",
        "latitude": "nullable|numeric|between:-90,90",
        "longitude": "nullable|numeric|between:-180,180",
        "type": "nullable|string|max:255",
        "bin_slug": "nullable|string|max:255",
        "group_slug": "nullable|string|exists:groups,group_slug",
        "registration_fee": "sometimes|nullable|numeric|min:0",
        "profile_image": "nullable|starts_with:data:,http://,https://"
    }
}
```

#### `POST /api/provider/update_client_status`

- Controller: `App\Http\Controllers\Client\ClientController@updateStatus`
- Auth required: `yes`
- Form request: `App\Http\Requests\Client\StatusRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Client\\StatusRequest",
    "rules": {
        "status": [
            "required",
            "string",
            "in:pending,deactivate,activate",
            "bail"
        ],
        "client_slug": [
            "required",
            "string",
            "exists:clients,client_slug",
            "bail"
        ]
    }
}
```

#### `PUT /api/provider/update_complaint_status/{complaint}`

- Controller: `App\Http\Controllers\Complaint\ComplaintmanagementController@updateComplaintStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/provider/update_driver_details/{driver}`

- Controller: `App\Http\Controllers\Driver\DriverController@updateDriverProfile`
- Auth required: `yes`
- Form request: `App\Http\Requests\Driver\UpdateProfileRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Driver\\UpdateProfileRequest",
    "rules": {
        "first_name": "required|string|max:255",
        "middle_name": "nullable|string|max:255",
        "last_name": "nullable|string|max:255",
        "date_of_birth": "required|date",
        "id_card_type": "sometimes|string",
        "id_card_number": "sometimes|string",
        "license_class": "sometimes|string",
        "license_number": "sometimes|string",
        "license_date_issued": "required|date",
        "license_expiry_issued": "required|date",
        "email": [
            "required",
            "string",
            "email",
            "max:255",
            {}
        ],
        "phone_number": [
            "required",
            "string",
            "max:20",
            {}
        ],
        "address": "required|string|max:255",
        "license_front_image": "nullable|starts_with:data:,http://,https://",
        "license_back_image": "nullable|starts_with:data:,http://,https://",
        "profile_image": "nullable|starts_with:data:,http://,https://",
        "emergency_contact_name": "nullable|string|max:100",
        "emergency_phone_number": "nullable|string|max:100",
        "emergency_contract_address": "nullable|string|max:255"
    }
}
```

#### `POST /api/provider/update_driver_location`

- Controller: `App\Http\Controllers\Driver\DriverController@updateLocation`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/provider/update_driver_status`

- Controller: `App\Http\Controllers\Driver\DriverController@updateStatus`
- Auth required: `yes`
- Form request: `App\Http\Requests\Driver\StatusRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Driver\\StatusRequest",
    "rules": {
        "status": [
            "required",
            "string",
            "in:pending,deactivate,activate,on_leave",
            "bail"
        ],
        "driver_slug": [
            "required",
            "string",
            "exists:drivers,driver_slug",
            "bail"
        ]
    }
}
```

#### `PUT /api/provider/update_fleet_details/{fleet}`

- Controller: `App\Http\Controllers\Fleet\FleetManagementController@updateFleet`
- Auth required: `yes`
- Form request: `App\Http\Requests\Fleet\UpdateFleetRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Fleet\\UpdateFleetRequest",
    "rules": []
}
```

#### `POST /api/provider/update_fleet_status`

- Controller: `App\Http\Controllers\Fleet\FleetManagementController@updateStatus`
- Auth required: `yes`
- Form request: `App\Http\Requests\Fleet\FleetStatusUpdateRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Fleet\\FleetStatusUpdateRequest",
    "rules": {
        "fleet_slug": "required|string|exists:fleets,fleet_slug",
        "status": "required|string|in:active,inactive,maintenance"
    }
}
```

#### `PUT /api/provider/update_group_details/{group}`

- Controller: `App\Http\Controllers\Group\GroupController@updateGroup`
- Auth required: `yes`
- Form request: `App\Http\Requests\Group\GroupUpdation`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Group\\GroupUpdation",
    "rules": []
}
```

#### `POST /api/provider/update_group_status`

- Controller: `App\Http\Controllers\Group\GroupController@updateGroupStatus`
- Auth required: `yes`
- Form request: `App\Http\Requests\Group\GroupStatusUpdate`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Group\\GroupStatusUpdate",
    "rules": {
        "group_slug": "required|exists:groups,group_slug",
        "status": "required|in:active,revoke"
    }
}
```

#### `PUT /api/provider/update_plan_details/{plan}`

- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@updatePlan`
- Auth required: `yes`
- Form request: `App\Http\Requests\RoutePlanner\RouteDetailsUpdate`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\RoutePlanner\\RouteDetailsUpdate",
    "rules": {
        "client_slug": "sometimes|string|exists:clients,client_slug",
        "driver_slug": "sometimes|string|exists:drivers,driver_slug",
        "fleet_slug": "sometimes|string|exists:fleets,fleet_slug",
        "group_slug": "sometimes|string|exists:groups,group_slug",
        "status": "nullable|string|in:pending,completed,cancalled,progress"
    }
}
```

#### `POST /api/provider/update_plan_status`

- Controller: `App\Http\Controllers\RoutePlanner\RoutePlannerManagement@updateStatus`
- Auth required: `yes`
- Form request: `App\Http\Requests\RoutePlanner\RouteStatusUpdate`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\RoutePlanner\\RouteStatusUpdate",
    "rules": {
        "id": "required|string|exists:route_planners,id",
        "status": "nullable|string|in:pending,completed,cancalled,progress"
    }
}
```

#### `PUT /api/provider/update_product/{product}`

- Controller: `App\Http\Controllers\Product\ProductController@updateProduct`
- Auth required: `yes`
- Form request: `App\Http\Requests\Product\ProductUpdateRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Product\\ProductUpdateRequest",
    "rules": {
        "name": [
            "sometimes",
            "string"
        ],
        "category": [
            "sometimes",
            "nullable",
            "string",
            "max:255"
        ],
        "color": [
            "sometimes",
            "string"
        ],
        "size": [
            "sometimes",
            "string"
        ],
        "images": [
            "sometimes",
            "array"
        ],
        "images.*": [
            "sometimes",
            "nullable",
            "starts_with:data:,http://,https://"
        ],
        "original_price": [
            "sometimes",
            "numeric",
            "min:0"
        ],
        "discounted_price": [
            "sometimes",
            "nullable",
            "numeric",
            "min:0"
        ],
        "discount_percentage": [
            "sometimes",
            "nullable",
            "numeric",
            "min:0",
            "max:100"
        ],
        "quantity": [
            "sometimes",
            "integer",
            "min:0"
        ]
    }
}
```

#### `PUT /api/provider/update_profile/{provider}`

- Controller: `App\Http\Controllers\Provider\ProviderController@updateProfile`
- Auth required: `yes`
- Form request: `App\Http\Requests\Provider\UpdateProviderProfileRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Provider\\UpdateProviderProfileRequest",
    "rules": {
        "first_name": "required|string|max:255",
        "last_name": "nullable|string|max:255",
        "email": [
            "required",
            "string",
            "email",
            "max:255",
            {}
        ],
        "phone_number": [
            "required",
            "string",
            "max:20",
            {}
        ],
        "business_registration_number": [
            "required",
            "string",
            "max:100",
            {}
        ],
        "business_name": "nullable|string|max:255",
        "gps_address": "required|string|max:255",
        "district_assembly": "nullable|string|max:255",
        "business_certificate_image": "nullable|starts_with:data:,http://,https://",
        "district_assembly_contract_image": "nullable|starts_with:data:,http://,https://",
        "tax_certificate_image": "nullable|starts_with:data:,http://,https://",
        "epa_permit_image": "nullable|starts_with:data:,http://,https://",
        "region": "required|string|max:100",
        "location": "required|string|max:255",
        "profile_image": "nullable|starts_with:data:,http://,https://"
    }
}
```

#### `PUT /api/provider/update_violation/{violation}`

- Controller: `App\Http\Controllers\Violation\ViolationManagementController@providerUpdateViolation`
- Auth required: `yes`
- Form request: `App\Http\Requests\Violation\ViolationUpdateRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Violation\\ViolationUpdateRequest",
    "rules": {
        "type": [
            "sometimes",
            "string"
        ],
        "description": [
            "sometimes",
            "nullable",
            "string"
        ],
        "location": [
            "sometimes",
            "string"
        ],
        "status": [
            "sometimes",
            "string",
            "in:pending,open,in_progress,closed"
        ],
        "images": [
            "nullable"
        ],
        "images.*": [
            "nullable",
            "starts_with:data:,http://,https://"
        ]
    }
}
```

#### `PUT /api/provider/update_violation_status/{violation}`

- Controller: `App\Http\Controllers\Violation\ViolationManagementController@updateViolationStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `PUT /api/provider/update_weighbridge_record_details/{record}`

- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@updateRecord`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/provider/update_weighbridge_record_status`

- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@updateRecordStatus`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/provider/weighbridge_records`

- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@allRecords`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response: skipped (No token available for guard provider)

#### `POST /api/provider/weighbridge_records`

- Controller: `App\Http\Controllers\WeighBridge\WeighBridgeController@createRecord`
- Auth required: `yes`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

### Guard: `public`

#### `POST /api/admin/login`

- Controller: `App\Http\Controllers\Admin\AdminAuthenticationController@login`
- Auth required: `no`
- Request payload:
```json
{
    "source": "inline",
    "body": {
        "emailOrPhone": "string (email or phone)",
        "password": "string"
    }
}
```
- Sample response (HTTP 500)
```json
{
    "message": "Personal access client not found for 'admins' user provider. Please create one.",
    "exception": "RuntimeException",
    "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\ClientRepository.php",
    "line": 74,
    "trace": [
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\Bridge\\ClientRepository.php",
            "line": 48,
            "function": "personalAccessClient",
            "class": "Laravel\\Passport\\ClientRepository",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\Bridge\\PersonalAccessGrant.php",
            "line": 33,
            "function": "getPersonalAccessClientEntity",
            "class": "Laravel\\Passport\\Bridge\\ClientRepository",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\league\\oauth2-server\\src\\AuthorizationServer.php",
            "line": 176,
            "function": "respondToAccessTokenRequest",
            "class": "Laravel\\Passport\\Bridge\\PersonalAccessGrant",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\PersonalAccessTokenFactory.php",
            "line": 58,
            "function": "respondToAccessTokenRequest",
            "class": "League\\OAuth2\\Server\\AuthorizationServer",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\PersonalAccessTokenFactory.php",
            "line": 29,
            "function": "dispatchRequestToAuthorizationServer",
            "class": "Laravel\\Passport\\PersonalAccessTokenFactory",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\HasApiTokens.php",
            "line": 103,
            "function": "make",
            "class": "Laravel\\Passport\\PersonalAccessTokenFactory",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Traits\\Helpers.php",
            "line": 44,
            "function": "createToken",
            "class": "App\\Models\\Actor",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Http\\Controllers\\Admin\\AdminAuthenticationController.php",
            "line": 45,
            "function": "apiToken",
            "class": "App\\Http\\Controllers\\Controller",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Controller.php",
            "line": 54,
            "function": "login",
            "class": "App\\Http\\Controllers\\Admin\\AdminAuthenticationController",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\ControllerDispatcher.php",
            "line": 43,
            "function": "callAction",
            "class": "Illuminate\\Routing\\Controller",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Route.php",
            "line": 265,
            "function": "dispatch",
            "class": "Illuminate\\Routing\\ControllerDispatcher",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Route.php",
            "line": 211,
            "function": "runController",
            "class": "Illuminate\\Routing\\Route",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 822,
            "function": "run",
            "class": "Illuminate\\Routing\\Route",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 180,
            "function": "Illuminate\\Routing\\{closure}",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Middleware\\SubstituteBindings.php",
            "line": 50,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Routing\\Middleware\\SubstituteBindings",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 137,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 821,
            "function": "then",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 800,
            "function": "runRouteWithinStack",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 764,
            "function": "runRoute",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 753,
            "function": "dispatchToRoute",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
            "line": 200,
            "function": "dispatch",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 180,
            "function": "Illuminate\\Foundation\\Http\\{closure}",
            "class": "Illuminate\\Foundation\\Http\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Http\\Middleware\\CrossOrigin.php",
            "line": 17,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "App\\Http\\Middleware\\CrossOrigin",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Http\\Middleware\\ForceJsonResponse.php",
            "line": 18,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "App\\Http\\Middleware\\ForceJsonResponse",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php",
            "line": 21,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php",
            "line": 21,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\TrimStrings",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePostSize.php",
            "line": 27,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance.php",
            "line": 109,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\HandleCors.php",
            "line": 61,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\HandleCors",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\TrustProxies.php",
            "line": 58,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\TrustProxies",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePathEncoding.php",
            "line": 26,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\ValidatePathEncoding",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 137,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
            "line": 175,
            "function": "then",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
            "line": 144,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\\Foundation\\Http\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 459,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 421,
            "function": "dispatchRequest",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 371,
            "function": "loginSample",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 42,
            "function": "captureSampleResponse",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Console\\Commands\\GenerateFrontendApiDocs.php",
            "line": 18,
            "function": "generate",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php",
            "line": 36,
            "function": "handle",
            "class": "App\\Console\\Commands\\GenerateFrontendApiDocs",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Util.php",
            "line": 43,
            "function": "Illuminate\\Container\\{closure}",
            "class": "Illuminate\\Container\\BoundMethod",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php",
            "line": 96,
            "function": "unwrapIfClosure",
            "class": "Illuminate\\Container\\Util",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php",
            "line": 35,
            "function": "callBoundMethod",
            "class": "Illuminate\\Container\\BoundMethod",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php",
            "line": 836,
            "function": "call",
            "class": "Illuminate\\Container\\BoundMethod",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php",
            "line": 211,
            "function": "call",
            "class": "Illuminate\\Container\\Container",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Command\\Command.php",
            "line": 318,
            "function": "execute",
            "class": "Illuminate\\Console\\Command",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php",
            "line": 180,
            "function": "run",
            "class": "Symfony\\Component\\Console\\Command\\Command",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Application.php",
            "line": 1073,
            "function": "run",
            "class": "Illuminate\\Console\\Command",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Application.php",
            "line": 356,
            "function": "doRunCommand",
            "class": "Symfony\\Component\\Console\\Application",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Application.php",
            "line": 195,
            "function": "doRun",
            "class": "Symfony\\Component\\Console\\Application",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Console\\Kernel.php",
            "line": 197,
            "function": "run",
            "class": "Symfony\\Component\\Console\\Application",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php",
            "line": 1235,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Console\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\artisan",
            "line": 16,
            "function": "handleCommand",
            "class": "Illuminate\\Foundation\\Application",
            "type": "->"
        }
    ]
}
```

#### `POST /api/admin/register_admin`

- Controller: `App\Http\Controllers\Admin\AdminController@register`
- Auth required: `no`
- Form request: `App\Http\Requests\Admin\RegisterRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Admin\\RegisterRequest",
    "rules": {
        "first_name": "required|string",
        "last_name": "nullable|string",
        "email": "required|string|email|unique:admins,email",
        "phone_number": "required|string|unique:admins,phone_number",
        "profile_image": "nullable|starts_with:data:,http://,https://"
    }
}
```

#### `POST /api/admin/resend_verificationCode`

- Controller: `App\Http\Controllers\Admin\AdminPasswordController@sendResetPasswordNotification`
- Auth required: `no`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/admin/reset_password`

- Controller: `App\Http\Controllers\Admin\AdminPasswordController@resetPassword`
- Auth required: `no`
- Form request: `App\Http\Requests\Admin\PasswordResetRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Admin\\PasswordResetRequest",
    "rules": {
        "password": [
            "required",
            {},
            "bail"
        ],
        "admin_slug": [
            "required",
            "string",
            "exists:admins,admin_slug"
        ],
        "otp": [
            "required"
        ]
    }
}
```

#### `POST /api/admin/reset_password_notification`

- Controller: `App\Http\Controllers\Admin\AdminPasswordController@sendResetPasswordNotification`
- Auth required: `no`
- Request payload:
```json
{
    "source": "inline",
    "body": {
        "emailOrPhone": "string (email or phone)"
    }
}
```

#### `POST /api/client/login`

- Controller: `App\Http\Controllers\Client\ClientAuthenticationController@login`
- Auth required: `no`
- Request payload:
```json
{
    "source": "inline",
    "body": {
        "emailOrPhone": "string (email or phone)",
        "password": "string"
    }
}
```
- Sample response (HTTP 500)
```json
{
    "message": "Personal access client not found for 'clients' user provider. Please create one.",
    "exception": "RuntimeException",
    "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\ClientRepository.php",
    "line": 74,
    "trace": [
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\Bridge\\ClientRepository.php",
            "line": 48,
            "function": "personalAccessClient",
            "class": "Laravel\\Passport\\ClientRepository",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\Bridge\\PersonalAccessGrant.php",
            "line": 33,
            "function": "getPersonalAccessClientEntity",
            "class": "Laravel\\Passport\\Bridge\\ClientRepository",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\league\\oauth2-server\\src\\AuthorizationServer.php",
            "line": 176,
            "function": "respondToAccessTokenRequest",
            "class": "Laravel\\Passport\\Bridge\\PersonalAccessGrant",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\PersonalAccessTokenFactory.php",
            "line": 58,
            "function": "respondToAccessTokenRequest",
            "class": "League\\OAuth2\\Server\\AuthorizationServer",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\PersonalAccessTokenFactory.php",
            "line": 29,
            "function": "dispatchRequestToAuthorizationServer",
            "class": "Laravel\\Passport\\PersonalAccessTokenFactory",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\HasApiTokens.php",
            "line": 103,
            "function": "make",
            "class": "Laravel\\Passport\\PersonalAccessTokenFactory",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Traits\\Helpers.php",
            "line": 44,
            "function": "createToken",
            "class": "App\\Models\\Actor",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Http\\Controllers\\Client\\ClientAuthenticationController.php",
            "line": 30,
            "function": "apiToken",
            "class": "App\\Http\\Controllers\\Controller",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Controller.php",
            "line": 54,
            "function": "login",
            "class": "App\\Http\\Controllers\\Client\\ClientAuthenticationController",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\ControllerDispatcher.php",
            "line": 43,
            "function": "callAction",
            "class": "Illuminate\\Routing\\Controller",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Route.php",
            "line": 265,
            "function": "dispatch",
            "class": "Illuminate\\Routing\\ControllerDispatcher",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Route.php",
            "line": 211,
            "function": "runController",
            "class": "Illuminate\\Routing\\Route",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 822,
            "function": "run",
            "class": "Illuminate\\Routing\\Route",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 180,
            "function": "Illuminate\\Routing\\{closure}",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Middleware\\SubstituteBindings.php",
            "line": 50,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Routing\\Middleware\\SubstituteBindings",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 137,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 821,
            "function": "then",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 800,
            "function": "runRouteWithinStack",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 764,
            "function": "runRoute",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 753,
            "function": "dispatchToRoute",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
            "line": 200,
            "function": "dispatch",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 180,
            "function": "Illuminate\\Foundation\\Http\\{closure}",
            "class": "Illuminate\\Foundation\\Http\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Http\\Middleware\\CrossOrigin.php",
            "line": 17,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "App\\Http\\Middleware\\CrossOrigin",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Http\\Middleware\\ForceJsonResponse.php",
            "line": 18,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "App\\Http\\Middleware\\ForceJsonResponse",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php",
            "line": 21,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php",
            "line": 21,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\TrimStrings",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePostSize.php",
            "line": 27,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance.php",
            "line": 109,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\HandleCors.php",
            "line": 61,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\HandleCors",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\TrustProxies.php",
            "line": 58,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\TrustProxies",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePathEncoding.php",
            "line": 26,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\ValidatePathEncoding",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 137,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
            "line": 175,
            "function": "then",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
            "line": 144,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\\Foundation\\Http\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 459,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 421,
            "function": "dispatchRequest",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 371,
            "function": "loginSample",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 42,
            "function": "captureSampleResponse",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Console\\Commands\\GenerateFrontendApiDocs.php",
            "line": 18,
            "function": "generate",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php",
            "line": 36,
            "function": "handle",
            "class": "App\\Console\\Commands\\GenerateFrontendApiDocs",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Util.php",
            "line": 43,
            "function": "Illuminate\\Container\\{closure}",
            "class": "Illuminate\\Container\\BoundMethod",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php",
            "line": 96,
            "function": "unwrapIfClosure",
            "class": "Illuminate\\Container\\Util",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php",
            "line": 35,
            "function": "callBoundMethod",
            "class": "Illuminate\\Container\\BoundMethod",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php",
            "line": 836,
            "function": "call",
            "class": "Illuminate\\Container\\BoundMethod",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php",
            "line": 211,
            "function": "call",
            "class": "Illuminate\\Container\\Container",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Command\\Command.php",
            "line": 318,
            "function": "execute",
            "class": "Illuminate\\Console\\Command",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php",
            "line": 180,
            "function": "run",
            "class": "Symfony\\Component\\Console\\Command\\Command",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Application.php",
            "line": 1073,
            "function": "run",
            "class": "Illuminate\\Console\\Command",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Application.php",
            "line": 356,
            "function": "doRunCommand",
            "class": "Symfony\\Component\\Console\\Application",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Application.php",
            "line": 195,
            "function": "doRun",
            "class": "Symfony\\Component\\Console\\Application",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Console\\Kernel.php",
            "line": 197,
            "function": "run",
            "class": "Symfony\\Component\\Console\\Application",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php",
            "line": 1235,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Console\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\artisan",
            "line": 16,
            "function": "handleCommand",
            "class": "Illuminate\\Foundation\\Application",
            "type": "->"
        }
    ]
}
```

#### `POST /api/client/resend_verificationCode`

- Controller: `App\Http\Controllers\Client\ClientPasswordController@sendResetPasswordNotification`
- Auth required: `no`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/client/reset_password`

- Controller: `App\Http\Controllers\Client\ClientPasswordController@resetPassword`
- Auth required: `no`
- Form request: `App\Http\Requests\Client\PasswordResetRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Client\\PasswordResetRequest",
    "rules": {
        "password": [
            "required",
            {},
            "bail"
        ],
        "client_slug": [
            "required",
            "alpha_dash"
        ],
        "otp": [
            "required"
        ]
    }
}
```

#### `POST /api/client/reset_password_notification`

- Controller: `App\Http\Controllers\Client\ClientPasswordController@sendResetPasswordNotification`
- Auth required: `no`
- Request payload:
```json
{
    "source": "inline",
    "body": {
        "emailOrPhone": "string (email or phone)"
    }
}
```

#### `POST /api/district_assembly/login`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssembleyAuthenticationController@login`
- Auth required: `no`
- Request payload:
```json
{
    "source": "inline",
    "body": {
        "emailOrPhone": "string (email or phone)",
        "password": "string"
    }
}
```
- Sample response (HTTP 500)
```json
{
    "message": "Personal access client not found for 'district_assemblies' user provider. Please create one.",
    "exception": "RuntimeException",
    "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\ClientRepository.php",
    "line": 74,
    "trace": [
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\Bridge\\ClientRepository.php",
            "line": 48,
            "function": "personalAccessClient",
            "class": "Laravel\\Passport\\ClientRepository",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\Bridge\\PersonalAccessGrant.php",
            "line": 33,
            "function": "getPersonalAccessClientEntity",
            "class": "Laravel\\Passport\\Bridge\\ClientRepository",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\league\\oauth2-server\\src\\AuthorizationServer.php",
            "line": 176,
            "function": "respondToAccessTokenRequest",
            "class": "Laravel\\Passport\\Bridge\\PersonalAccessGrant",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\PersonalAccessTokenFactory.php",
            "line": 58,
            "function": "respondToAccessTokenRequest",
            "class": "League\\OAuth2\\Server\\AuthorizationServer",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\PersonalAccessTokenFactory.php",
            "line": 29,
            "function": "dispatchRequestToAuthorizationServer",
            "class": "Laravel\\Passport\\PersonalAccessTokenFactory",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\HasApiTokens.php",
            "line": 103,
            "function": "make",
            "class": "Laravel\\Passport\\PersonalAccessTokenFactory",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Traits\\Helpers.php",
            "line": 44,
            "function": "createToken",
            "class": "App\\Models\\Actor",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Http\\Controllers\\DistrictAssembley\\DistrictAssembleyAuthenticationController.php",
            "line": 57,
            "function": "apiToken",
            "class": "App\\Http\\Controllers\\Controller",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Controller.php",
            "line": 54,
            "function": "login",
            "class": "App\\Http\\Controllers\\DistrictAssembley\\DistrictAssembleyAuthenticationController",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\ControllerDispatcher.php",
            "line": 43,
            "function": "callAction",
            "class": "Illuminate\\Routing\\Controller",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Route.php",
            "line": 265,
            "function": "dispatch",
            "class": "Illuminate\\Routing\\ControllerDispatcher",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Route.php",
            "line": 211,
            "function": "runController",
            "class": "Illuminate\\Routing\\Route",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 822,
            "function": "run",
            "class": "Illuminate\\Routing\\Route",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 180,
            "function": "Illuminate\\Routing\\{closure}",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Middleware\\SubstituteBindings.php",
            "line": 50,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Routing\\Middleware\\SubstituteBindings",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 137,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 821,
            "function": "then",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 800,
            "function": "runRouteWithinStack",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 764,
            "function": "runRoute",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 753,
            "function": "dispatchToRoute",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
            "line": 200,
            "function": "dispatch",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 180,
            "function": "Illuminate\\Foundation\\Http\\{closure}",
            "class": "Illuminate\\Foundation\\Http\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Http\\Middleware\\CrossOrigin.php",
            "line": 17,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "App\\Http\\Middleware\\CrossOrigin",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Http\\Middleware\\ForceJsonResponse.php",
            "line": 18,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "App\\Http\\Middleware\\ForceJsonResponse",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php",
            "line": 21,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php",
            "line": 21,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\TrimStrings",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePostSize.php",
            "line": 27,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance.php",
            "line": 109,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\HandleCors.php",
            "line": 61,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\HandleCors",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\TrustProxies.php",
            "line": 58,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\TrustProxies",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePathEncoding.php",
            "line": 26,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\ValidatePathEncoding",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 137,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
            "line": 175,
            "function": "then",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
            "line": 144,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\\Foundation\\Http\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 459,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 421,
            "function": "dispatchRequest",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 371,
            "function": "loginSample",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 42,
            "function": "captureSampleResponse",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Console\\Commands\\GenerateFrontendApiDocs.php",
            "line": 18,
            "function": "generate",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php",
            "line": 36,
            "function": "handle",
            "class": "App\\Console\\Commands\\GenerateFrontendApiDocs",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Util.php",
            "line": 43,
            "function": "Illuminate\\Container\\{closure}",
            "class": "Illuminate\\Container\\BoundMethod",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php",
            "line": 96,
            "function": "unwrapIfClosure",
            "class": "Illuminate\\Container\\Util",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php",
            "line": 35,
            "function": "callBoundMethod",
            "class": "Illuminate\\Container\\BoundMethod",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php",
            "line": 836,
            "function": "call",
            "class": "Illuminate\\Container\\BoundMethod",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php",
            "line": 211,
            "function": "call",
            "class": "Illuminate\\Container\\Container",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Command\\Command.php",
            "line": 318,
            "function": "execute",
            "class": "Illuminate\\Console\\Command",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php",
            "line": 180,
            "function": "run",
            "class": "Symfony\\Component\\Console\\Command\\Command",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Application.php",
            "line": 1073,
            "function": "run",
            "class": "Illuminate\\Console\\Command",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Application.php",
            "line": 356,
            "function": "doRunCommand",
            "class": "Symfony\\Component\\Console\\Application",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Application.php",
            "line": 195,
            "function": "doRun",
            "class": "Symfony\\Component\\Console\\Application",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Console\\Kernel.php",
            "line": 197,
            "function": "run",
            "class": "Symfony\\Component\\Console\\Application",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php",
            "line": 1235,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Console\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\artisan",
            "line": 16,
            "function": "handleCommand",
            "class": "Illuminate\\Foundation\\Application",
            "type": "->"
        }
    ]
}
```

#### `POST /api/district_assembly/resend_verificationCode`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssembleyPasswordController@sendResetPasswordNotification`
- Auth required: `no`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/district_assembly/reset_password`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssembleyPasswordController@resetPassword`
- Auth required: `no`
- Form request: `App\Http\Requests\DistrictAssembley\PasswordResetRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\DistrictAssembley\\PasswordResetRequest",
    "rules": {
        "password": [
            "required",
            {},
            "bail"
        ],
        "district_assembly_slug": [
            "required",
            "alpha_dash"
        ],
        "otp": [
            "required"
        ]
    }
}
```

#### `POST /api/district_assembly/reset_password_notification`

- Controller: `App\Http\Controllers\DistrictAssembley\DistrictAssembleyPasswordController@sendResetPasswordNotification`
- Auth required: `no`
- Request payload:
```json
{
    "source": "inline",
    "body": {
        "emailOrPhone": "string (email or phone)"
    }
}
```

#### `POST /api/facility/login`

- Controller: `App\Http\Controllers\Facility\FacilityAuthenticationController@login`
- Auth required: `no`
- Request payload:
```json
{
    "source": "inline",
    "body": {
        "emailOrPhone": "string (email or phone)",
        "password": "string"
    }
}
```
- Sample response (HTTP 500)
```json
{
    "message": "Personal access client not found for 'facilities' user provider. Please create one.",
    "exception": "RuntimeException",
    "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\ClientRepository.php",
    "line": 74,
    "trace": [
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\Bridge\\ClientRepository.php",
            "line": 48,
            "function": "personalAccessClient",
            "class": "Laravel\\Passport\\ClientRepository",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\Bridge\\PersonalAccessGrant.php",
            "line": 33,
            "function": "getPersonalAccessClientEntity",
            "class": "Laravel\\Passport\\Bridge\\ClientRepository",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\league\\oauth2-server\\src\\AuthorizationServer.php",
            "line": 176,
            "function": "respondToAccessTokenRequest",
            "class": "Laravel\\Passport\\Bridge\\PersonalAccessGrant",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\PersonalAccessTokenFactory.php",
            "line": 58,
            "function": "respondToAccessTokenRequest",
            "class": "League\\OAuth2\\Server\\AuthorizationServer",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\PersonalAccessTokenFactory.php",
            "line": 29,
            "function": "dispatchRequestToAuthorizationServer",
            "class": "Laravel\\Passport\\PersonalAccessTokenFactory",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\HasApiTokens.php",
            "line": 103,
            "function": "make",
            "class": "Laravel\\Passport\\PersonalAccessTokenFactory",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Traits\\Helpers.php",
            "line": 44,
            "function": "createToken",
            "class": "App\\Models\\Actor",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Http\\Controllers\\Facility\\FacilityAuthenticationController.php",
            "line": 57,
            "function": "apiToken",
            "class": "App\\Http\\Controllers\\Controller",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Controller.php",
            "line": 54,
            "function": "login",
            "class": "App\\Http\\Controllers\\Facility\\FacilityAuthenticationController",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\ControllerDispatcher.php",
            "line": 43,
            "function": "callAction",
            "class": "Illuminate\\Routing\\Controller",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Route.php",
            "line": 265,
            "function": "dispatch",
            "class": "Illuminate\\Routing\\ControllerDispatcher",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Route.php",
            "line": 211,
            "function": "runController",
            "class": "Illuminate\\Routing\\Route",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 822,
            "function": "run",
            "class": "Illuminate\\Routing\\Route",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 180,
            "function": "Illuminate\\Routing\\{closure}",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Middleware\\SubstituteBindings.php",
            "line": 50,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Routing\\Middleware\\SubstituteBindings",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 137,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 821,
            "function": "then",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 800,
            "function": "runRouteWithinStack",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 764,
            "function": "runRoute",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 753,
            "function": "dispatchToRoute",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
            "line": 200,
            "function": "dispatch",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 180,
            "function": "Illuminate\\Foundation\\Http\\{closure}",
            "class": "Illuminate\\Foundation\\Http\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Http\\Middleware\\CrossOrigin.php",
            "line": 17,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "App\\Http\\Middleware\\CrossOrigin",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Http\\Middleware\\ForceJsonResponse.php",
            "line": 18,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "App\\Http\\Middleware\\ForceJsonResponse",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php",
            "line": 21,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php",
            "line": 21,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\TrimStrings",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePostSize.php",
            "line": 27,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance.php",
            "line": 109,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\HandleCors.php",
            "line": 61,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\HandleCors",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\TrustProxies.php",
            "line": 58,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\TrustProxies",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePathEncoding.php",
            "line": 26,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\ValidatePathEncoding",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 137,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
            "line": 175,
            "function": "then",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
            "line": 144,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\\Foundation\\Http\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 459,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 421,
            "function": "dispatchRequest",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 371,
            "function": "loginSample",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 42,
            "function": "captureSampleResponse",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Console\\Commands\\GenerateFrontendApiDocs.php",
            "line": 18,
            "function": "generate",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php",
            "line": 36,
            "function": "handle",
            "class": "App\\Console\\Commands\\GenerateFrontendApiDocs",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Util.php",
            "line": 43,
            "function": "Illuminate\\Container\\{closure}",
            "class": "Illuminate\\Container\\BoundMethod",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php",
            "line": 96,
            "function": "unwrapIfClosure",
            "class": "Illuminate\\Container\\Util",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php",
            "line": 35,
            "function": "callBoundMethod",
            "class": "Illuminate\\Container\\BoundMethod",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php",
            "line": 836,
            "function": "call",
            "class": "Illuminate\\Container\\BoundMethod",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php",
            "line": 211,
            "function": "call",
            "class": "Illuminate\\Container\\Container",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Command\\Command.php",
            "line": 318,
            "function": "execute",
            "class": "Illuminate\\Console\\Command",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php",
            "line": 180,
            "function": "run",
            "class": "Symfony\\Component\\Console\\Command\\Command",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Application.php",
            "line": 1073,
            "function": "run",
            "class": "Illuminate\\Console\\Command",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Application.php",
            "line": 356,
            "function": "doRunCommand",
            "class": "Symfony\\Component\\Console\\Application",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Application.php",
            "line": 195,
            "function": "doRun",
            "class": "Symfony\\Component\\Console\\Application",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Console\\Kernel.php",
            "line": 197,
            "function": "run",
            "class": "Symfony\\Component\\Console\\Application",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php",
            "line": 1235,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Console\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\artisan",
            "line": 16,
            "function": "handleCommand",
            "class": "Illuminate\\Foundation\\Application",
            "type": "->"
        }
    ]
}
```

#### `POST /api/facility/resend_verificationCode`

- Controller: `App\Http\Controllers\Facility\FacilityPasswordController@sendResetPasswordNotification`
- Auth required: `no`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/facility/reset_password`

- Controller: `App\Http\Controllers\Facility\FacilityPasswordController@resetPassword`
- Auth required: `no`
- Form request: `App\Http\Requests\Facility\FacilityPasswordResetRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Facility\\FacilityPasswordResetRequest",
    "rules": {
        "password": [
            "required",
            {},
            "bail"
        ],
        "facility_slug": [
            "required",
            "alpha_dash"
        ],
        "otp": [
            "required"
        ]
    }
}
```

#### `POST /api/facility/reset_password_notification`

- Controller: `App\Http\Controllers\Facility\FacilityPasswordController@sendResetPasswordNotification`
- Auth required: `no`
- Request payload:
```json
{
    "source": "inline",
    "body": {
        "emailOrPhone": "string (email or phone)"
    }
}
```

#### `POST /api/payment_callback`

- Controller: `App\Http\Controllers\Payment\CalPayCallbackController@handle`
- Auth required: `no`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/payments/calpay/initiate-registration`

- Controller: `App\Http\Controllers\Payment\CalPayPaymentController@initiateRegistration`
- Auth required: `no`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/provider/login`

- Controller: `App\Http\Controllers\Provider\ProviderAuthenticationController@login`
- Auth required: `no`
- Request payload:
```json
{
    "source": "inline",
    "body": {
        "emailOrPhone": "string (email or phone)",
        "password": "string"
    }
}
```
- Sample response (HTTP 500)
```json
{
    "message": "Personal access client not found for 'providers' user provider. Please create one.",
    "exception": "RuntimeException",
    "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\ClientRepository.php",
    "line": 74,
    "trace": [
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\Bridge\\ClientRepository.php",
            "line": 48,
            "function": "personalAccessClient",
            "class": "Laravel\\Passport\\ClientRepository",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\Bridge\\PersonalAccessGrant.php",
            "line": 33,
            "function": "getPersonalAccessClientEntity",
            "class": "Laravel\\Passport\\Bridge\\ClientRepository",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\league\\oauth2-server\\src\\AuthorizationServer.php",
            "line": 176,
            "function": "respondToAccessTokenRequest",
            "class": "Laravel\\Passport\\Bridge\\PersonalAccessGrant",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\PersonalAccessTokenFactory.php",
            "line": 58,
            "function": "respondToAccessTokenRequest",
            "class": "League\\OAuth2\\Server\\AuthorizationServer",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\PersonalAccessTokenFactory.php",
            "line": 29,
            "function": "dispatchRequestToAuthorizationServer",
            "class": "Laravel\\Passport\\PersonalAccessTokenFactory",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\passport\\src\\HasApiTokens.php",
            "line": 103,
            "function": "make",
            "class": "Laravel\\Passport\\PersonalAccessTokenFactory",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Traits\\Helpers.php",
            "line": 44,
            "function": "createToken",
            "class": "App\\Models\\Actor",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Http\\Controllers\\Provider\\ProviderAuthenticationController.php",
            "line": 57,
            "function": "apiToken",
            "class": "App\\Http\\Controllers\\Controller",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Controller.php",
            "line": 54,
            "function": "login",
            "class": "App\\Http\\Controllers\\Provider\\ProviderAuthenticationController",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\ControllerDispatcher.php",
            "line": 43,
            "function": "callAction",
            "class": "Illuminate\\Routing\\Controller",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Route.php",
            "line": 265,
            "function": "dispatch",
            "class": "Illuminate\\Routing\\ControllerDispatcher",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Route.php",
            "line": 211,
            "function": "runController",
            "class": "Illuminate\\Routing\\Route",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 822,
            "function": "run",
            "class": "Illuminate\\Routing\\Route",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 180,
            "function": "Illuminate\\Routing\\{closure}",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Middleware\\SubstituteBindings.php",
            "line": 50,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Routing\\Middleware\\SubstituteBindings",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 137,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 821,
            "function": "then",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 800,
            "function": "runRouteWithinStack",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 764,
            "function": "runRoute",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php",
            "line": 753,
            "function": "dispatchToRoute",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
            "line": 200,
            "function": "dispatch",
            "class": "Illuminate\\Routing\\Router",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 180,
            "function": "Illuminate\\Foundation\\Http\\{closure}",
            "class": "Illuminate\\Foundation\\Http\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Http\\Middleware\\CrossOrigin.php",
            "line": 17,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "App\\Http\\Middleware\\CrossOrigin",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Http\\Middleware\\ForceJsonResponse.php",
            "line": 18,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "App\\Http\\Middleware\\ForceJsonResponse",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php",
            "line": 21,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php",
            "line": 21,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\TrimStrings",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePostSize.php",
            "line": 27,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance.php",
            "line": 109,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\HandleCors.php",
            "line": 61,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\HandleCors",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\TrustProxies.php",
            "line": 58,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\TrustProxies",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePathEncoding.php",
            "line": 26,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 219,
            "function": "handle",
            "class": "Illuminate\\Http\\Middleware\\ValidatePathEncoding",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php",
            "line": 137,
            "function": "Illuminate\\Pipeline\\{closure}",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
            "line": 175,
            "function": "then",
            "class": "Illuminate\\Pipeline\\Pipeline",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php",
            "line": 144,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\\Foundation\\Http\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 459,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Http\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 421,
            "function": "dispatchRequest",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 371,
            "function": "loginSample",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Services\\FrontendApiDocumentationService.php",
            "line": 42,
            "function": "captureSampleResponse",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\app\\Console\\Commands\\GenerateFrontendApiDocs.php",
            "line": 18,
            "function": "generate",
            "class": "App\\Services\\FrontendApiDocumentationService",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php",
            "line": 36,
            "function": "handle",
            "class": "App\\Console\\Commands\\GenerateFrontendApiDocs",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Util.php",
            "line": 43,
            "function": "Illuminate\\Container\\{closure}",
            "class": "Illuminate\\Container\\BoundMethod",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php",
            "line": 96,
            "function": "unwrapIfClosure",
            "class": "Illuminate\\Container\\Util",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php",
            "line": 35,
            "function": "callBoundMethod",
            "class": "Illuminate\\Container\\BoundMethod",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php",
            "line": 836,
            "function": "call",
            "class": "Illuminate\\Container\\BoundMethod",
            "type": "::"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php",
            "line": 211,
            "function": "call",
            "class": "Illuminate\\Container\\Container",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Command\\Command.php",
            "line": 318,
            "function": "execute",
            "class": "Illuminate\\Console\\Command",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php",
            "line": 180,
            "function": "run",
            "class": "Symfony\\Component\\Console\\Command\\Command",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Application.php",
            "line": 1073,
            "function": "run",
            "class": "Illuminate\\Console\\Command",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Application.php",
            "line": 356,
            "function": "doRunCommand",
            "class": "Symfony\\Component\\Console\\Application",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\symfony\\console\\Application.php",
            "line": 195,
            "function": "doRun",
            "class": "Symfony\\Component\\Console\\Application",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Console\\Kernel.php",
            "line": 197,
            "function": "run",
            "class": "Symfony\\Component\\Console\\Application",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php",
            "line": 1235,
            "function": "handle",
            "class": "Illuminate\\Foundation\\Console\\Kernel",
            "type": "->"
        },
        {
            "file": "C:\\laragon\\www\\workspace\\personal\\laravel-backend\\backend\\waste-managment-BE\\artisan",
            "line": 16,
            "function": "handleCommand",
            "class": "Illuminate\\Foundation\\Application",
            "type": "->"
        }
    ]
}
```

#### `POST /api/provider/resend_verificationCode`

- Controller: `App\Http\Controllers\Provider\ProviderPasswordController@sendVerificationNotification`
- Auth required: `no`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `POST /api/provider/reset_password`

- Controller: `App\Http\Controllers\Provider\ProviderPasswordController@resetPassword`
- Auth required: `no`
- Form request: `App\Http\Requests\Provider\ProviderPasswordResetRequest`
- Request payload:
```json
{
    "source": "form_request",
    "class": "App\\Http\\Requests\\Provider\\ProviderPasswordResetRequest",
    "rules": {
        "password": [
            "required",
            {},
            "bail"
        ],
        "provider_slug": [
            "required",
            "alpha_dash"
        ],
        "otp": [
            "required"
        ]
    }
}
```

#### `POST /api/provider/reset_password_notification`

- Controller: `App\Http\Controllers\Provider\ProviderPasswordController@sendResetPasswordNotification`
- Auth required: `no`
- Request payload:
```json
{
    "source": "inline",
    "body": {
        "emailOrPhone": "string (email or phone)"
    }
}
```

#### `POST /api/provider/verify_account`

- Controller: `App\Http\Controllers\Provider\ProviderPasswordController@verifyAccount`
- Auth required: `no`
- Request payload:
```json
{
    "source": "controller_inline",
    "note": "Validated inline in controller \u2014 see controller action"
}
```

#### `GET /api/yes`

- Controller: `Closure@handle`
- Auth required: `no`
- Request payload:
```json
{
    "source": "none",
    "body": null
}
```
- Sample response (HTTP 200)
```json
"yes yes"
```

## All Form Request validation rules

### `App\Http\Requests\Admin\PasswordChangeRequest`
```json
{
    "old_password": [
        "current_password:admin"
    ],
    "password": [
        "required",
        {},
        "confirmed",
        "bail"
    ]
}
```

### `App\Http\Requests\Admin\PasswordResetRequest`
```json
{
    "password": [
        "required",
        {},
        "bail"
    ],
    "admin_slug": [
        "required",
        "string",
        "exists:admins,admin_slug"
    ],
    "otp": [
        "required"
    ]
}
```

### `App\Http\Requests\Admin\RegisterRequest`
```json
{
    "first_name": "required|string",
    "last_name": "nullable|string",
    "email": "required|string|email|unique:admins,email",
    "phone_number": "required|string|unique:admins,phone_number",
    "profile_image": "nullable|starts_with:data:,http://,https://"
}
```

### `App\Http\Requests\Client\PasswordChangeRequest`
```json
{
    "old_password": [
        "current_password:client"
    ],
    "password": [
        "required",
        {},
        "confirmed",
        "bail"
    ]
}
```

### `App\Http\Requests\Client\PasswordResetRequest`
```json
{
    "password": [
        "required",
        {},
        "bail"
    ],
    "client_slug": [
        "required",
        "alpha_dash"
    ],
    "otp": [
        "required"
    ]
}
```

### `App\Http\Requests\Client\RegisterRequest`
```json
{
    "first_name": "required|string",
    "last_name": "nullable|string",
    "email": "required|string|email|unique:clients,email",
    "phone_number": "required|string|unique:clients,phone_number",
    "gps_address": "required|string",
    "latitude": "nullable|numeric|between:-90,90",
    "longitude": "nullable|numeric|between:-180,180",
    "type": "required|string",
    "bin_slug": "nullable|string",
    "group_slug": "nullable|string|exists:groups,group_slug",
    "registration_fee": "nullable|numeric|min:0",
    "registration_status": "nullable|boolean",
    "profile_image": "nullable|starts_with:data:,http://,https://"
}
```

### `App\Http\Requests\Client\StatusRequest`
```json
{
    "status": [
        "required",
        "string",
        "in:pending,deactivate,activate",
        "bail"
    ],
    "client_slug": [
        "required",
        "string",
        "exists:clients,client_slug",
        "bail"
    ]
}
```

### `App\Http\Requests\Client\UpdateClientProfileRequest`
```json
{
    "first_name": "required|string|max:255",
    "last_name": "nullable|string|max:255",
    "email": [
        "required",
        "string",
        "email",
        "max:255",
        {}
    ],
    "phone_number": [
        "required",
        "string",
        "max:20",
        {}
    ],
    "gps_address": "required|string|max:255",
    "latitude": "nullable|numeric|between:-90,90",
    "longitude": "nullable|numeric|between:-180,180",
    "type": "nullable|string|max:255",
    "bin_slug": "nullable|string|max:255",
    "group_slug": "nullable|string|exists:groups,group_slug",
    "registration_fee": "sometimes|nullable|numeric|min:0",
    "profile_image": "nullable|starts_with:data:,http://,https://"
}
```

### `App\Http\Requests\Complaint\ComplaintCreationRequest`
```json
{
    "location": [
        "required",
        "string"
    ],
    "description": [
        "nullable",
        "string"
    ],
    "images": [
        "nullable",
        "array"
    ],
    "images.*": [
        "nullable",
        "string"
    ]
}
```

### `App\Http\Requests\Complaint\ComplaintUpdateRequest`
```json
{
    "location": [
        "sometimes",
        "string"
    ],
    "description": [
        "sometimes",
        "nullable",
        "string"
    ],
    "status": [
        "sometimes",
        "string",
        "in:pending,open,in_progress,closed"
    ],
    "images": [
        "sometimes",
        "nullable",
        "array"
    ],
    "images.*": [
        "nullable",
        "string"
    ]
}
```

### `App\Http\Requests\Complaint\CreationRequest`
```json
{
    "status": [
        "required",
        "string",
        "in:pending,deactivate,active",
        "bail"
    ],
    "district_assembly_slug": [
        "required",
        "string",
        "exists:district_assemblies,district_assembly_slug",
        "bail"
    ]
}
```

### `App\Http\Requests\DistrictAssembley\AccountStatusRequest`
```json
{
    "status": [
        "required",
        "string",
        "in:pending,deactivate,active",
        "bail"
    ],
    "district_assembly_slug": [
        "required",
        "string",
        "exists:district_assemblies,district_assembly_slug",
        "bail"
    ],
    "suspension_reason": [
        "nullable",
        "string",
        "max:1000"
    ],
    "corrective_action": [
        "nullable",
        "string",
        "max:1000"
    ]
}
```

### `App\Http\Requests\DistrictAssembley\OnboardingRequest`
```json
{
    "region": "required|string|max:100",
    "district": "required|string|max:255",
    "email": "required|string|email|max:255|unique:district_assemblies,email",
    "phone_number": "required|string|max:20|unique:district_assemblies,phone_number",
    "gps_address": "required|string|max:255",
    "first_name": "required|string|max:255",
    "last_name": "nullable|string|max:255",
    "profile_image": "nullable|starts_with:data:,http://,https://"
}
```

### `App\Http\Requests\DistrictAssembley\PasswordChangeResetRequest`
```json
{
    "old_password": [
        "current_password:district_assembly"
    ],
    "password": [
        "required",
        {},
        "confirmed",
        "bail"
    ]
}
```

### `App\Http\Requests\DistrictAssembley\PasswordResetRequest`
```json
{
    "password": [
        "required",
        {},
        "bail"
    ],
    "district_assembly_slug": [
        "required",
        "alpha_dash"
    ],
    "otp": [
        "required"
    ]
}
```

### `App\Http\Requests\DistrictAssembley\ProfileUpdateRequest`
```json
{
    "region": "required|string|max:100",
    "district": "required|string|max:255",
    "email": [
        "required",
        "string",
        "email",
        "max:255",
        {}
    ],
    "phone_number": [
        "required",
        "string",
        "max:20",
        {}
    ],
    "gps_address": "required|string|max:255",
    "first_name": "required|string|max:255",
    "last_name": "nullable|string|max:255",
    "profile_image": "nullable|starts_with:data:,http://,https://"
}
```

### `App\Http\Requests\Driver\RegisterRequest`
```json
{
    "first_name": "required|string",
    "middle_name": "nullable|string",
    "last_name": "nullable|string",
    "date_of_birth": "required|date",
    "id_card_type": "required|string",
    "id_card_number": "required|string",
    "license_class": "required|string",
    "license_number": "required|string",
    "license_date_issued": "required|date",
    "license_expiry_issued": "required|date",
    "email": "required|string|email|unique:drivers,email",
    "phone_number": "required|string|unique:drivers,phone_number",
    "address": "required|string",
    "emergency_contact_name": "required|string",
    "emergency_phone_number": "required|string",
    "emergency_contract_address": "required|string",
    "license_front_image": "required|starts_with:data:,http://,https://",
    "license_back_image": "required|starts_with:data:,http://,https://",
    "profile_image": "required|starts_with:data:,http://,https://"
}
```

### `App\Http\Requests\Driver\StatusRequest`
```json
{
    "status": [
        "required",
        "string",
        "in:pending,deactivate,activate,on_leave",
        "bail"
    ],
    "driver_slug": [
        "required",
        "string",
        "exists:drivers,driver_slug",
        "bail"
    ]
}
```

### `App\Http\Requests\Driver\UpdateProfileRequest`
```json
{
    "first_name": "required|string|max:255",
    "middle_name": "nullable|string|max:255",
    "last_name": "nullable|string|max:255",
    "date_of_birth": "required|date",
    "id_card_type": "sometimes|string",
    "id_card_number": "sometimes|string",
    "license_class": "sometimes|string",
    "license_number": "sometimes|string",
    "license_date_issued": "required|date",
    "license_expiry_issued": "required|date",
    "email": [
        "required",
        "string",
        "email",
        "max:255",
        {}
    ],
    "phone_number": [
        "required",
        "string",
        "max:20",
        {}
    ],
    "address": "required|string|max:255",
    "license_front_image": "nullable|starts_with:data:,http://,https://",
    "license_back_image": "nullable|starts_with:data:,http://,https://",
    "profile_image": "nullable|starts_with:data:,http://,https://",
    "emergency_contact_name": "nullable|string|max:100",
    "emergency_phone_number": "nullable|string|max:100",
    "emergency_contract_address": "nullable|string|max:255"
}
```

### `App\Http\Requests\Facility\FacilityAccountStatusRequest`
```json
{
    "status": [
        "required",
        "string",
        "in:pending,deactivate,active",
        "bail"
    ],
    "facility_slug": [
        "required",
        "string",
        "exists:facilities,facility_slug",
        "bail"
    ],
    "suspension_reason": [
        "nullable",
        "string",
        "max:1000"
    ],
    "corrective_action": [
        "nullable",
        "string",
        "max:1000"
    ]
}
```

### `App\Http\Requests\Facility\FacilityOnboardingRequest`
```json
{
    "region": "required|string|max:100",
    "district": "required|string|max:255",
    "name": "required|string|max:255",
    "email": "required|string|email|max:255|unique:facilities,email",
    "phone_number": "required|string|max:20|unique:facilities,phone_number",
    "gps_address": "required|string|max:255",
    "first_name": "required|string|max:255",
    "last_name": "nullable|string|max:255",
    "business_registration_name": "nullable|string",
    "district_assembly": "nullable|string",
    "business_certificate_image": "nullable|starts_with:data:,http://,https://",
    "district_assembly_contract_image": "nullable|starts_with:data:,http://,https://",
    "tax_certificate_image": "nullable|starts_with:data:,http://,https://",
    "epa_permit_image": "nullable|starts_with:data:,http://,https://",
    "profile_image": "nullable|starts_with:data:,http://,https://",
    "type": "nullable|string",
    "ownership": "nullable|string",
    "zone_slugs": "nullable|array",
    "zone_slugs.*": "required|string|distinct|exists:zones,zone_slug"
}
```

### `App\Http\Requests\Facility\FacilityPasswordChangeResetRequest`
```json
{
    "old_password": [
        "current_password:facility"
    ],
    "password": [
        "required",
        {},
        "confirmed",
        "bail"
    ]
}
```

### `App\Http\Requests\Facility\FacilityPasswordResetRequest`
```json
{
    "password": [
        "required",
        {},
        "bail"
    ],
    "facility_slug": [
        "required",
        "alpha_dash"
    ],
    "otp": [
        "required"
    ]
}
```

### `App\Http\Requests\Facility\UpdateFacilityProfileRequest`
```json
{
    "district": "required|string|max:255",
    "name": "required|string|max:255",
    "email": [
        "required",
        "string",
        "email",
        "max:255",
        {}
    ],
    "phone_number": [
        "required",
        "string",
        "max:20",
        {}
    ],
    "gps_address": "required|string|max:255",
    "first_name": "required|string|max:255",
    "last_name": "nullable|string|max:255",
    "business_certificate_image": "nullable|starts_with:data:,http://,https://",
    "district_assembly_contract_image": "nullable|starts_with:data:,http://,https://",
    "tax_certificate_image": "nullable|starts_with:data:,http://,https://",
    "epa_permit_image": "nullable|starts_with:data:,http://,https://",
    "profile_image": "nullable|starts_with:data:,http://,https://",
    "type": "nullable|string|max:255",
    "ownership": "nullable|string|max:255"
}
```

### `App\Http\Requests\Feedback\CreateClientFeedbackRequest`
```json
{
    "ratings": [
        "required",
        "integer",
        "min:1",
        "max:5"
    ],
    "comments": [
        "nullable",
        "string"
    ],
    "score": [
        "nullable",
        "integer",
        "min:0",
        "max:10"
    ]
}
```

### `App\Http\Requests\Feedback\UpdateClientFeedbackRequest`
```json
{
    "ratings": "sometimes|integer|min:1|max:5",
    "comments": "sometimes|string|max:1000",
    "score": "sometimes|numeric|min:0|max:100",
    "status": "sometimes|string|in:pending,reviewed,resolved"
}
```

### `App\Http\Requests\Fleet\FleetStatusUpdateRequest`
```json
{
    "fleet_slug": "required|string|exists:fleets,fleet_slug",
    "status": "required|string|in:active,inactive,maintenance"
}
```

### `App\Http\Requests\Fleet\RegisterFleetRequest`
```json
{
    "vehicle_make": "nullable|string",
    "model": "nullable|string",
    "manufacture_year": "nullable|integer",
    "license_plate": "nullable|string|unique:fleets,license_plate",
    "bin_capacity": "nullable|string",
    "color": "nullable|string",
    "owner_first_name": "nullable|string",
    "owner_last_name": "nullable|string",
    "owner_phone_number": "nullable|string",
    "owner_address": "nullable|string",
    "provider_slug": "nullable|string|exists:providers,provider_slug",
    "insurance_expiry_date": "nullable|date",
    "insurance_policy_number": "nullable|string|unique:fleets,insurance_policy_number",
    "vehicle_images": "nullable",
    "vehicle_registration_certificate_image": "nullable",
    "vehicle_insurance_certificate_image": "nullable",
    "vehicle_roadworthy_certificate_image": "nullable",
    "status": "nullable|string|in:active,inactive,maintenance"
}
```

### `App\Http\Requests\Fleet\UpdateFleetRequest`
```json
[]
```

### `App\Http\Requests\Group\GroupCreation`
```json
{
    "name": "required|string|unique:groups,name",
    "description": "nullable|string"
}
```

### `App\Http\Requests\Group\GroupStatusUpdate`
```json
{
    "group_slug": "required|exists:groups,group_slug",
    "status": "required|in:active,revoke"
}
```

### `App\Http\Requests\Group\GroupUpdation`
```json
[]
```

### `App\Http\Requests\Pickup\PickupCreationRequest`
```json
{
    "title": "required|string",
    "category": "required|string",
    "description": "nullable|string",
    "location": "required|string",
    "images": [
        "nullable",
        "array"
    ],
    "images.*": [
        "string",
        "starts_with:data:,http://,https://"
    ]
}
```

### `App\Http\Requests\Pickup\PickupStatusChangeRequest`
```json
{
    "id": "required|string|exists:pickups,id",
    "status": "required|string"
}
```

### `App\Http\Requests\Pickup\SetPickupDateRequest`
```json
{
    "code": [
        "required",
        "string",
        "exists:pickups,code"
    ],
    "pickup_date": [
        "required",
        "date",
        "after_or_equal:today"
    ]
}
```

### `App\Http\Requests\Pickup\SetPickupPriceRequest`
```json
{
    "amount": [
        "required",
        "numeric",
        "min:0"
    ],
    "code": [
        "required",
        "string",
        "exists:pickups,code"
    ]
}
```

### `App\Http\Requests\Pickup\UpdatePickupRequest`
```json
{
    "title": [
        "sometimes",
        "string"
    ],
    "category": [
        "sometimes",
        "string"
    ],
    "description": [
        "sometimes",
        "string"
    ],
    "location": [
        "sometimes",
        "string"
    ],
    "images": [
        "sometimes",
        "array"
    ],
    "images.*": [
        "sometimes",
        "starts_with:data:,http://,https://"
    ]
}
```

### `App\Http\Requests\Product\ProductCreationRequest`
```json
{
    "name": [
        "required",
        "string"
    ],
    "category": [
        "nullable",
        "string",
        "max:255"
    ],
    "color": [
        "nullable",
        "string"
    ],
    "size": [
        "nullable",
        "string"
    ],
    "images": [
        "nullable",
        "array"
    ],
    "images.*": [
        "nullable",
        "starts_with:data:,http://,https://"
    ],
    "original_price": [
        "required",
        "numeric",
        "min:0"
    ],
    "discounted_price": [
        "nullable",
        "numeric",
        "min:0"
    ],
    "discount_percentage": [
        "nullable",
        "numeric",
        "min:0",
        "max:100"
    ],
    "quantity": [
        "required",
        "integer",
        "min:0"
    ]
}
```

### `App\Http\Requests\Product\ProductUpdateRequest`
```json
{
    "name": [
        "sometimes",
        "string"
    ],
    "category": [
        "sometimes",
        "nullable",
        "string",
        "max:255"
    ],
    "color": [
        "sometimes",
        "string"
    ],
    "size": [
        "sometimes",
        "string"
    ],
    "images": [
        "sometimes",
        "array"
    ],
    "images.*": [
        "sometimes",
        "nullable",
        "starts_with:data:,http://,https://"
    ],
    "original_price": [
        "sometimes",
        "numeric",
        "min:0"
    ],
    "discounted_price": [
        "sometimes",
        "nullable",
        "numeric",
        "min:0"
    ],
    "discount_percentage": [
        "sometimes",
        "nullable",
        "numeric",
        "min:0",
        "max:100"
    ],
    "quantity": [
        "sometimes",
        "integer",
        "min:0"
    ]
}
```

### `App\Http\Requests\Provider\ProviderPasswordChangeResetRequest`
```json
{
    "old_password": [
        "current_password:provider"
    ],
    "password": [
        "required",
        {},
        "confirmed",
        "bail"
    ]
}
```

### `App\Http\Requests\Provider\ProviderPasswordResetRequest`
```json
{
    "password": [
        "required",
        {},
        "bail"
    ],
    "provider_slug": [
        "required",
        "alpha_dash"
    ],
    "otp": [
        "required"
    ]
}
```

### `App\Http\Requests\Provider\ProviderStatusRequest`
```json
{
    "status": [
        "required",
        "string",
        "in:pending,deactivate,active",
        "bail"
    ],
    "provider_slug": [
        "required",
        "string",
        "exists:providers,provider_slug",
        "bail"
    ],
    "suspension_reason": [
        "nullable",
        "string",
        "max:1000"
    ],
    "corrective_action": [
        "nullable",
        "string",
        "max:1000"
    ]
}
```

### `App\Http\Requests\Provider\StoreProviderRegisterRequest`
```json
{
    "first_name": "required|string",
    "last_name": "nullable|string",
    "email": "required|string|email|unique:providers,email",
    "phone_number": "required|string|unique:providers,phone_number",
    "business_name": "required|string",
    "district_assembly": "nullable|string",
    "business_registration_number": "required|string|unique:providers,business_registration_number",
    "gps_address": "required|string",
    "business_certificate_image": "nullable|starts_with:data:,http://,https://",
    "district_assembly_contract_image": "nullable|starts_with:data:,http://,https://",
    "tax_certificate_image": "nullable|starts_with:data:,http://,https://",
    "epa_permit_image": "nullable|starts_with:data:,http://,https://",
    "zone_slugs": "nullable|array",
    "zone_slugs.*": "required|string|distinct|exists:zones,zone_slug",
    "region": "required|string",
    "location": "required|string",
    "profile_image": "nullable|starts_with:data:,http://,https://"
}
```

### `App\Http\Requests\Provider\UpdateProviderProfileRequest`
```json
{
    "first_name": "required|string|max:255",
    "last_name": "nullable|string|max:255",
    "email": [
        "required",
        "string",
        "email",
        "max:255",
        {}
    ],
    "phone_number": [
        "required",
        "string",
        "max:20",
        {}
    ],
    "business_registration_number": [
        "required",
        "string",
        "max:100",
        {}
    ],
    "business_name": "nullable|string|max:255",
    "gps_address": "required|string|max:255",
    "district_assembly": "nullable|string|max:255",
    "business_certificate_image": "nullable|starts_with:data:,http://,https://",
    "district_assembly_contract_image": "nullable|starts_with:data:,http://,https://",
    "tax_certificate_image": "nullable|starts_with:data:,http://,https://",
    "epa_permit_image": "nullable|starts_with:data:,http://,https://",
    "region": "required|string|max:100",
    "location": "required|string|max:255",
    "profile_image": "nullable|starts_with:data:,http://,https://"
}
```

### `App\Http\Requests\Purchase\PurchaseCreationRequest`
```json
{
    "items": [
        "required",
        "array",
        "min:1"
    ],
    "items.*.product_slug": [
        "required",
        "string",
        "exists:products,product_slug"
    ],
    "items.*.quantity": [
        "required",
        "integer",
        "min:1"
    ]
}
```

### `App\Http\Requests\RoutePlanner\RegisterRoute`
```json
{
    "provider_slug": "nullable|string|exists:providers,provider_slug",
    "driver_slug": "required|string|exists:drivers,driver_slug",
    "fleet_slug": "required|string|exists:fleets,fleet_slug",
    "pickup_type": "required|string|in:bulk_waste_request,normal",
    "pickup_date": "nullable|date",
    "group_slugs": "required_if:pickup_type,normal|array|min:1",
    "group_slugs.*": "string|exists:groups,group_slug",
    "bulk_request_codes": "required_if:pickup_type,bulk_waste_request|array|min:1",
    "bulk_request_codes.*": "string|exists:bulk_waste_requests,request_code",
    "status": "nullable|string|in:pending,completed,cancelled,progress,in_progress"
}
```

### `App\Http\Requests\RoutePlanner\RouteDetailsUpdate`
```json
{
    "client_slug": "sometimes|string|exists:clients,client_slug",
    "driver_slug": "sometimes|string|exists:drivers,driver_slug",
    "fleet_slug": "sometimes|string|exists:fleets,fleet_slug",
    "group_slug": "sometimes|string|exists:groups,group_slug",
    "status": "nullable|string|in:pending,completed,cancalled,progress"
}
```

### `App\Http\Requests\RoutePlanner\RouteStatusUpdate`
```json
{
    "id": "required|string|exists:route_planners,id",
    "status": "nullable|string|in:pending,completed,cancalled,progress"
}
```

### `App\Http\Requests\Violation\ViolationCreationRequest`
```json
{
    "client_slug": [
        "nullable",
        "string"
    ],
    "type": [
        "required",
        "string"
    ],
    "location": [
        "required",
        "string"
    ],
    "description": [
        "nullable",
        "string"
    ],
    "images": [
        "nullable",
        "array"
    ],
    "images.*": [
        "nullable",
        "starts_with:data:,http://,https://"
    ]
}
```

### `App\Http\Requests\Violation\ViolationUpdateRequest`
```json
{
    "type": [
        "sometimes",
        "string"
    ],
    "description": [
        "sometimes",
        "nullable",
        "string"
    ],
    "location": [
        "sometimes",
        "string"
    ],
    "status": [
        "sometimes",
        "string",
        "in:pending,open,in_progress,closed"
    ],
    "images": [
        "nullable"
    ],
    "images.*": [
        "nullable",
        "starts_with:data:,http://,https://"
    ]
}
```

### `App\Http\Requests\Weighbridge\CreateTicket`
```json
{
    "provider_slug": [
        "required",
        "string",
        "exists:providers,provider_slug"
    ],
    "fleet_slug": [
        "nullable",
        "string",
        "exists:fleets,fleet_slug"
    ],
    "zone_slug": [
        "nullable",
        "string",
        "exists:zones,zone_slug"
    ],
    "fleet_code": [
        "nullable",
        "string"
    ],
    "gross_weight": [
        "nullable",
        "numeric",
        "min:0"
    ],
    "amount": [
        "required",
        "numeric",
        "min:0"
    ],
    "payment_status": [
        "required",
        "string",
        "in:pending_payment,paid,credit"
    ],
    "scan_status": [
        "nullable",
        "string",
        "in:scanned,unscanned,handover"
    ],
    "notes": [
        "nullable",
        "string"
    ]
}
```

### `App\Http\Requests\Zone\ZoneCreationRequest`
```json
{
    "name": "required|string|unique:zones,name",
    "region": "required|string",
    "description": "nullable|string",
    "locations": "required|array"
}
```

### `App\Http\Requests\Zone\ZoneStatusUpdateRequest`
```json
{
    "zone_slug": "required|exists:zones,zone_slug",
    "status": "required|in:active,revoke"
}
```

### `App\Http\Requests\Zone\ZoneUpdationRequest`
```json
{
    "name": [
        "sometimes",
        {}
    ],
    "region": "sometimes|string",
    "description": "nullable|string",
    "locations": "nullable|array"
}
```
