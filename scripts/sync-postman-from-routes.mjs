/**
 * Adds any routes from `php artisan route:list --json` that are missing from the
 * Postman collection, with example bodies and Bearer auth variables.
 *
 * Usage: node scripts/sync-postman-from-routes.mjs
 *
 * Requires: PHP / Laravel in PATH from project root.
 */
import { execSync } from "child_process";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, "..");
const collectionPath = path.join(
  root,
  "Waste Removal Live - Importable (Backend Aligned).postman_collection.json"
);

function laravelUriToPostman(uri) {
  const parts = uri
    .split("/")
    .filter(Boolean)
    .map((seg) =>
      seg.startsWith("{") && seg.endsWith("}") ? `{{${seg.slice(1, -1)}}}` : seg
    );
  return parts;
}

function normalizeMethods(methodField) {
  const m = methodField.split("|").filter((x) => x !== "HEAD");
  return m.length ? m : ["GET"];
}

function getAuthBearer(middleware) {
  const mw = middleware || [];
  if (mw.some((x) => x.includes("auth:client"))) return "{{client_live_token}}";
  if (mw.some((x) => x.includes("auth:provider"))) return "{{provider_live_token}}";
  if (mw.some((x) => x.includes("auth:facility"))) return "{{facility_live_token}}";
  if (mw.some((x) => x.includes("auth:district_assembly")))
    return "{{district_assembly_live_token}}";
  if (mw.some((x) => x.includes("auth:admin"))) return "{{admin_live_token}}";
  if (mw.some((x) => x.includes("auth:api"))) return "{{passport_access_token}}";
  return null;
}

