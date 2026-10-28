// ---------- State ----------
const STORE_KEY = "pc-crm-demo-v1";
let DB, ROLE = "admin";
function clone(o) { return JSON.parse(JSON.stringify(o)); }
function loadState() {
  DB = clone(SEED);
  try {
    const raw = localStorage.getItem(STORE_KEY);
    if (raw) { const s = JSON.parse(raw); if (s.db) DB = s.db; if (s.role) ROLE = s.role; if (s.lang) LANG = s.lang; }
  } catch (e) { /* storage unavailable: run in memory */ }
}
function saveState() { try { localStorage.setItem(STORE_KEY, JSON.stringify({ db: DB, role: ROLE, lang: LANG })); } catch (e) {} }
function resetDemo() { DB = clone(SEED); saveState(); toast(t("reset_demo")); render(); }

// ---------- Helpers ----------
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
function esc(v) { return String(v ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c])); }
function tx(o) { return o == null ? "" : typeof o === "string" ? o : (o[LANG] ?? o.en ?? ""); }
function me() { return DB.users.find(u => u.id === DB.roleUser[ROLE]); }
function user(id) { return DB.users.find(u => u.id === id); }
function uname(id) { const u = user(id); return u ? tx(u.name) : "—"; }
function client(id) { return DB.clients.find(c => c.id === id); }
function vendor(id) { return DB.vendors.find(v => v.id === id); }
function file(id) { return DB.files.find(f => f.id === id); }
function port(code) { return DB.ports[code] ? tx(DB.ports[code]) : (code || "—"); }
function isAdmin() { return ROLE === "admin"; }
function roleName(r) { return t("role_" + r); }

function fdate(iso) { if (!iso) return "—"; const [d, tm] = iso.split(" "); const [y, m, dd] = d.split("-"); return `${dd}/${m}/${y}` + (tm ? " " + tm : ""); }
function daysBetween(a, b) { return Math.round((new Date(b) - new Date(a)) / 86400000); }
function workDaysSince(iso) { // Sunday–Thursday work week
  let n = 0, d = new Date(iso + "T00:00:00Z"); const end = new Date(TODAY + "T00:00:00Z");
  while (d < end) { d.setUTCDate(d.getUTCDate() + 1); const wd = d.getUTCDay(); if (wd !== 5 && wd !== 6) n++; }
  return n;
}
const NF = new Intl.NumberFormat("en-US", { maximumFractionDigits: 2, minimumFractionDigits: 0 });
const NF2 = new Intl.NumberFormat("en-US", { maximumFractionDigits: 2, minimumFractionDigits: 2 });
const CUR_SYM = { USD: "$", EUR: "€", EGP: "EGP ", CNY: "CNY ", AED: "AED ", TRY: "TRY " };
function money(n, cur = "USD", dec) { const s = (dec ? NF2 : NF).format(n || 0); return `<span class="num ltr">${cur === "USD" || cur === "EUR" ? CUR_SYM[cur] + s : s + " " + cur}</span>`; }
function toUSD(amt, cur) { if (cur === "USD") return amt; const r = DB.rates[cur]; return r ? amt / r.v : amt; }

function pill(text, kind = "") { return `<span class="pill ${kind}">${esc(text)}</span>`; }
function stageChip(n) { return `<span class="stage-chip"><b>${n}</b>${esc(t("st" + n))}</span>`; }
function avatar(id) { const u = user(id); return `<span class="avatar" title="${esc(u ? tx(u.name) : "")}">${esc(u ? u.initials : "?")}</span>`; }

let toastTimer;
function toast(msg, bad) {
  let area = $(".toast-area"); if (!area) { area = document.createElement("div"); area.className = "toast-area"; area.setAttribute("role", "status"); document.body.appendChild(area); }
  const el = document.createElement("div"); el.className = "toast-msg" + (bad ? " bad" : ""); el.textContent = msg; area.appendChild(el);
  setTimeout(() => el.remove(), 3200);
}

