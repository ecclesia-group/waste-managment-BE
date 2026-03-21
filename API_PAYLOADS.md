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

### Client Dashboard Content

#### GET `/client/banners` (Auth: Bearer)
Returns active hero banners for clients.

#### GET `/client/guides?category=bin_use` (Auth: Bearer)
Returns guides for clients. `category` optional.

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
  "group_id": "GROUP-001"
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
Creates a route planner plan and schedules pending pickup rows for all active clients in the plan group.
```json
{
  "provider_slug": "provider-uuid",
  "driver_slug": "driver-uuid",
  "fleet_slug": "fleet-uuid",
  "group_slug": "group-uuid"
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
Updates scan status for a pickup code and keeps route planner assignment rows in sync.
If `status` is `scanned`, the backend also sets the pickup `status` to `completed`.
```json
{
  "code": "PICKUP-CODE",
  "status": "scanned" | "not_scanned" | "unscanned"
}
```

#### GET `/provider/assignment_logs` (Auth: Bearer)
Assignment/bin activity logs used for map filtering.
Query params: `status=scanned|unscanned|pending`, `from=YYYY-MM-DD`, `to=YYYY-MM-DD`, `driver_slug`, `group_slug`, `provider_slug`, `limit`.

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

### Provider Dashboard Content

#### GET `/provider/banners` (Auth: Bearer)
#### GET `/provider/guides?category=scanning` (Auth: Bearer)

### Waste Handover Requests (Provider)

#### POST `/provider/handover_requests` (Auth: Bearer)

```json
{
  "title": "Handover request",
  "waste_types": ["mixed_waste", "plastics"],
  "description": "Need a bigger truck to take over",
  "pickup_location": "Zone A - roadside",
  "fee_amount": 25,
  "target_provider_slug": "optional-provider-uuid",
  "images": ["data:image/png;base64,iVBORw0..."]
}
```

#### GET `/provider/handover_requests?status=pending` (Auth: Bearer)

#### POST `/provider/handover_requests/{handover}/accept` (Auth: Bearer)

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

### Facility Reports (Analytics)
#### GET `/facility/reports` (Auth: Bearer)
Returns consolidated analytics for the authenticated facility (weighbridge intake + payment split).

### District Assembly (MMDA) Reports (Analytics)
#### GET `/district_assembly/reports` (Auth: Bearer)
Returns consolidated analytics for the authenticated MMDA (providers/facilities under the district assembly).

---

### Admin (Super Admin) — Content

### Admin Reports (Analytics)
#### GET `/admin/reports` (Auth: Bearer)
Returns platform-wide summary metrics (customers/providers/facility scanned assignments + total violations).

#### POST `/admin/banners` (Auth: Bearer)

```json
{
  "title": "System maintenance",
  "message": "Maintenance on Friday 10pm",
  "audience": "all",
  "status": "active",
  "image": ["data:image/png;base64,iVBORw0..."],
  "starts_at": "2026-03-10",
  "ends_at": "2026-03-12"
}
```

#### POST `/admin/guides` (Auth: Bearer)

```json
{
  "title": "Proper bin use",
  "category": "bin_use",
  "content": "Step-by-step guide...",
  "audience": "client",
  "status": "active",
  "attachments": ["data:application/pdf;base64,JVBERi0x..."]
}
```