/** Minimal example bodies keyed by METHOD + space + uri (Laravel style) */
const BODY_BY_KEY = {
  "POST api/client/login": `{\n  "emailOrPhone": "user@example.com",\n  "password": "your-password"\n}`,
  "POST api/client/reset_password_notification": `{\n  "emailOrPhone": "user@example.com"\n}`,
  "POST api/client/reset_password": `{\n  "password": "NewPass1!",\n  "confirm_password": "NewPass1!",\n  "client_slug": "{{client_slug}}",\n  "otp": "000000"\n}`,
  "POST api/client/change_password": `{\n  "current_password": "old",\n  "password": "NewPass1!",\n  "password_confirmation": "NewPass1!"\n}`,
  "POST api/client/create_complaint": `{\n  "title": "Missed pickup",\n  "location": "House 12",\n  "description": "Details",\n  "images": []\n}`,
  "POST api/client/create_bulk_waste_request": `{\n  "title": "Bulk pickup",\n  "category": "bulky",\n  "description": "Description",\n  "location": "Accra",\n  "images": []\n}`,
  "POST api/client/create_feedback": `{\n  "title": "Feedback",\n  "message": "Text"\n}`,
  "POST api/client/create_purchase": `{\n  "items": [\n    { "product_slug": "{{product_slug}}", "quantity": 1 }\n  ]\n}`,
  "POST api/client/process_payment/{purchase}": `{\n  "transaction_id": "{{transaction_id}}",\n  "payment_method": "momo",\n  "network": "MTN",\n  "phone_number": "233500000000",\n  "name": "Jane Doe"\n}`,
  "POST api/client/cart/add_item": `{\n  "product_slug": "{{product_slug}}",\n  "quantity": 1\n}`,
  "PUT api/client/cart/update_item/{product_slug}": `{\n  "quantity": 2\n}`,
  "POST api/client/cart/checkout": `{}`,
  "POST api/client/update_status": `{\n  "code": "{{pickup_code}}",\n  "status": "cancelled"\n}`,
  "POST api/client/reschedule_pickup": `{\n  "code": "{{pickup_code}}",\n  "new_date": "2026-03-25"\n}`,
  "POST api/client/update_complaint/{complaint}": `{\n  "title": "Updated",\n  "description": "..."\n}`,

  "POST api/provider/login": `{\n  "emailOrPhone": "provider@example.com",\n  "password": "your-password"\n}`,
  "POST api/provider/register_client": `{\n  "first_name": "Ama",\n  "last_name": "Mensah",\n  "phone_number": "233500000001",\n  "email": "ama@example.com",\n  "gps_address": "GA-123-4567",\n  "type": "residential",\n  "pickup_location": "Front gate",\n  "bin_size": "120L",\n  "bin_code": "BIN-0001",\n  "group_id": "{{group_slug}}"\n}`,
  "POST api/provider/scan_qrcode": `{\n  "qrcode_data": "{\\\\\\"client_slug\\\\\\":\\\\\\"{{client_slug}}\\\\\\",\\\\\\"bin_code\\\\\\":\\\\\\"BIN-001\\\\\\"}"\n}`,
  "POST api/provider/create_violation": `{\n  "client_slug": "{{client_slug}}",\n  "type": "waste_contamination",\n  "location": "GA-123",\n  "description": "Optional",\n  "images": []\n}`,
  "POST api/provider/create_plan": `{\n  "provider_slug": "{{provider_slug}}",\n  "driver_slug": "{{driver_slug}}",\n  "fleet_slug": "{{fleet_slug}}",\n  "group_slug": "{{group_slug}}"\n}`,
  "POST api/provider/change_scan_status": `{\n  "code": "{{pickup_code}}",\n  "status": "scanned"\n}`,
  "POST api/provider/handover_requests": `{\n  "title": "Handover",\n  "waste_types": ["mixed_waste"],\n  "description": "Need truck",\n  "pickup_location": "Zone A",\n  "fee_amount": 25,\n  "target_provider_slug": "{{target_provider_slug}}",\n  "images": []\n}`,
  "POST api/provider/handover_requests/{handover}/complete": `{\n  "transaction_id": "{{transaction_id}}",\n  "payment_method": "momo",\n  "network": "MTN",\n  "phone_number": "233500000000",\n  "name": "Payer"\n}`,
  "POST api/provider/handover_requests/{handover}/decline": `{}`,
  "POST api/provider/update_client_status": `{\n  "client_slug": "{{client_slug}}",\n  "status": "active"\n}`,
  "POST api/provider/update_driver_status": `{\n  "driver_slug": "{{driver_slug}}",\n  "status": "active"\n}`,
  "POST api/provider/update_fleet_status": `{\n  "fleet_slug": "{{fleet_slug}}",\n  "status": "active"\n}`,
  "POST api/provider/update_group_status": `{\n  "group_slug": "{{group_slug}}",\n  "status": "active"\n}`,
  "POST api/provider/update_plan_status": `{\n  "plan_slug": "{{plan}}",\n  "status": "active"\n}`,
  "POST api/provider/set_pickup_price": `{\n  "pickup_code": "{{pickup_code}}",\n  "amount": 50\n}`,
  "POST api/provider/set_pickup_date": `{\n  "pickup_code": "{{pickup_code}}",\n  "pickup_date": "2026-03-20"\n}`,
  "POST api/provider/manual_bin_code_scan": `{\n  "bin_code": "{{bin_code}}"\n}`,
  "POST api/provider/provider_pickup_creation": `{\n  "client_slug": "{{client_slug}}",\n  "title": "Pickup",\n  "category": "regular",\n  "description": "",\n  "location": "Accra"\n}`,

  "POST api/facility/login": `{\n  "emailOrPhone": "facility@example.com",\n  "password": "your-password"\n}`,
  "POST api/facility/register_weigh_bridge_entry": `{\n  "provider_slug": "{{provider_slug}}",\n  "fleet_slug": "{{fleet_slug}}",\n  "fleet_code": "FLT-001",\n  "gross_weight": 1000.5,\n  "amount": 100,\n  "payment_status": "paid",\n  "scan_status": "scanned",\n  "notes": ""\n}`,
  "POST api/facility/update_weigh_bridge_entry_status": `{\n  "id": 1,\n  "payment_status": "paid",\n  "scan_status": "scanned"\n}`,

  "POST api/district_assembly/login": `{\n  "emailOrPhone": "mmda@example.com",\n  "password": "your-password"\n}`,
  "POST api/district_assembly/register_provider": `{\n  "first_name": "Ama",\n  "last_name": "Mensah",\n  "email": "p@example.com",\n  "phone_number": "233500000000",\n  "business_name": "Co",\n  "business_registration_number": "BRN",\n  "gps_address": "GA-1",\n  "region": "Greater Accra",\n  "location": "Accra",\n  "zone_slug": "{{zone_slug}}",\n  "business_certificate_image": ""\n}`,
  "POST api/district_assembly/register_facility": `{\n  "region": "Greater Accra",\n  "district": "District",\n  "name": "Site",\n  "email": "f@example.com",\n  "phone_number": "233500000111",\n  "gps_address": "GA-2",\n  "first_name": "John",\n  "last_name": "Doe",\n  "business_registration_name": "BRN",\n  "district_assembly": "{{district_assembly_slug}}",\n  "business_certificate_image": ""\n}`,
  "PUT api/district_assembly/update_complaint_status/{complaint}": `{\n  "status": "resolved"\n}`,
  "PUT api/district_assembly/update_provider_status/{provider}": `{\n  "status": "active"\n}`,
  "PUT api/district_assembly/update_facility_status/{facility}": `{\n  "status": "active"\n}`,

  "POST api/admin/login": `{\n  "emailOrPhone": "admin@example.com",\n  "password": "your-password"\n}`,
  "POST api/admin/register_admin": `{\n  "first_name": "Super",\n  "last_name": "Admin",\n  "email": "admin@example.com",\n  "phone_number": "233500000000",\n  "password": "Passw0rd!123",\n  "password_confirmation": "Passw0rd!123"\n}`,
  "POST api/admin/register_provider": `{\n  "first_name": "A",\n  "last_name": "B",\n  "email": "p@example.com",\n  "phone_number": "233500000000",\n  "business_registration_number": "BRN",\n  "gps_address": "GA-1",\n  "zone_slug": "{{zone_slug}}",\n  "region": "Greater Accra",\n  "location": "Accra",\n  "business_certificate_image": "",\n  "epa_permit_image": ""\n}`,
  "POST api/admin/register_facility": `{\n  "name": "Facility",\n  "email": "f@example.com",\n  "phone_number": "233500000111",\n  "district_assembly": "{{district_assembly_slug}}",\n  "region": "Greater Accra",\n  "district": "D",\n  "gps_address": "GA-2",\n  "first_name": "J",\n  "last_name": "D",\n  "business_registration_name": "BRN",\n  "business_certificate_image": ""\n}`,
  "POST api/admin/register_district_assembly": `{\n  "region": "Volta",\n  "district": "Muni",\n  "email": "m@example.com",\n  "phone_number": "233500000222",\n  "gps_address": "GA-3",\n  "first_name": "M",\n  "last_name": "D",\n  "profile_image": ""\n}`,
  "POST api/admin/create_zone": `{\n  "name": "Zone A",\n  "city": "Accra",\n  "town": "Tema",\n  "description": ""\n}`,
  "POST api/admin/update_zone_status": `{\n  "zone_slug": "{{zone_slug}}",\n  "status": "active"\n}`,
  "POST api/admin/update_provider_status": `{\n  "provider_slug": "{{provider_slug}}",\n  "status": "active"\n}`,
  "POST api/admin/update_facility_status": `{\n  "facility_slug": "{{facility_slug}}",\n  "status": "active"\n}`,
  "POST api/admin/update_district_assembly_status": `{\n  "district_assembly_slug": "{{district_assembly_slug}}",\n  "status": "active"\n}`,
  "POST api/admin/banners": `{\n  "title": "Notice",\n  "message": "Text",\n  "audience": "all",\n  "status": "active",\n  "starts_at": "2026-01-01",\n  "ends_at": "2026-12-31",\n  "image": []\n}`,
  "PUT api/admin/banners/{banner}": `{\n  "title": "Updated",\n  "message": "Text",\n  "status": "active"\n}`,
  "POST api/admin/guides": `{\n  "title": "Guide",\n  "category": "bin_use",\n  "content": "HTML or text",\n  "audience": "client",\n  "status": "active",\n  "attachments": []\n}`,
  "PUT api/admin/guides/{guide}": `{\n  "title": "Updated",\n  "content": "..."\n}`,
  "PUT api/admin/update_purchase_status/{purchase}": `{\n  "status": "confirmed"\n}`,
  "POST api/admin/create_product": `{\n  "name": "Bin liner",\n  "description": "",\n  "price": 10,\n  "category": "consumables",\n  "stock": 100,\n  "images": []\n}`,
  "PUT api/admin/update_product/{product}": `{\n  "name": "Updated",\n  "price": 12\n}`,
};