const ICONS = {
  home: '<path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
  box: '<path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/>',
  plus: '<path d="M12 5v14M5 12h14"/>',
  tag: '<path d="M3 12V3h9l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
  ship: '<path d="M3 17l2 4h14l2-4H3z"/><path d="M5 17V9h14v8"/><path d="M9 9V5h6v4"/>',
  users: '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.5-3.5 3.3-6 6.5-6s6 2.5 6.5 6"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7"/><path d="M18 14c2 .6 3.3 2.8 3.5 6"/>',
  spark: '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5L18 18M6 18l2.5-2.5M15.5 8.5L18 6"/>',
  truck: '<path d="M2 6h11v10H2z"/><path d="M13 9h5l3 3v4h-8"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
  file: '<path d="M6 3h8l5 5v13H6z"/><path d="M14 3v5h5"/><path d="M9 13h7M9 17h7"/>',
  coins: '<ellipse cx="9" cy="7" rx="6" ry="3"/><path d="M3 7v5c0 1.7 2.7 3 6 3s6-1.3 6-3V7"/><path d="M9 15v2c0 1.7 2.7 3 6 3s6-1.3 6-3v-5c0-1.7-2.7-3-6-3"/>',
  chart: '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
  key: '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M17 6l3 3"/>',
  gear: '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9L7 7M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1"/>',
  bell: '<path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4z"/><path d="M10 21h4"/>',
  menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
  search: '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
  globe: '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 3 2.5 15 0 18M12 3c-2.5 3-2.5 15 0 18"/>',
  inbox: '<path d="M3 13l3-8h12l3 8v6H3z"/><path d="M3 13h5l1 3h6l1-3h5"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
  upload: '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v4h16v-4"/>',
  wa: '<path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z"/><path d="M9 9c0 3 3 6 6 6l1-1.5-2-1-1 .8c-1-.4-2-1.4-2.4-2.4l.8-1-1-2z"/>',
};
function ico(n) { return `<svg class="ico" viewBox="0 0 24 24" aria-hidden="true">${ICONS[n] || ""}</svg>`; }

// ---------- Workflow definitions ----------
const STAGES = [
  { n: 1, owner: "sales", items: [["c1_first", 0]] },
  { n: 2, owner: "sales", items: [["c2_rates", 0, "pricing"], ["c2_sent", 0], ["c2_trial", 0], ["c2_approval", 1]] },
  { n: 3, owner: "ops", items: [["c3_handover", 0, "sales"], ["c3_ready", 0], ["c3_acid", 1], ["c3_booking", 0], ["c3_docs", 0]] },
  { n: 4, owner: "ops", items: [["c4_bl", 0], ["c4_inform", 0], ["c4_broker", 0], ["c4_proforma", 0]] },
  { n: 5, owner: "customs", items: [["c5_notice", 0], ["c5_do", 0], ["c5_cleared", 0], ["c5_release", 1, "accounts"], ["c5_delivered", 1], ["c5_costs", 0, "ops"]] },
  { n: 6, owner: "accounts", items: [["c6_invoice", 0], ["c6_paid", 1], ["c6_suppliers", 0]] },
  { n: 7, owner: "sales", items: [["c7_call", 1], ["c7_feedback", 0], ["c7_next", 0], ["c7_ref", 0]] },
];
const MAIN_SERVICES = ["fcl", "lcl", "air", "land", "bulk", "roro", "se", "courier"];
const ADDONS = ["clear", "truck", "ins", "other"];
const SVC_KEY = { fcl: "sv_fcl", lcl: "sv_lcl", air: "sv_air", land: "sv_land", bulk: "sv_bulk", roro: "sv_roro", se: "sv_se", courier: "sv_courier", clear: "sv_clear", truck: "sv_truck", ins: "sv_ins", other: "sv_other" };
const CONT_TYPES = ["20'GP", "40'GP", "40'HC", "45'HC", "20'RF", "40'RF"];
const INCOTERMS_SEA = ["EXW", "FCA", "FAS", "FOB", "CFR", "CIF", "CPT", "CIP", "DAP", "DPU", "DDP"];
const INCOTERMS_OTHER = ["EXW", "FCA", "CPT", "CIP", "DAP", "DPU", "DDP"];

