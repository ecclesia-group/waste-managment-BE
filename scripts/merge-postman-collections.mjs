/**
 * Merges missing requests from the reference Postman collection into the live one.
 * Run from repo root: node scripts/merge-postman-collections.mjs
 */
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, "..");

const livePath = path.join(
  root,
  "Waste Removal Live - Importable (Backend Aligned).postman_collection.json"
);
const refPath = path.join(
  root,
  "Don't use this for waste removal project, reference above.postman_collection.json"
);

const live = JSON.parse(fs.readFileSync(livePath, "utf8"));
const ref = JSON.parse(fs.readFileSync(refPath, "utf8"));

function normalizeTokens(str) {
  return str
    .replace(/\{\{client_local_token\}\}/g, "{{client_live_token}}")
    .replace(/\{\{provider_local_token\}\}/g, "{{provider_live_token}}")
    .replace(/\{\{facility_local_token\}\}/g, "{{facility_live_token}}")
    .replace(/\{\{admin_local_token\}\}/g, "{{admin_live_token}}")
    .replace(
      /\{\{district_assembly_local_token\}\}/g,
      "{{district_assembly_live_token}}"
    );
}

function deepCloneNormalize(obj) {
  return JSON.parse(normalizeTokens(JSON.stringify(obj)));
}

function getPathParts(item) {
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

function requestSignature(item) {
  const r = item.request;
  if (!r?.method) return null;
  const pathParts = getPathParts(item);
  if (!pathParts?.length) return null;
  return `${r.method} ${pathParts.join("/")}`;
}

function collectSignatures(items, set = new Set()) {
  if (!items) return set;
  for (const it of items) {
    if (it.request) {
      const s = requestSignature(it);
      if (s) set.add(s);
    }
    if (it.item) collectSignatures(it.item, set);
  }
  return set;
}

function collectRequests(items, out = []) {
  if (!items) return out;
  for (const it of items) {
    if (it.request) out.push(it);
    if (it.item) collectRequests(it.item, out);
  }
  return out;
}

function findRoot(items, name) {
  return items?.find((i) => i.name === name);
}

function ensureSubfolder(parent, folderName, atStart = true) {
  if (!parent.item) parent.item = [];
  let f = parent.item.find((i) => i.name === folderName && Array.isArray(i.item));
  if (!f) {
    f = { name: folderName, item: [] };
    if (atStart) parent.item.unshift(f);
    else parent.item.push(f);
  }
  return f;
}

function targetFolder(pathParts) {
  if (!pathParts?.length) return null;
  const p = pathParts.join("/");
  const scope = pathParts[1];

  if (scope === "client") {
    const seg = pathParts[2] ?? "";
    if (seg === "dashboard" || seg === "banners" || seg === "guides") {
      return { root: "Client/Citizen", sub: "Dashboard & guides (backend aligned)" };
    }
    if (
      seg.startsWith("get_products") ||
      seg.startsWith("get_single_product") ||
      seg === "cart" ||
      seg.startsWith("create_purchase") ||
      seg.startsWith("get_purchases") ||
      seg.startsWith("get_payment_history") ||
      seg.startsWith("process_payment") ||
      p.includes("/cart/")
    ) {
      return { root: "Client/Citizen", sub: "Store (backend aligned)" };
    }
  }

  if (scope === "provider") {
    const seg2 = pathParts[2];
    if (
      seg2 === "login" ||
      seg2 === "logout" ||
      seg2 === "reset_password" ||
      seg2 === "reset_password_notification" ||
      seg2 === "change_password" ||
      seg2 === "resend_verificationCode"
    ) {
      return null;
    }
    if (
      seg2 === "dashboard" ||
      seg2 === "banners" ||
      seg2 === "guides" ||
      seg2 === "payments" ||
      seg2 === "reports" ||
      seg2 === "handover_requests" ||
      p.includes("/handover_requests/")
    ) {
      return {
        root: "Provider",
        sub: "Dashboard, handover & payments (backend aligned)",
      };
    }
    return {
      root: "Provider",
      sub: "Operations (backend aligned)",
    };
  }

  if (scope === "facility") {
    const seg2 = pathParts[2];
    if (
      seg2 === "login" ||
      seg2 === "logout" ||
      seg2 === "reset_password" ||
      seg2 === "reset_password_notification" ||
      seg2 === "change_password" ||
      seg2 === "resend_verificationCode"
    ) {
      return null;
    }
    if (seg2 === "dashboard" || seg2 === "reports") {
      return {
        root: "Facility",
        sub: "Dashboard & reports (backend aligned)",
      };
    }
    return {
      root: "Facility",
      sub: "Weighbridge (backend aligned)",
    };
  }

  if (scope === "district_assembly") {
    const seg2 = pathParts[2];
    if (
      seg2 !== "login" &&
      seg2 !== "logout" &&
      seg2 !== "reset_password" &&
      seg2 !== "reset_password_notification" &&
      seg2 !== "change_password" &&
      seg2 !== "resend_verificationCode"
    ) {
      return {
        root: "District Assembly",
        sub: "MMDA operations (backend aligned)",
      };
    }
  }

  if (scope === "admin") {
    return {
      root: "Super Administrator",
      sub: "Admin API additions (backend aligned)",
    };
  }

  return null;
}

const liveSigs = collectSignatures(live.item);
const refRequests = collectRequests(ref.item);
const added = [];

for (const it of refRequests) {
  const sig = requestSignature(it);
  if (!sig || liveSigs.has(sig)) continue;

  const pathParts = getPathParts(it);
  if (!pathParts) continue;

  const t = targetFolder(pathParts);
  if (!t) continue;
  const rootNode = findRoot(live.item, t.root);
  if (!rootNode) continue;
  const sub = ensureSubfolder(rootNode, t.sub, true);
  sub.item.push(deepCloneNormalize(it));
  liveSigs.add(sig);
  added.push(sig);
}

fs.writeFileSync(livePath, JSON.stringify(live, null, 2) + "\n");
console.log(`Merged ${added.length} new requests into live collection.`);
added.forEach((s) => console.log("  +", s));
