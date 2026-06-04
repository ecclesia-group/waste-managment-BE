## Waste Management BE — Frontend Payload Guide

**See also:** `docs/API_AUDIT.md` (module compliance, gaps, response-shape notes).

**Postman (generated from live routes):**  
- Run `php scripts/generate_postman_collection.php`  
- Import `postman/Waste_Removal_API.generated.postman_collection.json`  
- For rich example bodies from your team, keep your existing **Waste Removal Live** collection and copy requests into folders as needed (merge by hand — safest for production secrets).

Base URL: `https://<your-domain>/api`

All responses follow:

```json
{
  "data": {
    "status_code": 200,
    "message": "Action Successful",
    "in_error": false,
    "reason": "Human readable reason",
    "data": {},
    "point_in_time": "2026-03-09T12:00:00.000000Z"
  }
}
```

#### PUT `/admin/update_purchase_status/{purchase}` (Auth: Bearer)
Updates an order/purchase status and creates an in-app notification for the client.
```json
{ "status": "pending" | "confirmed" | "out_for_delivery" | "delivered" | "cancelled" }
```

### Auth (Client)

#### POST `/client/login`

```json
{ "emailOrPhone": "user@example.com", "password": "secret" }
```

Response `data.data` contains `token` (Passport) and client profile.

#### POST `/client/logout` (Auth: Bearer)

Response `data.data`: `[]`

### Complaints (Client)

#### POST `/client/create_complaint` (Auth: Bearer)

```json
{
  "title": "Missed pickup",
  "location": "House 12, Street A",
  "description": "Truck did not come today",
  "images": ["data:image/png;base64,iVBORw0..."]
}
```

#### GET `/client/get_complaints` (Auth: Bearer)

#### GET `/client/get_single_complaint/{complaint}` (Auth: Bearer)

### Bulk Waste Requests / Pickups (Client)

#### POST `/client/create_bulk_waste_request` (Auth: Bearer)

```json
{
  "title": "Bulk waste pickup",
  "category": "bulky",
  "description": "Old furniture + cartons",
  "location": "Back gate",
  "images": ["data:image/jpeg;base64,/9j/4AAQSk..."]
}
```

#### GET `/client/get_client_pickups` (Auth: Bearer)
#### GET `/client/get_completed_pickups` (Auth: Bearer)
#### GET `/client/get_pickup_dates` (Auth: Bearer)

### Store (Client)

#### GET `/client/get_products` (Auth: Bearer)
Optional query params:
- `category` (string): filter products by category
- `q` (string): search product name

#### Store Cart (Client)
#### GET `/client/cart` (Auth: Bearer)
Returns the authenticated client cart items.

#### POST `/client/cart/add_item` (Auth: Bearer)
```json
{ "product_slug": "product-uuid", "quantity": 2 }
```

#### PUT `/client/cart/update_item/{product_slug}` (Auth: Bearer)
```json
{ "quantity": 3 }
```

#### DELETE `/client/cart/remove_item/{product_slug}` (Auth: Bearer)
Removes the product from the cart.

#### POST `/client/cart/checkout` (Auth: Bearer)
Creates a pending purchase from the current cart and clears the cart.

#### POST `/client/create_purchase` (Auth: Bearer)

```json
{
  "items": [
    { "product_slug": "6b9b0a7e-...", "quantity": 2 },
    { "product_slug": "c8b21b2c-...", "quantity": 1 }
  ]
}
```

#### POST `/client/process_payment/{purchase}` (Auth: Bearer)

```json
{
  "transaction_id": "TXN-123456",
  "payment_method": "momo",
  "network": "MTN",
  "phone_number": "233500000000",
  "name": "Jane Doe"
}
```

Response includes:

```json
{
  "payment": { "transaction_id": "TXN-123456", "amount": 120.0, "status": "success" },
  "purchase": { "status": "pending", "items": [] },
  "qrcode": "https://<cdn>/storage/qrcodes/<file>.png"
}
```

#### GET `/client/get_payment_history` (Auth: Bearer)
Returns all `payments` made by the authenticated client.

### Violations (Client)
Clients **only view** violations.

#### GET `/client/get_violations` (Auth: Bearer)
#### GET `/client/get_single_violation/{violation}` (Auth: Bearer)

---

### Provider — Customers

#### POST `/provider/register_client` (Auth: Bearer)

```json
{
  "first_name": "Ama",
  "last_name": "Mensah",
  "phone_number": "23350...",
  "email": "ama@example.com",
  "gps_address": "GA-123-4567",
  "type": "residential",
  "pickup_location": "Front gate",
  "bin_size": "120L",
  "bin_code": "BIN-0001",
  "group_slug": "GROUP-001"
}
```

#### POST `/provider/scan_qrcode` (Auth: Bearer)

```json
{ "qrcode_data": "{\"client_slug\":\"...\",\"bin_code\":\"BIN-0001\"}" }
```

### Provider — Violations (record during pickup)

#### POST `/provider/create_violation` (Auth: Bearer)

```json
{
  "client_slug": "0f0e...uuid",
  "type": "waste_contamination",
  "location": "GA-123-4567",
  "description": "Mixed plastics in organic waste",
  "images": ["data:image/png;base64,iVBORw0..."]
}
```