function itemOwner(stageN, item) { return item[2] || STAGES[stageN - 1].owner; }
function canTick(stageN, item) { return isAdmin() || ROLE === itemOwner(stageN, item); }
function canMove(f) { return isAdmin() || ROLE === STAGES[f.stage - 1].owner; }
function openBlockers(f) { return (f.dc || []).filter(i => i.sev === "blocker" && i.status === "open").length; }
function contName(c) { return c; }

// Same rules as FileStageService gates on the server.
function gateProblems(f) {
  const p = [];
  const st = STAGES[f.stage - 1];
  st.items.forEach(it => { if (it[1] && !f.checks[it[0]]) p.push(t("gate_need_check", { item: t(it[0]) })); });
  if (f.stage === 1 && inquiryProblems(f).length) p.push(t("gate_need_rfq"));
  if (f.stage === 3) { const needsBooking = f.ship?.carrier || f.service === "land" || f.addons.includes("truck"); if (needsBooking && !f.checks.c3_booking) p.push(t("gate_need_check", { item: t("c3_booking") })); }
  if (f.stage === 4) {
    const s = f.ship || {};
    if (!s.bl) p.push(t("gate_need_bl")); if (!s.atd) p.push(t("gate_need_etd")); if (!s.eta) p.push(t("gate_need_eta")); if (!s.carrier) p.push(t("gate_need_carrier"));
    const b = openBlockers(f); if (b) p.push(t("gate_need_blockers", { n: b }));
  }
  return p;
}

// Mirrors InquiryValidator::problems() on the server.
function inquiryProblems(f) {
  const p = [], q = f.inq || {};
  if (!f.client) p.push(t("p_client"));
  if (!f.service) p.push(f.addons?.includes("ins") && f.addons.length === 1 ? t("p_ins_alone") : t("p_service"));
  const rows = q.rows || [];
  if (!rows.length) p.push(t("p_rows"));
  rows.forEach((r, i) => {
    let ok = true;
    if (f.service === "fcl" || f.service === "se") ok = r.type && r.count > 0;
    else if (["lcl", "air", "courier"].includes(f.service)) ok = r.pieces > 0 && r.l > 0 && r.w > 0 && r.h > 0 && r.kg > 0;
    else if (f.service === "land") ok = r.truck && r.count > 0 && r.kg > 0;
    else if (f.service === "bulk") ok = r.tonnes > 0;
    else if (f.service === "roro") ok = r.count > 0 && r.kg > 0;
    if (!ok) p.push(t("p_row_incomplete", { n: i + 1 }));
  });
  if (!f.origin) p.push(t("p_origin")); if (!f.dest) p.push(t("p_dest")); if (!f.incoterm) p.push(t("p_incoterm"));
  if (f.incoterm === "EXW" && !f.pickup) p.push(t("p_exw"));
  if (f.addons?.includes("truck") && !f.delivery) p.push(t("p_delivery"));
  if (!q.ready) p.push(t("p_ready")); else if (q.ready < TODAY && f.stage <= 1) p.push(t("p_ready_past"));
  if (q.cargo === "haz" && !(q.un && q.imo && q.pg && q.msds)) p.push(t("p_haz"));
  if (q.cargo === "reefer" && (q.temp === "" || q.temp == null)) p.push(t("p_temp"));
  if ((f.service === "fcl" || f.service === "se") && !q.free) p.push(t("p_free"));
  if (q.pay === "credit" && !q.credit_days) p.push(t("p_credit"));
  return p;
}