function bodyForRoute(method, uri) {
  const key = `${method} ${uri}`;
  if (BODY_BY_KEY[key]) return BODY_BY_KEY[key];
  const noPurchase = uri.replace("/{purchase}", "/{purchase}");
  return null;
}

function getPathPartsFromItem(item) {
  const r = item.request;
  if (!r?.url) return null;
  if (r.url.path?.length) return r.url.path;
  if (r.url.raw) {
    const raw = r.url.raw.replace(/\{\{[^}]+\}\}/g, "x");
    try {
      const base = raw.startsWith("http") ? raw : "http://localhost" + raw;
      const u = new URL(base);
      return u.pathname.split("/").filter(Boolean);
    } catch {
      return null;
    }
  }
  return null;
}

function routeCovered(artisanUri, method, postmanRequests) {
  const aParts = artisanUri.split("/").filter(Boolean);
  for (const item of postmanRequests) {
    const pm = item.request?.method;
    if (pm !== method) continue;
    const pParts = getPathPartsFromItem(item);
    if (!pParts || pParts.length !== aParts.length) continue;
    let ok = true;
    for (let i = 0; i < aParts.length; i++) {
      const a = aParts[i];
      const p = pParts[i];
      if (a.startsWith("{") && a.endsWith("}")) continue;
      if (p.startsWith("{{") && p.endsWith("}}")) continue;
      if (p.startsWith("<") && p.endsWith(">")) continue;
      if (a !== p) {
        ok = false;
        break;
      }
    }
    if (ok) return true;
  }
  return false;
}