### Route Planner (Map + Scan Status)

#### POST `/provider/create_plan` (Auth: Bearer)
Creates a route planner plan and schedules pending pickup rows using one of two planning modes:
- `pickup_type = "bulk_waste_request"`: plan from clients that have submitted bulk waste requests.
- `pickup_type = "normal"`: plan from selected group(s) and/or specific clients.
```json
{
  "provider_slug": "provider-uuid",
  "driver_slug": "driver-uuid",
  "fleet_slug": "fleet-uuid",
  "pickup_type": "normal",
  "group_slugs": ["group-uuid-1", "group-uuid-2"],
  "client_slugs": ["optional-client-uuid"]
}
```

Bulk request plan example:
```json
{
  "provider_slug": "provider-uuid",
  "driver_slug": "driver-uuid",
  "fleet_slug": "fleet-uuid",
  "pickup_type": "bulk_waste_request",
  "bulk_request_codes": ["BWR123", "BWR456"]
}
```

#### GET `/provider/get_single_plan/{plan}` (Auth: Bearer)
Returns `data.bins` for the map UI (bin addresses are red/unscanned vs green/scanned).
Each bin includes:
```json
{
  "pickup_code": "ABC123",
  "scan_status": "scanned" | "unscanned",
  "client": { "client_slug": "...", "bin_code": "..." },
  "pickup": { "code": "ABC123", "status": "completed" }
}
```

#### POST `/provider/change_scan_status` (Auth: Bearer)
Updates scan status immediately (no GPS or offline lag checks). Syncs route planner assignment rows for the map.
If `status` is `scanned`, pickup `status` becomes `completed`.
Optional `comment` is stored on the pickup `description`. Record violations separately via `POST /provider/create_violation`.
```json
{
  "code": "PICKUP-CODE",
  "status": "scanned" | "not_scanned" | "unscanned",
  "comment": "optional driver note"
}
```

#### GET `/provider/assignment_logs` (Auth: Bearer)
Assignment/bin activity logs used for map filtering.
Query params: `status=scanned|unscanned|pending`, `from=YYYY-MM-DD`, `to=YYYY-MM-DD`, `driver_slug`, `group_slug`, `provider_slug`, `limit`.

#### GET `/provider/map_pickup_overview` (Auth: Bearer)
Map payload for pickups with optional plan-specific drawing filters.
Query params:
- `plan_id` to draw bins for one pickup plan
- `group_slug` to draw only one group within the plan/provider
- `group_by=provider|plan|group|zone|mmda`

### Provider Reports (Analytics)
#### GET `/provider/reports` (Auth: Bearer)
Returns consolidated analytics for the authenticated provider (clients/fleet/utilization/payments/violations).

Response `data` keys (current backend):
```json
{
  "customers_overview": { "active": 0, "inactive": 0, "suspended": 0 },
  "fleet_overview": { "active": 0, "inactive": 0 },
  "utilization": { "scanned_bins": 0, "unscanned_bins": 0, "handover_requests_total": 0, "handover_requests_completed": 0 },
  "routing_analytics": { "avg_seconds_to_scan": null, "by_group": [] },
  "payment_analytics": { "payments_success_count": 0, "payments_success_total_amount": 0 },
  "violation_overview": { "total_violations": 0, "by_type": [] }
}
```

### Waste Handover Requests (Provider / team member)

Team members submit aboboya requests on behalf of their provider. All **other** providers in the same zone(s) receive SMS, email, and in-app notifications. Records appear under `GET /provider/handover_requests/available` for zone peers to accept.

#### POST `/provider/handover_requests` (Auth: Bearer)

```json
{
  "title": "Aboboya pickup",
  "requester_type": "aboboya",
  "requester_name": "Kofi Mensah",
  "requester_phone": "0241234567",
  "requester_email": "kofi@example.com",
  "waste_types": ["mixed_waste"],
  "pickup_location": "Osu, Accra",
  "latitude": 5.6037,
  "longitude": -0.1870,
  "fee_amount": 0,
  "images": []
}
```

Optional on create: `selected_driver_slug`, `selected_fleet_slug` (usually set on accept instead).

#### GET `/provider/handover_requests/available` (Auth: Bearer)
Pending requests in your zone(s) you may accept (excludes your own submissions).

#### GET `/provider/handover_requests?status=pending` (Auth: Bearer)

#### POST `/provider/handover_requests/{handover}/accept` (Auth: Bearer)
First provider in the zone wins. Requester receives SMS + email with provider name, phone, fleet, and driver (if assigned).

```json
{
  "driver_slug": "optional-driver-uuid",
  "fleet_slug": "optional-fleet-uuid"
}
```

#### POST `/provider/handover_requests/{handover}/confirm_payment` (Auth: Bearer)

#### POST `/provider/handover_requests/{handover}/complete` (Auth: Bearer)
Optionally include fee payment details:

```json
{
  "transaction_id": "TXN-HANDOVER-001",
  "payment_method": "cash"
}
```

---

### Facility — Weighbridge