function chargeable(service, r) {
  const cbm = (r.l * r.w * r.h * (r.pieces || 1)) / 1e6;
  if (service === "lcl") return { v: Math.max(cbm, (r.kg || 0) / 1000), u: "W/M", cbm };
  if (service === "air") return { v: Math.max(r.kg || 0, (r.l * r.w * r.h * (r.pieces || 1)) / 6000), u: "kg", cbm };
  if (service === "courier") { const v = Math.max(r.kg || 0, (r.l * r.w * r.h * (r.pieces || 1)) / 5000); return { v: Math.ceil(v * 2) / 2, u: "kg", cbm }; }
  return null;
}
function volumeSummary(f) {
  const rows = f.inq?.rows || [];
  if (f.service === "fcl" || f.service === "se") return rows.map(r => `${r.count}×${r.type}`).join(", ");
  if (["lcl", "air", "courier"].includes(f.service)) return rows.map(r => { const c = chargeable(f.service, r); return `${r.pieces} pcs · ${NF.format(r.kg)} kg` + (c ? ` · ${NF.format(c.v)} ${c.u === "W/M" ? "W/M" : "kg ch."}` : ""); }).join("; ");
  return "";
}

function costTotalUSD(f, kind = "est") { return (f.costs || []).reduce((s, c) => s + toUSD((kind === "act" ? (c.act ?? c.est) : c.est) || 0, c.cur), 0); }
function sellTotalUSD(f) { return (f.sell || []).reduce((s, l) => s + toUSD(l.amt || 0, l.cur), 0); }
function canSeeMoney() { return isAdmin() || ROLE === "accounts"; }
function canSeeContact() { return ROLE !== "pricing" && ROLE !== "pricing_review"; }

function visibleFiles() {
  let fs = DB.files;
  if (ROLE === "sales") fs = fs.filter(f => f.sales === me().id);
  return fs;
}
function visibleClients() { return ROLE === "sales" ? DB.clients.filter(c => c.owner === me().id) : DB.clients; }

// ---------- Total in words ----------
function enWords(n) {
  const a = ["", "one", "two", "three", "four", "five", "six", "seven", "eight", "nine", "ten", "eleven", "twelve", "thirteen", "fourteen", "fifteen", "sixteen", "seventeen", "eighteen", "nineteen"];
  const b = ["", "", "twenty", "thirty", "forty", "fifty", "sixty", "seventy", "eighty", "ninety"];
  const h = x => { let s = ""; if (x >= 100) { s += a[Math.floor(x / 100)] + " hundred"; x %= 100; if (x) s += " and "; } if (x >= 20) { s += b[Math.floor(x / 10)]; if (x % 10) s += "-" + a[x % 10]; } else if (x) s += a[x]; return s; };
  if (n === 0) return "zero";
  const parts = []; const sc = [[1e9, "billion"], [1e6, "million"], [1e3, "thousand"]];
  for (const [v, w] of sc) if (n >= v) { parts.push(h(Math.floor(n / v)) + " " + w); n %= v; }
  if (n) parts.push(h(n));
  return parts.join(" ");
}
function arWords(n) {
  const ones = ["", "واحد", "اثنان", "ثلاثة", "أربعة", "خمسة", "ستة", "سبعة", "ثمانية", "تسعة"];
  const teens = ["عشرة", "أحد عشر", "اثنا عشر", "ثلاثة عشر", "أربعة عشر", "خمسة عشر", "ستة عشر", "سبعة عشر", "ثمانية عشر", "تسعة عشر"];
  const tens = ["", "", "عشرون", "ثلاثون", "أربعون", "خمسون", "ستون", "سبعون", "ثمانون", "تسعون"];
  const hund = ["", "مائة", "مائتان", "ثلاثمائة", "أربعمائة", "خمسمائة", "ستمائة", "سبعمائة", "ثمانمائة", "تسعمائة"];
  const b100 = x => x < 10 ? ones[x] : x < 20 ? teens[x - 10] : (x % 10 ? ones[x % 10] + " و" + tens[Math.floor(x / 10)] : tens[Math.floor(x / 10)]);
  const b1000 = x => { const hh = Math.floor(x / 100), r = x % 100; return [hund[hh], r ? b100(r) : ""].filter(Boolean).join(" و"); };
  const scale = (x, one, two, few, many) => x === 1 ? one : x === 2 ? two : x <= 10 ? b1000(x) + " " + few : b1000(x) + " " + many;
  if (n === 0) return "صفر";
  const parts = [];
  const mil = Math.floor(n / 1e6), th = Math.floor((n % 1e6) / 1000), rest = n % 1000;
  if (mil) parts.push(scale(mil, "مليون", "مليونان", "ملايين", "مليون"));
  if (th) parts.push(scale(th, "ألف", "ألفان", "آلاف", "ألف"));
  if (rest) parts.push(b1000(rest));
  return parts.join(" و");
}
const CUR_WORDS = { USD: [["US dollars", "cents"], ["دولار أمريكي", "سنت"]], EUR: [["euros", "cents"], ["يورو", "سنت"]], EGP: [["Egyptian pounds", "piastres"], ["جنيه مصري", "قرش"]] };
function totalInWords(amt, cur, lang) {
  const whole = Math.floor(amt + 1e-9), frac = Math.round((amt - whole) * 100);
  const w = CUR_WORDS[cur] || [[cur, "cents"], [cur, "سنت"]];
  if (lang === "ar") return `فقط ${arWords(whole)} ${w[1][0]}` + (frac ? ` و${arWords(frac)} ${w[1][1]}` : "") + " لا غير";
  const s = enWords(whole); return s.charAt(0).toUpperCase() + s.slice(1) + " " + w[0][0] + (frac ? " and " + enWords(frac) + " " + w[0][1] : "") + " only";
}