function collectRequests(items, out = []) {
  if (!items) return out;
  for (const it of items) {
    if (it.request) out.push(it);
    if (it.item) collectRequests(it.item, out);
  }
  return out;
}

function targetFolderName(uri) {
  if (uri.startsWith("api/client/")) {
    const rest = uri.replace("api/client/", "");
    const first = rest.split("/")[0];
    if (
      [
        "dashboard",
        "banners",
        "guides",
      ].includes(first) ||
      rest.startsWith("guides")
    )
      return { root: "Client/Citizen", sub: "Dashboard & guides (backend aligned)" };
    if (
      /^(get_products|get_single_product|cart|create_purchase|get_purchases|get_single_purchase|process_payment|get_payment_history)/.test(
        rest
      ) ||
      rest.includes("cart/")
    )
      return { root: "Client/Citizen", sub: "Store (backend aligned)" };
    return { root: "Client/Citizen", sub: "Route coverage (auto — from artisan)" };
  }
  if (uri.startsWith("api/provider/")) {
    const rest = uri.replace("api/provider/", "");
    const first = rest.split("/")[0];
    if (
      ["dashboard", "banners", "guides", "payments", "reports"].includes(first) ||
      rest.startsWith("handover_requests")
    )
      return {
        root: "Provider",
        sub: "Dashboard, handover & payments (backend aligned)",
      };
    return { root: "Provider", sub: "Route coverage (auto — from artisan)" };
  }
  if (uri.startsWith("api/facility/")) {
    if (
      uri.includes("weigh_bridge") ||
      uri.includes("register_weigh") ||
      uri.includes("all_weigh")
    )
      return { root: "Facility", sub: "Weighbridge (backend aligned)" };
    if (uri.includes("dashboard") || uri.includes("reports"))
      return { root: "Facility", sub: "Dashboard & reports (backend aligned)" };
    return { root: "Facility", sub: "Route coverage (auto — from artisan)" };
  }
  if (uri.startsWith("api/district_assembly/")) {
    return {
      root: "District Assembly",
      sub: "MMDA operations (backend aligned)",
    };
  }
  if (uri.startsWith("api/admin/")) {
    return {
      root: "Super Administrator",
      sub: "Admin API additions (backend aligned)",
    };
  }
  return { root: "__misc__", sub: "Misc API (from artisan sync)" };
}

