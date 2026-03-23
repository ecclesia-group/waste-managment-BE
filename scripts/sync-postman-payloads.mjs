import fs from "fs";
import path from "path";
import { execSync } from "child_process";

const root = process.cwd();
const writeMethods = new Set(["POST", "PUT", "PATCH"]);

const BODY_BY_KEY = {
  "POST api/client/login": `{"emailOrPhone":"user@example.com","password":"your-password"}`,
  "POST api/client/reset_password_notification": `{"emailOrPhone":"user@example.com"}`,
  "POST api/client/reset_password": `{"password":"NewPass1!","confirm_password":"NewPass1!","client_slug":"{{client_slug}}","otp":"000000"}`,
  "POST api/client/change_password": `{"current_password":"old","password":"NewPass1!","password_confirmation":"NewPass1!"}`,
  "POST api/client/create_complaint": `{"title":"Missed pickup","location":"House 12","description":"Details","images":[]}`,
  "POST api/client/create_bulk_waste_request": `{"title":"Bulk pickup","category":"bulky","description":"Description","location":"Accra","images":[]}`,
  "POST api/client/create_feedback": `{"title":"Feedback","message":"Text"}`,
  "POST api/client/create_purchase": `{"items":[{"product_slug":"{{product_slug}}","quantity":1}]}`,
  "POST api/client/process_payment/{purchase}": `{"transaction_id":"{{transaction_id}}","payment_method":"momo","network":"MTN","phone_number":"233500000000","name":"Jane Doe"}`,
  "POST api/client/cart/add_item": `{"product_slug":"{{product_slug}}","quantity":1}`,
  "PUT api/client/cart/update_item/{product_slug}": `{"quantity":2}`,
  "POST api/client/cart/checkout": `{}`,
  "POST api/client/update_status": `{"code":"{{pickup_code}}","status":"cancelled"}`,
  "POST api/client/reschedule_pickup": `{"code":"{{pickup_code}}","new_date":"2026-03-25"}`,
  "POST api/provider/login": `{"emailOrPhone":"provider@example.com","password":"your-password"}`,
  "POST api/provider/register_client": `{"first_name":"Ama","last_name":"Mensah","phone_number":"233500000001","email":"ama@example.com","gps_address":"GA-123-4567","type":"residential","pickup_location":"Front gate","bin_size":"120L","bin_code":"BIN-0001","group_id":"{{group_slug}}"}`,
  "POST api/provider/create_violation": `{"client_slug":"{{client_slug}}","type":"waste_contamination","location":"GA-123","description":"Optional","images":[]}`,
  "POST api/provider/create_plan": `{"provider_slug":"{{provider_slug}}","driver_slug":"{{driver_slug}}","fleet_slug":"{{fleet_slug}}","group_slug":"{{group_slug}}"}`,
  "POST api/provider/change_scan_status": `{"code":"{{pickup_code}}","status":"scanned"}`,
  "POST api/provider/handover_requests": `{"title":"Handover","waste_types":["mixed_waste"],"description":"Need truck","pickup_location":"Zone A","fee_amount":25,"target_provider_slug":"{{target_provider_slug}}","images":[]}`,
  "POST api/provider/handover_requests/{handover}/decline": `{}`,
  "POST api/provider/handover_requests/{handover}/complete": `{"transaction_id":"{{transaction_id}}","payment_method":"momo","network":"MTN","phone_number":"233500000000","name":"Payer"}`,
  "POST api/provider/set_pickup_price": `{"pickup_code":"{{pickup_code}}","amount":50}`,
  "POST api/provider/set_pickup_date": `{"pickup_code":"{{pickup_code}}","pickup_date":"2026-03-20"}`,
  "POST api/provider/manual_bin_code_scan": `{"bin_code":"{{bin_code}}"}`,
  "POST api/provider/provider_pickup_creation": `{"client_slug":"{{client_slug}}","title":"Pickup","category":"regular","description":"","location":"Accra"}`,
  "POST api/facility/login": `{"emailOrPhone":"facility@example.com","password":"your-password"}`,
  "POST api/facility/register_weigh_bridge_entry": `{"provider_slug":"{{provider_slug}}","fleet_slug":"{{fleet_slug}}","fleet_code":"FLT-001","gross_weight":1000.5,"amount":100,"payment_status":"paid","scan_status":"scanned","notes":""}`,
  "POST api/facility/update_weigh_bridge_entry_status": `{"id":1,"payment_status":"paid","scan_status":"scanned"}`,
  "POST api/district_assembly/login": `{"emailOrPhone":"mmda@example.com","password":"your-password"}`,
  "POST api/district_assembly/register_provider": `{"first_name":"Ama","last_name":"Mensah","email":"p@example.com","phone_number":"233500000000","business_name":"Co","business_registration_number":"BRN","gps_address":"GA-1","region":"Greater Accra","location":"Accra","zone_slugs":["{{zone_slug}}"],"business_certificate_image":""}`,
  "POST api/district_assembly/register_facility": `{"region":"Greater Accra","district":"District","name":"Site","email":"f@example.com","phone_number":"233500000111","gps_address":"GA-2","first_name":"John","last_name":"Doe","business_registration_name":"BRN","district_assembly":"{{district_assembly_slug}}","business_certificate_image":""}`,
  "PUT api/district_assembly/update_complaint_status/{complaint}": `{"status":"resolved"}`,
  "PUT api/district_assembly/update_provider_status/{provider}": `{"status":"deactivate","suspension_reason":"Repeated SLA breaches","corrective_action":"Submit recovery plan and pass review"}`,
  "PUT api/district_assembly/update_facility_status/{facility}": `{"status":"deactivate","suspension_reason":"Health and safety issue","corrective_action":"Provide compliance documents and pass inspection"}`,
  "POST api/admin/login": `{"emailOrPhone":"admin@example.com","password":"your-password"}`,
  "POST api/admin/register_admin": `{"first_name":"Super","last_name":"Admin","email":"admin@example.com","phone_number":"233500000000","password":"Passw0rd!123","password_confirmation":"Passw0rd!123"}`,
  "POST api/admin/register_provider": `{"first_name":"A","last_name":"B","email":"p@example.com","phone_number":"233500000000","business_registration_number":"BRN","gps_address":"GA-1","zone_slugs":["{{zone_slug}}"],"region":"Greater Accra","location":"Accra","business_certificate_image":"","epa_permit_image":""}`,
  "POST api/admin/register_facility": `{"name":"Facility","email":"f@example.com","phone_number":"233500000111","district_assembly":"{{district_assembly_slug}}","region":"Greater Accra","district":"D","gps_address":"GA-2","first_name":"J","last_name":"D","business_registration_name":"BRN","business_certificate_image":""}`,
  "POST api/admin/register_district_assembly": `{"region":"Volta","district":"Muni","email":"m@example.com","phone_number":"233500000222","gps_address":"GA-3","first_name":"M","last_name":"D","profile_image":""}`,
  "POST api/admin/create_zone": `{"name":"Zone A","city":"Accra","town":"Tema","description":""}`,
  "POST api/admin/update_zone_status": `{"zone_slug":"{{zone_slug}}","status":"active"}`,
  "POST api/admin/update_provider_status": `{"provider_slug":"{{provider_slug}}","status":"deactivate","suspension_reason":"Operations paused due to compliance issue","corrective_action":"Upload valid compliance certificates"}`,
  "POST api/admin/update_facility_status": `{"facility_slug":"{{facility_slug}}","status":"deactivate","suspension_reason":"Site audit not passed","corrective_action":"Complete remediation checklist and request re-inspection"}`,
  "POST api/admin/update_district_assembly_status": `{"district_assembly_slug":"{{district_assembly_slug}}","status":"deactivate","suspension_reason":"Administrative review pending","corrective_action":"Resolve outstanding policy review items"}`,
  "POST api/admin/banners": `{"title":"Notice","message":"Text","audience":"all","status":"active","starts_at":"2026-01-01","ends_at":"2026-12-31","image":[]}`,
  "PUT api/admin/banners/{banner}": `{"title":"Updated","message":"Text","status":"active"}`,
  "POST api/admin/guides": `{"title":"Guide","category":"bin_use","content":"HTML or text","audience":"client","status":"active","attachments":[]}`,
  "PUT api/admin/guides/{guide}": `{"title":"Updated","content":"..."}`,
  "PUT api/admin/update_purchase_status/{purchase}": `{"status":"confirmed"}`,
  "POST api/admin/create_product": `{"name":"Bin liner","description":"","price":10,"category":"consumables","stock":100,"images":[]}`,
  "PUT api/admin/update_product/{product}": `{"name":"Updated","price":12}`,
  "POST api/admin/providers/{provider}/zones": `{"zone_slugs":["{{zone_slug}}"]}`,
};