// ---------- Access ----------
const NAV = [
  ["g_work", [["dashboard", "nav_dashboard", "home", "*"], ["files", "nav_files", "box", "*"], ["rfq", "nav_rfq", "plus", "sales"], ["pricing", "nav_pricing", "tag", "pricing,pricing_review"], ["ops", "nav_ops", "ship", "ops,customs"]]],
  ["g_people", [["clients", "nav_clients", "users", "sales,accounts"], ["leads", "nav_leads", "spark", "sales"], ["vendors", "nav_vendors", "truck", "pricing,ops,customs,accounts"]]],
  ["g_money", [["invoices", "nav_invoices", "file", "accounts"], ["collections", "nav_collections", "coins", "accounts"], ["performance", "nav_performance", "chart", "sales"]]],
  ["g_admin", [["users", "nav_users", "key", ""], ["config", "nav_config", "gear", ""], ["ai", "nav_ai", "spark", ""]]],
];
function canOpen(view) {
  if (isAdmin()) return true;
  for (const [, items] of NAV) for (const [v, , , roles] of items) if (v === view) return roles === "*" || roles.split(",").includes(ROLE);
  if (view === "file") return true;
  if (view === "client") return ["sales", "accounts"].includes(ROLE);
  if (view === "invoice") return ROLE === "accounts";
  return false;
}
function navCount(v) {
  if (v === "pricing") return DB.files.filter(f => f.status === "open" && ["pending", "review"].includes(f.pricing)).length + DB.inbound.filter(m => m.status !== "confirmed").length;
  if (v === "leads") return DB.leads.filter(l => !l.contacted && (isAdmin() || l.to === me().id)).length;
  if (v === "ops") return DB.files.filter(f => f.status === "open" && [3, 4, 5].includes(f.stage)).length;
  return 0;
}

// ---------- Router / layout ----------
let railOpen = false;
function route() { const h = (location.hash || "#dashboard").slice(1); const m = h.match(/^(file|client|invoice)-(.+)$/); return m ? { view: m[1], id: m[2] } : { view: h || "dashboard" }; }
function go(h) { if (location.hash === "#" + h) render(); else location.hash = h; }

