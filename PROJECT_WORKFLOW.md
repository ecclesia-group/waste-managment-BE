# Waste Management BE - Project Workflow

## 0) API audit & Postman (2026)

- **Full audit / module matrix:** `docs/API_AUDIT.md` (no RBAC scope).
- **Postman:** Generate a collection that matches **current** `routes/api.php`:
  ```bash
  php scripts/generate_postman_collection.php
  ```
  Output: `postman/Waste_Removal_API.generated.postman_collection.json` (variables: `base_url`, `client_token`, `provider_token`, etc.).
- **Your own Postman export** (e.g. from Downloads) can be merged in Postman UI: import both collections, drag folders, preserve your sample JSON bodies.
- **Automated smoke tests:** `tests/Feature/ApiSmokeTest.php`. If `php artisan test` errors inside `vendor/` (PHP version vs dev tools), align PHP with `composer.json` / upgrade toolchain.

## 1) What this backend is
This is a Laravel API backend for a multi-actor waste management platform:
`admin`, `district_assembly (MMDA)`, `provider`, `facility`, and `client`.

It uses Passport (`auth:...`) for actor authentication and a shared response format:
`data.status_code`, `data.message`, `data.in_error`, `data.reason`, `data.data`, `data.point_in_time`.

### Shared response format (see `API_PAYLOADS.md`)
Every endpoint returns JSON shaped like:
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

## 2) How to navigate the codebase
### Routes
All API routes live in `routes/api.php`.
Look here first to find the endpoint -> controller mapping.

### Controllers
Each module uses a dedicated controller namespace:
- `app/Http/Controllers/Client/*`
- `app/Http/Controllers/Provider/*`
- `app/Http/Controllers/Pickup/*`
- `app/Http/Controllers/RoutePlanner/*`
- `app/Http/Controllers/Violation/*`
- `app/Http/Controllers/WeighBridge/*`
- `app/Http/Controllers/Handover/*`
- `app/Http/Controllers/Complaint/*`

### Requests (validation)
Request validation rules live in `app/Http/Requests/**`.
Always check:
- the `rules()` for required keys + allowed values
- optional “scan-first tolerance” behavior (some requests allow nullable values)

### Models
Models live in `app/Models/*`.
They define:
- fillable fields
- casts (e.g. JSON arrays)
- relationships (for eager loading / map payloads)

### Helpers / shared utilities
`app/Traits/Helpers.php` contains shared helper logic:
- image/video base64 handling
- QR code generation + storage caching

## 3) High-level end-to-end flows
### A) Bin QR code lifecycle (client-owned bin)
1. A client’s bin is represented by `clients.bin_code`.
2. QR content is generated to include `client_slug` + `bin_code` (and other display info).
3. The generated QR image URL is stored in `clients.qrcode` (array).
4. Provider scanning is done by scanning the bin QR:
   - `POST /api/provider/scan_qrcode` (`ClientController::scanQRCode`)
5. If the bin is marked damaged (violation type indicates bin damage):
   - QR is regenerated and the old QR is invalidated by `bin_code` mismatch checks.

Key code locations:
- `app/Http/Controllers/Purchase/PurchaseController.php` (initial QR generation during payment/buy)
- `app/Traits/Helpers.php` (QR image caching + generation)
- `app/Http/Controllers/Client/ClientController.php` (scan validation)
- `app/Http/Controllers/Violation/ViolationManagementController.php` (bin damage -> regenerate)

### B) Route planning + map (red vs green)
The route planning UI needs to show which houses/bins were scanned.
This backend now supports that via:
`route_planner_bin_assignments` (one row per client/bin assignment for a plan).

Flow:
1. Provider creates a plan (`POST /api/provider/create_plan`)
2. Backend creates:
   - `route_planners` record
   - a pending `pickups` row per assigned client
   - a `route_planner_bin_assignments` row per assigned client/bin
3. Map “bin color” derives from assignment scan state:
   - `pending` / `not_scanned` -> red/unscanned
   - `scanned` -> green
4. When driver scans a bin:
   - `POST /api/provider/change_scan_status`
   - updates both `pickups.scan_status` and `route_planner_bin_assignments.scan_status`
5. When the driver enters a bin code manually:
   - `POST /api/provider/manual_bin_code_scan`
   - returns pending assignments/pickups for that bin

Key code locations:
- Migration: `database/migrations/2026_03_18_000001_create_route_planner_bin_assignments_table.php`
- Model: `app/Models/RoutePlannerBinAssignment.php`
- Create plan + map payload: `app/Http/Controllers/RoutePlanner/RoutePlannerManagement.php`
- Scan updates: `app/Http/Controllers/Pickup/PickupController.php`

### C) Violations and client notifications
Spec requirement: clients should see violation reports as notifications (education only).
Current backend behavior:
1. Providers record violations during pickup:
   - `POST /api/provider/create_violation`
2. Backend:
   - creates the `violations` row
   - creates an in-app notification for the client
3. If the violation indicates bin damage:
   - regenerate bin QR + bin_code
   - send an additional notification: “Bin damaged - QR regenerated”

Key code locations:
- `app/Http/Controllers/Violation/ViolationManagementController.php`
- Notification retrieval:
  - `GET /api/client/get_all_notifications` (`NotificationController`)

### D) Store orders + payment history
- Payments are stored in `payments` and linked to purchases via `purchase_id`.
- When payment succeeds:
  - `POST /api/client/process_payment/{purchase}` sets `purchases.status = confirmed`
- Client payment history:
  - `GET /api/client/get_payment_history`
- Admin order status updates:
  - `PUT /api/admin/update_purchase_status/{purchase}`

### E) Reports / analytics dashboards
Reports endpoints aggregate metrics using existing tables/models.
- `GET /api/provider/reports`
- `GET /api/facility/reports`
- `GET /api/district_assembly/reports`
- `GET /api/admin/reports`

These endpoints compute consolidated totals (counts/sums/breakdowns) for dashboards:
- provider: clients, fleets, scanned vs unscanned bins, payment totals, violations breakdown
- facility: weighbridge intake totals and payment split
- district assembly/admin: platform-wide summaries across relevant tenants

## 4) Performance workflow checklist (before production)
When adding new endpoints or improving existing ones:
1. Use pagination for “list” endpoints (avoid `->get()` returning everything).
2. Add selective indexes to migrations for frequent filters.
3. Cache read-heavy content:
   - banners/guides/products (audience + status + updated_at rules)
4. Keep scanning endpoints:
   - O(1) lookups using codes/unique keys
   - atomic writes (use DB transactions)
5. Never call third-party services per request without caching.

## 5) How to safely extend the system
If you need a new feature:
1. Add/adjust validation in `app/Http/Requests/**`.
2. Update or add a migration only when required by new entities.
3. Add/adjust controller logic.
4. Update `routes/api.php`.
5. Update `API_PAYLOADS.md` so frontend payloads stay correct.
6. Update Postman collection to match the payloads/variables.