function walkForCollections(dir, out = []) {
  for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
    if (ent.name === ".git" || ent.name === "node_modules") continue;
    const p = path.join(dir, ent.name);
    if (ent.isDirectory()) walkForCollections(p, out);
    else if (ent.isFile() && ent.name.endsWith(".postman_collection.json")) out.push(p);
  }
  return out;
}

function collectRequests(items, out = []) {
  for (const item of items || []) {
    if (item.request) out.push(item);
    if (item.item) collectRequests(item.item, out);
  }
  return out;
}

function normalizeMethods(methodField) {
  const methods = (methodField || "GET").split("|").filter((m) => m !== "HEAD");
  return methods.length ? methods : ["GET"];
}

function getRequestPath(item) {
  const url = item.request?.url;
  if (!url) return null;
  if (Array.isArray(url.path) && url.path.length) return url.path.join("/");
  if (typeof url.raw === "string" && url.raw.length) {
    const noHost = url.raw
      .replace(/^https?:\/\/[^/]+\//, "")
      .replace(/^\{\{[^}]+\}\}\//, "")
      .replace(/^\//, "");
    return noHost.split("?")[0];
  }
  return null;
}

const routes = JSON.parse(execSync("php artisan route:list --json", { encoding: "utf8" })).filter(
  (r) => r.uri?.startsWith("api/") && !r.uri.startsWith("api/oauth")
);
const routeMethodSet = new Set();
for (const route of routes) {
  for (const method of normalizeMethods(route.method)) {
    routeMethodSet.add(`${method} ${route.uri}`);
  }
}

const uniqueFiles = [...new Set(walkForCollections(root))];
let totalPatched = 0;

for (const filePath of uniqueFiles) {
  const collection = JSON.parse(fs.readFileSync(filePath, "utf8"));
  const requests = collectRequests(collection.item);
  let patched = 0;

  for (const reqItem of requests) {
    const method = reqItem.request?.method || "GET";
    if (!writeMethods.has(method)) continue;

    const pathFromReq = getRequestPath(reqItem);
    if (!pathFromReq) continue;

    const normalizedPath = pathFromReq.replace(/\{\{([^}]+)\}\}/g, "{$1}");
    const key = `${method} ${normalizedPath}`;
    if (!routeMethodSet.has(key)) continue;

    const hasBody = typeof reqItem.request?.body?.raw === "string" && reqItem.request.body.raw.trim().length > 0;
    if (hasBody) continue;

    reqItem.request.body = {
      mode: "raw",
      raw: BODY_BY_KEY[key] || "{}",
      options: { raw: { language: "json" } },
    };
    reqItem.request.header = reqItem.request.header || [];
    if (!reqItem.request.header.some((h) => String(h?.key || "").toLowerCase() === "content-type")) {
      reqItem.request.header.push({ key: "Content-Type", value: "application/json", type: "text" });
    }
    patched++;
  }

  if (patched > 0) {
    fs.writeFileSync(filePath, JSON.stringify(collection, null, 2) + "\n");
  }
  totalPatched += patched;
  console.log(`${path.relative(root, filePath)}: patched ${patched}`);
}

console.log(`TOTAL_PATCHED ${totalPatched}`);