function applyTheme() {
  const root = document.documentElement, ex = root.getAttribute("data-theme");
  const dark = ex ? ex === "dark" : matchMedia("(prefers-color-scheme: dark)").matches;
  root.setAttribute("data-bs-theme", dark ? "dark" : "light");
}
function applyLang() {
  document.documentElement.lang = LANG; document.documentElement.dir = LANG === "ar" ? "rtl" : "ltr";
  $("#bs-ltr").disabled = LANG === "ar"; $("#bs-rtl").disabled = LANG !== "ar";
  document.title = "Paradigm Cargo CRM";
}

function shell(content) {
  const r = route(), u = me(), unread = DB.notifications.filter(n => n.unread && (isAdmin() || n.to.includes(ROLE))).length;
  const nav = NAV.map(([g, items]) => {
    const vis = items.filter(([v]) => canOpen(v)); if (!vis.length) return "";
    return `<div class="group">${t(g)}</div>` + vis.map(([v, k, i]) => { const c = navCount(v); const act = r.view === v || (v === "files" && r.view === "file") || (v === "clients" && r.view === "client") || (v === "invoices" && r.view === "invoice"); return `<a href="#${v}" class="${act ? "active" : ""}">${ico(i)}<span>${t(k)}</span>${c ? `<span class="count">${c}</span>` : ""}</a>`; }).join("");
  }).join("");
  const roles = ["admin", "sales", "pricing", "pricing_review", "ops", "customs", "accounts"].map(x => `<option value="${x}" ${x === ROLE ? "selected" : ""}>${roleName(x)} — ${esc(tx(user(DB.roleUser[x]).name))}</option>`).join("");
  return `
  <div class="shell">
    <nav class="rail ${railOpen ? "open" : ""}" aria-label="${t("menu")}">
      <div class="brand"><span class="brand-mark">PC</span><span>Paradigm Cargo</span></div>
      ${nav}
      <div class="foot">${esc(u.email)}<br><a href="#login" style="padding:6px 0;border:0">${t("sign_out")}</a></div>
    </nav>
    ${railOpen ? '<div class="scrim" data-act="rail"></div>' : ""}
    <div style="min-width:0">
      <div class="demo-banner">${t("demo_banner")} <a href="#" data-act="reset" class="ms-2">${t("reset_demo")}</a></div>
      <header class="topbar">
        <button class="btn btn-light btn-sm menu-btn" data-act="rail" aria-label="${t("menu")}">${ico("menu")}</button>
        <form class="search" data-act="search" role="search"><div class="input-group input-group-sm"><span class="input-group-text bg-transparent">${ico("search")}</span><input id="q" class="form-control" type="search" placeholder="${t("search_ph")}" aria-label="${t("search_ph")}"></div></form>
        <div class="spacer"></div>
        <label class="small-label d-flex align-items-center gap-2" for="role-sel">${t("view_as")}<select id="role-sel" class="form-select form-select-sm" style="width:auto;max-width:210px">${roles}</select></label>
        <button class="btn btn-light btn-sm" data-act="lang">${ico("globe")} ${t("lang_switch")}</button>
        <button class="btn btn-light btn-sm bell" data-act="notifs" aria-label="${t("notifs")}">${ico("bell")}${unread ? `<span class="dot">${unread}</span>` : ""}</button>
        <span class="avatar">${esc(u.initials)}</span>
      </header>
      <main class="main" id="main">${content}</main>
    </div>
  </div>
  <div class="offcanvas offcanvas-end" tabindex="-1" id="notifs-panel" aria-labelledby="notifs-h">
    <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title" id="notifs-h">${t("notifs")}</h5><button class="btn btn-sm btn-light" data-act="readall">${t("mark_all_read")}</button></div>
    <div class="offcanvas-body p-0">${DB.notifications.filter(n => isAdmin() || n.to.includes(ROLE)).map((n, i) => `<a href="#${n.link}" class="notif text-reset text-decoration-none ${n.unread ? "unread" : ""}" data-notif="${DB.notifications.indexOf(n)}"><span class="tl-dot" style="${n.unread ? "" : "background:var(--line)"}"></span><div><div>${esc(tx(n.text))}</div><div class="small-label mt-1">${fdate(n.at)}</div></div></a>`).join("") || `<div class="empty">—</div>`}</div>
  </div>
  <div class="modal fade" id="modal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-scrollable"><div class="modal-content" id="modal-content"></div></div></div>`;
}