function ensureSubfolder(parent, folderName, atStart = false) {
  if (!parent.item) parent.item = [];
  let f = parent.item.find((i) => i.name === folderName && Array.isArray(i.item));
  if (!f) {
    f = { name: folderName, item: [] };
    if (atStart) parent.item.unshift(f);
    else parent.item.push(f);
  }
  return f;
}

function findRoot(items, name) {
  return items?.find((i) => i.name === name);
}

function buildRequest(method, uri, middleware) {
  const pathParts = laravelUriToPostman(uri);
  const raw = `{{wms_domain}}/` + pathParts.join("/");

  const bearer = getAuthBearer(middleware);
  const auth =
    bearer === null
      ? { type: "noauth" }
      : {
          type: "bearer",
          bearer: [{ key: "token", value: bearer, type: "string" }],
        };

  const bodyRaw = bodyForRoute(method, uri);
  const headers =
    bodyRaw && ["POST", "PUT", "PATCH"].includes(method)
      ? [{ key: "Content-Type", value: "application/json" }]
      : [];

  const req = {
    method,
    header: headers,
    auth,
    url: {
      raw,
      host: ["{{wms_domain}}"],
      path: pathParts,
    },
  };

  if (bodyRaw && ["POST", "PUT", "PATCH"].includes(method)) {
    req.body = {
      mode: "raw",
      raw: bodyRaw,
      options: { raw: { language: "json" } },
    };
  }

  const name = `${method} ${uri.replace(/^api\//, "")}`.slice(0, 120);

  return {
    name,
    request: req,
    response: [],
  };
}

// --- main
const json = execSync("php artisan route:list --json", {
  cwd: root,
  encoding: "utf8",
});
const allRoutes = JSON.parse(json);

const apiRoutes = allRoutes.filter((r) => {
  const u = r.uri || "";
  return u.startsWith("api/") && !u.startsWith("api/oauth");
});

const collection = JSON.parse(fs.readFileSync(collectionPath, "utf8"));
const existing = collectRequests(collection.item);

const added = [];
for (const route of apiRoutes) {
  const uri = route.uri.replace(/^\//, "");
  const methods = normalizeMethods(route.method);
  for (const method of methods) {
    if (routeCovered(uri, method, existing)) continue;
    const item = buildRequest(method, uri, route.middleware);

    const { root, sub } = targetFolderName(uri);
    if (root === "__misc__") {
      let miscTop = collection.item.find((i) => i.name === "Misc API (from artisan sync)");
      if (!miscTop) {
        miscTop = { name: "Misc API (from artisan sync)", item: [] };
        collection.item.push(miscTop);
      }
      const subFolder = ensureSubfolder(miscTop, sub, false);
      subFolder.item.push(item);
      existing.push(item);
      added.push(`${method} ${uri}`);
      continue;
    }

    const rootNode = findRoot(collection.item, root);
    if (!rootNode) continue;
    const subFolder = ensureSubfolder(rootNode, sub, false);
    subFolder.item.push(item);
    existing.push(item);
    added.push(`${method} ${uri}`);
  }
}

fs.writeFileSync(collectionPath, JSON.stringify(collection, null, 2) + "\n");
console.log(`sync-postman-from-routes: added ${added.length} requests.`);
added.forEach((a) => console.log("  +", a));