#### POST `/provider/weighbridge_records` (Auth: Bearer)
Provider submits a weighbridge handover to facility. Payment is captured later by facility scan/verification.

```json
{
  "facility_slug": "facility-uuid",
  "fleet_slug": "fleet-uuid",
  "fleet_code": "FLT-001",
  "zone_slug": "zone-uuid",
  "gross_weight": 2450.5,
  "amount": 320.0,
  "notes": "Sent to facility awaiting payment verification"
}
```

Response includes generated ticket `code` and defaults:
- `payment_status = "pending_payment"`
- `scan_status = "handover"`

#### POST `/facility/register_weigh_bridge_entry` (Auth: Bearer)

```json
{
  "provider_slug": "provider-uuid",
  "fleet_slug": "fleet-uuid",
  "fleet_code": "FLT-001",
  "gross_weight": 2450.5,
  "amount": 320.0,
  "payment_status": "paid",
  "scan_status": "scanned",
  "notes": "Arrived 10:20am"
}
```

#### GET `/facility/all_weigh_bridge_entries?payment_status=credit&from=2026-03-01&to=2026-03-09` (Auth: Bearer)

#### POST `/facility/update_weigh_bridge_entry_status` (Auth: Bearer)

```json
{ "id": 12, "payment_status": "paid" }
```

#### POST `/facility/verify_weigh_bridge_ticket` (Auth: Bearer)
Facility verifies ticket code when truck arrives and records payment mode (`paid` or `credit`).

```json
{
  "code": "WB-ABC12345",
  "payment_status": "credit",
  "notes": "Accepted on credit at gate"
}
```

### Facility Reports (Analytics)
#### GET `/facility/reports` (Auth: Bearer)
Returns consolidated analytics for the authenticated facility (weighbridge intake + payment split).

### District Assembly (MMDA) Reports (Analytics)
#### GET `/district_assembly/reports` (Auth: Bearer)
Returns consolidated analytics for the authenticated MMDA (providers/facilities under the district assembly).

---

### Admin Reports (Analytics)
#### GET `/admin/reports` (Auth: Bearer)
Returns platform-wide summary metrics (customers/providers/facility scanned assignments + total violations).

---

## Frontend Integration Contract (Final)

Use this section as the implementation checklist for mobile/web frontend teams.

### 1) Weighbridge Payment Lifecycle

- **Provider submits handover:** `POST /provider/weighbridge_records`
  - Backend creates ticket `code`
  - Initial statuses are always:
    - `payment_status = pending_payment`
    - `scan_status = handover`
- **Facility verifies at gate:** `POST /facility/verify_weigh_bridge_ticket`
  - Required: `code`, `payment_status` (`paid` or `credit`)
  - Backend sets `scan_status = scanned`
- **Provider views own records:** `GET /provider/weighbridge_records`
  - Optional filters: `facility_slug`, `payment_status`, `scan_status`

### 2) Pickup Planning Modes

- **Create plan:** `POST /provider/create_plan`
- **Required core fields:** `provider_slug`, `driver_slug`, `fleet_slug`, `pickup_type`
- **Mode A (`bulk_waste_request`):**
  - Provide `bulk_request_codes` and/or `client_slugs`
  - Backend schedules pickups tied to request codes
- **Mode B (`normal`):**
  - Provide `group_slugs` and/or `client_slugs`
  - Backend schedules pickups for selected groups/clients

### 3) Live Scan + Driver Location

- **Driver live location update:** `POST /provider/update_driver_location`
  - Required: `driver_slug`, `latitude`, `longitude`
- **Pickup scan update:** `POST /provider/change_scan_status`
  - Required: `code`, `status` (`scanned`, `not_scanned`, `unscanned`)
  - If scanned, backend marks pickup as completed and updates route assignment scan state.

### 4) Map Drawing for Pickup Plans

- **Overview endpoint:** `GET /provider/map_pickup_overview`
- **Filters for drawing one selected plan:**
  - `plan_id=<route_planner_id>`
  - Optional `group_slug=<group_slug>`
- **Grouping options:** `group_by=provider|plan|group|zone|mmda`
- **Map item fields (important):**
  - `route_planner_id`
  - `pickup_code`
  - `provider_slug`
  - `group_slug`
  - `zone_slug`
  - `latitude`, `longitude`
  - `scan_status`

### 5) Waste Handover Requests

- **Create (team member):** `POST /provider/handover_requests` with `requester_name`, `requester_phone`, location, `requester_type` (`aboboya`)
- **Zone inbox:** `GET /provider/handover_requests/available`
- **Accept:** `POST /provider/handover_requests/{handover}/accept` — notifies requester by SMS/email
- **Payment:** `POST /provider/handover_requests/{handover}/confirm_payment` (if fee > 0)
- **Complete:** `POST /provider/handover_requests/{handover}/complete`

### 6) Cross-Role Visibility Rules (Frontend Expectations)

- Provider sees provider-scoped records/plans/assignments only.
- Facility sees facility-scoped weighbridge entries only.
- MMDA sees providers/facilities/zones within its district scope.
- Admin sees platform-wide analytics endpoints.