function pageHead(title, sub, actions = "") { return `<div class="page-head"><div><h1>${title}</h1>${sub ? `<div class="sub">${sub}</div>` : ""}</div><div class="d-flex gap-2 flex-wrap">${actions}</div></div>`; }
function noAccess() { return `<div class="panel"><div class="empty">${t("no_access")}</div></div>`; }

const VIEWS = {};
function render() {
  applyLang(); applyTheme();
  const app = $("#app"), r = route();
  // Close any open Bootstrap overlays before redraw
  $$(".modal-backdrop, .offcanvas-backdrop").forEach(e => e.remove()); document.body.classList.remove("modal-open"); document.body.style.removeProperty("overflow"); document.body.style.removeProperty("padding-right");
  if (r.view === "login") { app.innerHTML = VIEWS.login(); bindLogin(); return; }
  const v = VIEWS[r.view];
  const html = !v ? VIEWS.dashboard() : canOpen(r.view) ? v(r.id) : noAccess();
  app.innerHTML = shell(html);
  window.scrollTo(0, 0);
  if (v && v.after) v.after(r.id);
}

function openModal(html, size = "") {
  const dlg = $("#modal .modal-dialog"); dlg.className = "modal-dialog modal-dialog-scrollable " + size;
  $("#modal-content").innerHTML = html;
  const m = bootstrap.Modal.getOrCreateInstance($("#modal")); m.show(); return m;
}
function closeModal() { const m = bootstrap.Modal.getInstance($("#modal")); if (m) m.hide(); }

// Global events
document.addEventListener("click", e => {
  const a = e.target.closest("[data-act]"); if (!a) { const n = e.target.closest("[data-notif]"); if (n) { DB.notifications[+n.dataset.notif].unread = false; saveState(); } return; }
  const act = a.dataset.act;
  if (act === "rail") { railOpen = !railOpen; render(); }
  else if (act === "lang") { LANG = LANG === "ar" ? "en" : "ar"; saveState(); render(); }
  else if (act === "reset") { e.preventDefault(); resetDemo(); }
  else if (act === "notifs") { bootstrap.Offcanvas.getOrCreateInstance($("#notifs-panel")).show(); }
  else if (act === "readall") { DB.notifications.forEach(n => { if (isAdmin() || n.to.includes(ROLE)) n.unread = false; }); saveState(); render(); }
  else if (HANDLERS[act]) { e.preventDefault(); HANDLERS[act](a, e); }
});
document.addEventListener("change", e => {
  if (e.target.id === "role-sel") { ROLE = e.target.value; railOpen = false; saveState(); const r = route(); if (!canOpen(r.view === "file" ? "file" : r.view)) go("dashboard"); else render(); }
  else { const c = e.target.closest("[data-change]"); if (c && HANDLERS[c.dataset.change]) HANDLERS[c.dataset.change](c, e); }
});
document.addEventListener("submit", e => {
  e.preventDefault();
  const f = e.target;
  if (f.dataset.act === "search") { const q = $("#q").value.trim(); if (q) { SEARCH_Q = q; go("files"); } }
  else if (f.dataset.submit && HANDLERS[f.dataset.submit]) HANDLERS[f.dataset.submit](f, e);
});
const HANDLERS = {};
let SEARCH_Q = "";
window.addEventListener("hashchange", () => { railOpen = false; render(); });
