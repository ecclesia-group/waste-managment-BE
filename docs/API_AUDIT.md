# API System Audit — Waste Management Backend

**Generated:** 2026-04-21  
**Scope:** Routes in `routes/api.php`, controllers, and alignment with the product document. **RBAC / roles / permissions are out of scope** (per project direction).

## 1. Executive summary

| Area | Status |
|------|--------|
| Multi-actor API surface (Client, Provider, Facility, MMDA, Admin) | **Implemented** — see route groups |
| Consistent REST naming | **Partial** — many routes use action-style names (`get_single_*`, `create_*`) |
| Unified response envelope `{ success, message, data, meta }` | **Not implemented** — app uses `data.status_code`, `data.in_error`, `data.reason`, `data.data` (see `API_PAYLOADS.md`) |
| Client/provider verify + resend verification flow | **Implemented** — explicit `/verify_account` and `resend_verificationCode` wiring for client/provider |
| Client registration payment gate | **Implemented** — client is authenticated but gated by registration payment middleware before dashboard and app modules; pay via `POST /api/client/payments/registration` |
| Pagination / filtering on all list endpoints | **Partial** — varies by controller |
| Postman collection aligned to live routes | **Automated** — `postman/Waste_Removal_API.generated.postman_collection.json` |
| Automated test suite | **Minimal** — see `tests/Feature/ApiSmokeTest.php`; local `php artisan test` may require **PHP 8.3+** with current vendor deps |

## 2. Response format (actual vs product doc)

**Current (implemented in controllers via shared helpers):**

```json
{
  "data": {
    "status_code": 200,
    "message": "Action Successful",
    "in_error": false,
    "reason": "...",
    "data": {},
    "point_in_time": "..."
  }
}
```

**Product doc target:**

```json
{
  "success": true,
  "message": "",
  "data": {},
  "meta": {}
}
```

**Recommendation:** Introduce a **response transformer middleware** or gradual migration in `Controller::apiResponse` so React apps can adopt one shape; do not flip all endpoints in one PR without frontend coordination.

## 3. Route inventory (source of truth)

- **Total API routes (approx.):** run `php artisan route:list --path=api`
- **Authoritative list:** `php artisan route:list --json` (used by the Postman generator)

## 4. Module compliance (product document)

### Client

| Module | Implemented | Notes |
|--------|-------------|--------|
| Dashboard (profile, MMDA, provider, zone) | **Yes** | `GET /api/client/dashboard` (after registration payment; see `payments/registration`) |
| Registration payment | **Yes** | `POST /api/client/payments/registration`, `GET /api/client/payments/registration/status` |
| Guides / banners | **Yes** | `GET /api/client/guides`, `GET /api/client/banners` |
| Complaints | **Yes** | CRUD-style routes under `/api/client/*complaint*` |
| Pickup schedule / dates | **Yes** | `GET /api/client/get_pickup_dates` |
| Bulk waste / pickups | **Yes** | create, update, list, show, delete (bulk request endpoints added), plus pickup history/single |
| History (pickup + payment) | **Yes** | pickups + `get_payment_history`, purchases |
| Violations (read-only) | **Yes** | list + single |
| Store (products, cart, checkout, orders) | **Yes** | products, cart, checkout, purchases, payment |

### Provider

| Module | Implemented | Notes |
|--------|-------------|--------|
| Dashboard + handover counts | **Yes** | `GET /api/provider/dashboard`, handover routes |
| Guides / banners | **Yes** | |
| Customers | **Yes** | register, list, show, update, delete, status |
| Groups | **Yes** | full CRUD-style |
| Drivers | **Yes** | |
| Fleet | **Yes** | |
| Route planner + assignment logs | **Yes** | plans, logs, bin scan status |
| Pickup (provider) | **Yes** | creation, list/show, update/delete, price/date, scans |
| Bulk waste requests (provider) | **Yes** | list, show, status update |
| Payments | **Yes** | list/show + `bins`, `waste_handover_request`, `weighbridge_records` (client pays registration, not provider) |
| Products | **Yes** | provider CRUD |
| Violations | **Yes** | |
| Complaints (client complaints for provider) | **Yes** | |
| Handover | **Yes** | |
| Reports | **Yes** | `GET /api/provider/reports` |
| Weighbridge (provider) | **Partial** | provider now has payment/reporting endpoint for weighbridge records; CRUD remains under facility module |
| Teams | **N/A** | Team/RBAC routes removed from codebase |

### Facility

| Module | Implemented | Notes |
|--------|-------------|--------|
| Dashboard | **Yes** | `GET /api/facility/dashboard` |
| Weighbridge | **Yes** | register, list, show, update, delete, status |
| Reports | **Yes** | `GET /api/facility/reports` |
| Facility-only “payment management” CRUD | **Gap / partial** | No separate payment routes; check `ReportsController` / future `Payment` module |
| Teams | **N/A** | |

### MMDA (`district_assembly`)

| Module | Implemented | Notes |
|--------|-------------|--------|
| Dashboard + assignment logs | **Yes** | |
| Providers / facilities / zones | **Yes** | list + single + onboarding |
| Complaints | **Yes** | list, single, status |
| Onboarding | **Yes** | `register_provider`, `register_facility` |
| Reports | **Yes** | `GET /api/district_assembly/reports` |
| Teams | **N/A** | |

### Super Admin (`admin`)

| Module | Implemented | Notes |
|--------|-------------|--------|
| Dashboard / global stats | **Partial** | `actors_statistics`, `reports`, assignment logs |
| Provider / facility / MMDA / zone / complaint / violation / product | **Yes** | |
| Orders | **Yes** | `update_purchase_status` |
| Banners / guides | **Yes** | |
| Teams | **N/A** | |

## 5. Gaps & recommendations (non-RBAC)

1. **Response envelope:** Plan migration to `{ success, message, data, meta }` or document that the current shape is canonical.
2. **REST naming:** Optionally add **v2** routes with RESTful resource names without removing v1 until clients migrate.
3. **Pagination:** Standardize `page`, `per_page`, and `meta` on list endpoints.
4. **Client-zone deprecation:** keep DB column for backward compatibility but avoid new app logic depending on `clients.zone_slug`; derive zone from provider zone assignments.
5. **Postman:** Regenerate and re-import the generated collection after route additions.
6. **Tests:** Expand feature tests per actor; add regression tests for client registration-payment gate and verify-account endpoints.

## 6. Artifacts in this repo

| File | Purpose |
|------|---------|
| `php artisan route:list --json` | Exports authoritative live route inventory for Postman/manual sync |
| `postman/Waste_Removal_API.generated.postman_collection.json` | Import into Postman |
| `API_PAYLOADS.md` | Request/response examples |
| `PROJECT_WORKFLOW.md` | Flows and navigation |

## 7. How to regenerate Postman

No generator script is currently present in this repository.  
Use:

```bash
php artisan route:list --json
```

Then update/import `postman/Waste_Removal_API.generated.postman_collection.json` after aligning it with the live route list. Set collection variables: `base_url`, `*_token`.

---

*This audit is descriptive; it does not replace manual QA or security review.*
