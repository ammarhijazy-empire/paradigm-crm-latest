// ---------- Quote request form ----------
function blankRFQ() { return { client: "", dir: "import", service: "", addons: [], origin: "", dest: "", incoterm: "", pickup: "", delivery: "", stage: 1, inq: { rows: [], cargo: "general", commodity: "", ready: "", price_by: "", free: "", freight: "collect", pay: "cia", credit_days: "" }, ai: [], aiChecked: false, ask: [], transcript: "" }; }
let RFQ = blankRFQ();
const SEA = ["fcl", "lcl", "bulk", "roro", "se"];
function blankRow(s) { return s === "fcl" ? { type: "40'HC", count: 1 } : s === "se" ? { type: "Flat Rack", count: 1 } : s === "land" ? { truck: "FTL", count: 1, kg: "" } : s === "bulk" ? { tonnes: "" } : { pieces: "", l: "", w: "", h: "", kg: "" }; }

VIEWS.rfq = () => {
  const R = RFQ, q = R.inq, probs = inquiryProblems(R), aiCls = k => R.ai.includes(k) && !R.aiChecked ? "ai-field" : "";
  const myClients = visibleClients();
  const svcBtn = (s, type) => `<input type="${type}" class="btn-check" name="${type === "radio" ? "svc-main" : "svc-add"}" id="svc-${s}" value="${s}" ${type === "radio" ? (R.service === s ? "checked" : "") : (R.addons.includes(s) ? "checked" : "")} data-change="${type === "radio" ? "rfqsvc" : "rfqaddon"}"><label class="btn btn-sm btn-outline-primary ${aiCls(type === "radio" ? "service" : "addons")}" for="svc-${s}">${t(SVC_KEY[s])}</label>`;
  const inp = (path, val, opts = {}) => `<input id="rf-${path.replace(/\./g, "-")}" type="${opts.type || "text"}" class="form-control form-control-sm ${opts.cls || ""} ${aiCls(path)}" value="${esc(val ?? "")}" data-change="rfqf" data-k="${path}" ${opts.min != null ? `min="${opts.min}"` : ""} ${opts.ph ? `placeholder="${esc(opts.ph)}"` : ""}>`;
  const lbl = (path, key) => `<label class="form-label" for="rf-${path.replace(/\./g, "-")}">${t(key)}</label>`;
  const portSel = (k, val) => `<select id="rf-${k}" class="form-select form-select-sm ${aiCls(k)}" data-change="rfqf" data-k="${k}"><option value="">—</option>${Object.keys(DB.ports).map(c => `<option value="${c}" ${val === c ? "selected" : ""}>${esc(port(c))}</option>`).join("")}</select>`;
  const rows = R.service ? q.rows.map((r, i) => {
    const f = (k, key, type = "number") => `<div><label class="form-label" for="rf-r${i}-${k}">${t(key)}</label><input id="rf-r${i}-${k}" type="${type}" min="0" class="form-control form-control-sm num ${aiCls("rows")}" value="${esc(r[k] ?? "")}" data-change="rfqrow" data-i="${i}" data-k="${k}"></div>`;
    const sel = (k, key, opts) => `<div><label class="form-label" for="rf-r${i}-${k}">${t(key)}</label><select id="rf-r${i}-${k}" dir="ltr" class="form-select form-select-sm ${aiCls("rows")}" data-change="rfqrow" data-i="${i}" data-k="${k}">${opts.map(o => `<option ${r[k] === o ? "selected" : ""}>${o}</option>`).join("")}</select></div>`;
    let body = "";
    if (R.service === "fcl") body = sel("type", "cont_type", CONT_TYPES) + f("count", "count");
    else if (R.service === "se") body = sel("type", "cont_type", ["Flat Rack", "Open Top"]) + f("count", "count") + f("l", "len") + f("w", "wid") + f("h", "hei");
    else if (["lcl", "air", "courier"].includes(R.service)) { const c = r.l && r.w && r.h ? chargeable(R.service, { ...r, l: +r.l, w: +r.w, h: +r.h, kg: +r.kg, pieces: +r.pieces || 1 }) : null; body = f("pieces", "pieces") + f("l", "len") + f("w", "wid") + f("h", "hei") + f("kg", "weight") + `<div><div class="form-label">${t("chargeable")}</div><div class="fw-semibold num ltr pt-1">${c ? NF.format(c.v) + " " + c.u + (R.service === "lcl" ? ` · ${NF.format(c.cbm)} ${t("cbm")}` : "") : "—"}</div></div>`; }
    else if (R.service === "land") body = sel("truck", "truck_type", ["FTL", "LTL"]) + f("count", "count") + f("kg", "weight");
    else if (R.service === "bulk") body = f("tonnes", "tonnes");
    else if (R.service === "roro") body = f("count", "count") + f("l", "len") + f("w", "wid") + f("h", "hei") + f("kg", "weight");
    return `<div class="row-line">${body}<div><button class="btn btn-sm btn-light w-100" data-act="rfqdel" data-i="${i}">${t("remove")}</button></div></div>`;
  }).join("") + `<button class="btn btn-sm btn-light" data-act="rfqadd">${t("add_row")}</button>` : `<div class="small-label">${t("p_service")}</div>`;
  const incos = SEA.includes(R.service) ? INCOTERMS_SEA : INCOTERMS_OTHER;
  const cargoTypes = ["general", "haz", "reefer", "fragile", "liquid", "oog"];
  const cargoExtra = q.cargo === "haz" ? `<div class="grid g-4 mt-2"><div>${lbl("inq.un", "un_no")}${inp("inq.un", q.un)}</div><div>${lbl("inq.imo", "imo_class")}${inp("inq.imo", q.imo)}</div><div>${lbl("inq.pg", "pg")}<select id="rf-inq-pg" class="form-select form-select-sm" data-change="rfqf" data-k="inq.pg"><option value="">—</option>${["I", "II", "III"].map(x => `<option ${q.pg === x ? "selected" : ""}>${x}</option>`).join("")}</select></div><div><label class="form-label" for="rf-msds">MSDS</label><input id="rf-msds" type="file" class="form-control form-control-sm" data-change="rfqmsds"></div></div>`
    : q.cargo === "reefer" ? `<div class="mt-2" style="max-width:200px">${lbl("inq.temp", "temp")}${inp("inq.temp", q.temp, { type: "number" })}</div>` : "";
  const aiBanner = R.ai.length ? `<div class="ai-box mb-3"><h4>${t("wa_filled", { n: R.ai.length })}</h4>
      ${R.transcript ? `<div class="small mb-2"><strong>${t("wa_transcript")}:</strong> ${esc(R.transcript)}</div>` : ""}
      ${R.ask.length ? `<div class="small"><strong>${t("wa_ask")}:</strong><ul class="mb-2">${R.ask.map(a => `<li>${esc(t(a))}</li>`).join("")}</ul></div>` : ""}
      <div class="form-check"><input class="form-check-input" type="checkbox" id="ai-ok" ${R.aiChecked ? "checked" : ""} data-change="aiok"><label class="form-check-label" for="ai-ok">${t("wa_confirm")}</label></div></div>` : "";
  const canSend = !probs.length && (!R.ai.length || R.aiChecked);
  return pageHead(t("rfq_title"), t("rfq_sub"), DB.settings.ai.f1 ? `<button class="btn btn-light btn-sm" data-act="wa">${ico("wa")} ${t("fill_wa")}</button>` : "") + `<div class="grid g-main">
    <div class="panel">${aiBanner ? `<div class="p-3 pb-0">${aiBanner}</div>` : ""}
      <div class="form-section"><h3><span class="idx">01</span>${t("client")}</h3><div class="grid g-2">
        <div><label class="form-label" for="rf-client">${t("client")}</label><select id="rf-client" class="form-select form-select-sm" data-change="rfqf" data-k="client"><option value="">—</option>${myClients.map(c => `<option value="${c.id}" ${R.client === c.id ? "selected" : ""}>${esc(tx(c.company))}</option>`).join("")}</select></div>
        <div><span class="form-label d-block">${t("direction")}</span><div class="btn-group btn-group-sm" role="group">${["import", "export"].map(d => `<input type="radio" class="btn-check" name="rf-dir" id="rf-dir-${d}" value="${d}" ${R.dir === d ? "checked" : ""} data-change="rfqdir"><label class="btn btn-outline-primary" for="rf-dir-${d}">${t(d)}</label>`).join("")}</div></div></div></div>
      <div class="form-section"><h3><span class="idx">02</span>${t("services")}</h3>
        <div class="small-label mb-2">${t("main_service")}</div><div class="svc-grid mb-3">${MAIN_SERVICES.map(s => svcBtn(s, "radio")).join("")}</div>
        <div class="small-label mb-2">${t("addons")}</div><div class="svc-grid">${ADDONS.map(s => svcBtn(s, "checkbox")).join("")}</div></div>
      <div class="form-section"><h3><span class="idx">03</span>${t("volume")}</h3>${rows}</div>
      <div class="form-section"><h3><span class="idx">04</span>${t("places")}</h3><div class="grid g-3">
        <div>${lbl("origin", "origin")}${portSel("origin", R.origin)}</div><div>${lbl("dest", "destination")}${portSel("dest", R.dest)}</div>
        <div>${lbl("incoterm", "incoterm")}<select id="rf-incoterm" class="form-select form-select-sm ${aiCls("incoterm")}" data-change="rfqf" data-k="incoterm"><option value="">—</option>${incos.map(i => `<option ${R.incoterm === i ? "selected" : ""}>${i}</option>`).join("")}</select></div>
        ${R.incoterm === "EXW" ? `<div class="g-span">${lbl("pickup", "pickup_addr")}${inp("pickup", R.pickup)}</div>` : ""}
        ${R.addons.includes("truck") || R.service === "courier" ? `<div>${lbl("delivery", "delivery_addr")}${portSel("delivery", R.delivery)}</div>` : ""}</div></div>
      <div class="form-section"><h3><span class="idx">05</span>${t("cargo")}</h3><div class="grid g-2">
        <div>${lbl("inq.commodity", "commodity")}${inp("inq.commodity", q.commodity)}</div>
        <div><span class="form-label d-block">${t("cargo_type")}</span><select id="rf-cargo" class="form-select form-select-sm" data-change="rfqf" data-k="inq.cargo" aria-label="${t("cargo_type")}">${cargoTypes.map(c => `<option value="${c}" ${q.cargo === c ? "selected" : ""}>${t("cg_" + c)}</option>`).join("")}</select></div></div>${cargoExtra}</div>
      <div class="form-section"><h3><span class="idx">06</span>${t("other_fields")}</h3><div class="grid g-3">
        <div>${lbl("inq.ready", "ready_date")}${inp("inq.ready", q.ready, { type: "date" })}</div><div>${lbl("inq.price_by", "price_by")}${inp("inq.price_by", q.price_by, { type: "date" })}</div>
        ${["fcl", "se"].includes(R.service) ? `<div>${lbl("inq.free", "free_time")}${inp("inq.free", q.free, { type: "number", min: 0 })}</div>` : ""}
        <div><label class="form-label" for="rf-freight">${t("freight_terms")}</label><select id="rf-freight" class="form-select form-select-sm" data-change="rfqf" data-k="inq.freight">${["prepaid", "collect"].map(x => `<option value="${x}" ${q.freight === x ? "selected" : ""}>${t(x)}</option>`).join("")}</select></div>
        <div><label class="form-label" for="rf-pay">${t("pay_method")}</label><select id="rf-pay" class="form-select form-select-sm" data-change="rfqf" data-k="inq.pay">${["cia", "cad", "arrival", "credit"].map(x => `<option value="${x}" ${q.pay === x ? "selected" : ""}>${t("pm_" + x)}</option>`).join("")}</select></div>
        ${q.pay === "credit" ? `<div>${lbl("inq.credit_days", "credit_days")}${inp("inq.credit_days", q.credit_days, { type: "number", min: 1 })}</div>` : ""}
        <div><label class="form-label" for="rf-att">${t("attachments")}</label><input id="rf-att" type="file" multiple class="form-control form-control-sm"></div></div></div>
    </div>
    <div><div class="panel problems"><div class="panel-h"><h2>${t("rfq_check")}</h2><span class="pill ${probs.length ? "bad" : "ok"}">${probs.length}</span></div><div class="panel-b">
      ${probs.length ? `<ul class="small ps-3 mb-0">${probs.map(p => `<li class="mb-1">${esc(p)}</li>`).join("")}</ul>` : `<div class="gate-box ok">${t("rfq_ok")}</div>`}
      ${R.ai.length && !R.aiChecked ? `<div class="gate-box bad mt-2">${t("wa_confirm")}</div>` : ""}
      <div class="d-grid gap-2 mt-3"><button class="btn btn-primary" data-act="rfqsend" ${canSend ? "" : "disabled"}>${t("send_pricing")}</button><button class="btn btn-light" data-act="rfqclear">${t("clear_form")}</button></div>
    </div></div></div></div>`;
};
function setPath(o, path, v) { const ks = path.split("."); let x = o; ks.slice(0, -1).forEach(k => x = x[k]); x[ks.at(-1)] = v; }
HANDLERS.rfqf = el => { setPath(RFQ, el.dataset.k, el.value); render(); };
HANDLERS.rfqdir = el => { RFQ.dir = el.value; render(); };
HANDLERS.rfqsvc = el => { RFQ.service = el.value; RFQ.inq.rows = [blankRow(el.value)]; if (!SEA.includes(el.value) && !INCOTERMS_OTHER.includes(RFQ.incoterm)) RFQ.incoterm = ""; render(); };
HANDLERS.rfqaddon = el => { RFQ.addons = el.checked ? [...RFQ.addons, el.value] : RFQ.addons.filter(a => a !== el.value); render(); };
HANDLERS.rfqrow = el => { const r = RFQ.inq.rows[+el.dataset.i], k = el.dataset.k; r[k] = ["type", "truck"].includes(k) ? el.value : (el.value === "" ? "" : +el.value); render(); };
HANDLERS.rfqadd = () => { RFQ.inq.rows.push(blankRow(RFQ.service)); render(); };
HANDLERS.rfqdel = a => { RFQ.inq.rows.splice(+a.dataset.i, 1); render(); };
HANDLERS.rfqmsds = el => { RFQ.inq.msds = el.files[0]?.name || ""; render(); };
HANDLERS.aiok = el => { RFQ.aiChecked = el.checked; render(); };
HANDLERS.rfqclear = () => { RFQ = blankRFQ(); render(); };
HANDLERS.rfqsend = () => {
  if (inquiryProblems(RFQ).length) return;
  const u = me(), n = DB.files.filter(f => f.ref.startsWith(u.initials + "-2610-")).length + 1, num = 48 + DB.files.filter(f => +f.id.slice(6) >= 48).length;
  const f = { id: "PC-26-" + String(num).padStart(3, "0"), ref: `${u.initials}-2610-${n}`, client: RFQ.client, sales: u.id, ops: "u6", stage: 1, status: "open", service: RFQ.service, addons: RFQ.addons, dir: RFQ.dir, origin: RFQ.origin, dest: RFQ.dest, incoterm: RFQ.incoterm, pickup: RFQ.pickup, delivery: RFQ.delivery, since: TODAY,
    inq: { ...RFQ.inq, commodity: L(RFQ.inq.commodity, RFQ.inq.commodity), free: +RFQ.inq.free || 0, credit_days: +RFQ.inq.credit_days || 0 }, checks: { c1_first: true }, pricing: "pending", costs: [], sell: [], rounds: [], vq: [], docs: [], dc: [],
    history: [[TODAY, u.id, L("Quote request sent to pricing" + (RFQ.ai.length ? " (filled from WhatsApp with AI, checked by sales)" : ""), "طلب التسعير اتبعت للتسعير" + (RFQ.ai.length ? " (اتملى من الواتساب بالذكاء الاصطناعي واتراجع من المبيعات)" : ""))]], notes: [] };
  DB.files.unshift(f); DB.notifications.unshift({ at: TODAY + " 12:00", text: L(`New quote request ${f.ref} to price`, `طلب تسعير جديد ${f.ref} مستني تسعير`), to: ["pricing", "admin"], link: "file-" + f.id, unread: true });
  saveState(); toast(t("rfq_sent", { ref: f.ref })); RFQ = blankRFQ(); FILE_TAB = "overview"; go("file-" + f.id);
};
const WA_SAMPLE = `[28/10/2026 09:14] Eng. Sherif: صباح الخير يا عبدالله
[28/10/2026 09:15] Eng. Sherif: عايزين سعر ٢ حاوية ٤٠ هاي كيوب من نينغبو للإسكندرية FOB
[28/10/2026 09:15] Eng. Sherif: حبيبات بولي بروبلين زي المرة اللي فاتت
[28/10/2026 09:16] Eng. Sherif: البضاعة جاهزة يوم ١٠ نوفمبر إن شاء الله
[28/10/2026 09:17] Eng. Sherif: ‎<voice note 0:24>
[28/10/2026 09:18] Eng. Sherif: ومعاهم التخليص والنقل للمصنع في العاشر`;
HANDLERS.wa = () => {
  openModal(`<div class="modal-header"><h5 class="modal-title">${ico("wa")} ${t("wa_title")}</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="${t("close")}"></button></div>
    <div class="modal-body"><p class="small muted">${t("wa_hint")}</p>
      <label class="form-label" for="wa-text">${t("wa_paste")}</label><textarea id="wa-text" class="form-control mb-2" rows="7" dir="auto"></textarea>
      <button class="btn btn-link btn-sm p-0 mb-3" data-act="wasample">${t("wa_sample")}</button>
      <label class="form-label d-block" for="wa-files">${t("wa_files")}</label><input id="wa-files" type="file" multiple class="form-control" accept=".txt,.zip,.opus,.ogg,.m4a,.mp3">
      <div class="ai-box mt-3 small">${t("ai_suggests")}</div></div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">${t("cancel")}</button><button class="btn btn-primary" data-act="warun">${ico("spark")} ${t("wa_run")}</button></div>`);
};
HANDLERS.wasample = () => { $("#wa-text").value = WA_SAMPLE; };
HANDLERS.warun = a => {
  const text = $("#wa-text").value.trim(), files = $("#wa-files").files.length;
  if (!text && !files) { $("#wa-text").classList.add("is-invalid"); return; }
  a.disabled = true; a.textContent = t("wa_running");
  setTimeout(() => {
    const c1 = DB.clients.find(c => c.id === "c1");
    RFQ = { ...blankRFQ(), client: visibleClients().includes(c1) ? "c1" : "", service: "fcl", addons: ["clear", "truck"], origin: "CNNGB", dest: "EGALY", incoterm: "FOB", delivery: "10TH",
      inq: { rows: [{ type: "40'HC", count: 2 }], cargo: "general", commodity: "حبيبات بولي بروبلين", ready: "2026-11-10", price_by: "", free: "", freight: "collect", pay: "cia", credit_days: "" },
      ai: ["service", "addons", "rows", "origin", "dest", "incoterm", "delivery", "inq.commodity", "inq.ready"], aiChecked: false, ask: ["free_time", "pay_method", "price_by"],
      transcript: /voice|opus|ogg|m4a|mp3/i.test(text) || files ? "عايز السعر النهارده لو ينفع، والفاتورة باسم الشركة زي كل مرة، والنقل للمصنع في المنطقة الصناعية التالتة." : "" };
    DB.aiRuns.unshift({ at: TODAY + " " + new Date().toTimeString().slice(0, 5), f: "ai_f1", user: me().id, file: "", tokens: 4120, cost: 0.01, status: "rs_done" }); DB.settings.ai.spent += 0.01; saveState();
    closeModal(); setTimeout(render, 200);
  }, 1200);
};

// ---------- Pricing desk ----------
function pricingDesk(embedded) {
  const open = DB.files.filter(f => f.status === "open");
  const toPrice = open.filter(f => f.pricing === "pending"), review = open.filter(f => f.pricing === "review");
  const reqTable = list => list.length ? `<div class="tbl-wrap"><table class="table table-hover align-middle"><thead><tr><th>${t("reference")}</th><th>${t("service")}</th><th>${t("route")}</th><th>${t("volume")}</th><th>${t("need_by")}</th><th>${t("replies")}</th></tr></thead><tbody>${list.map(f => `<tr><td><a class="row-link mono" href="#file-${f.id}" data-act="gopricing" data-file="${f.id}">${f.ref}</a><div class="small-label mono">${f.id}</div></td><td>${esc(t(SVC_KEY[f.service]))}</td><td class="small">${esc(port(f.origin))} → ${esc(port(f.dest))}</td><td class="small ltr">${esc(volumeSummary(f))}</td><td class="num">${f.inq.price_by ? (f.inq.price_by <= TODAY ? pill(fdate(f.inq.price_by), "warn") : fdate(f.inq.price_by)) : "—"}</td><td class="num">${(f.vq || []).length}</td></tr>`).join("")}</tbody></table></div>` : `<div class="empty">${t("none")}</div>`;
  const inbox = `<div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>${t("from")}</th><th>${t("subject")}</th><th>${t("matched_to")}</th><th>${t("status")}</th><th></th></tr></thead><tbody>${DB.inbound.map(m => `<tr>
      <td>${esc(vendor(m.vendor)?.name || m.from)}<div class="small-label ltr">${fdate(m.at)}</div></td><td class="small mono">${esc(m.subject)}</td>
      <td>${m.matched ? `<a class="mono" href="#file-${m.matched}">${file(m.matched).ref}</a>` : `<select class="form-select form-select-sm" data-change="matchmail" data-m="${m.id}" aria-label="${t("matched_to")}"><option value="">${t("needs_matching")}</option>${open.filter(f => f.stage <= 2).map(f => `<option value="${f.id}">${f.ref}</option>`).join("")}</select>`}</td>
      <td>${m.status === "confirmed" ? pill(t("confirm_quote"), "ok") : m.status === "draft" ? pill(t("ai_read"), "info ai") : pill(t("needs_matching"), "warn")}</td>
      <td>${m.matched ? `<a class="btn btn-sm btn-light" href="#file-${m.matched}" data-act="gopricing" data-file="${m.matched}">${t("view")}</a>` : ""}</td></tr>`).join("")}</tbody></table></div>`;
  const showReq = isAdmin() || ROLE === "pricing", showRev = isAdmin() || ROLE === "pricing_review";
  return `<div class="stack">${showReq ? panel(t("pq_requests"), reqTable(toPrice), pill(toPrice.length), true) : ""}
    ${showReq && DB.settings.ai.f2 ? panel(t("pq_inbox") + " " + pill(t("ai_suggests"), "info"), inbox, `<span class="small-label mono">rates@paradigmcargo.com</span>`, true) : ""}
    ${showRev ? panel(t("pq_review"), reqTable(review), pill(review.length), true) : ""}</div>`;
}
VIEWS.pricing = () => pageHead(t("pricing_title"), t("pricing_sub")) + pricingDesk();
HANDLERS.gopricing = a => { FILE_TAB = "pricing"; go("file-" + a.dataset.file); };
HANDLERS.matchmail = el => {
  const m = DB.inbound.find(x => x.id === el.dataset.m); if (!el.value) return;
  const f = file(el.value), qid = "q" + (Object.keys(DB.vendorQuotes).length + 1), n = (f.vq || []).length + 1;
  DB.vendorQuotes[qid] = { file: f.id, ref: `${f.ref}-Q${n}`, vendor: m.vendor, carrier: "Arkas", freight: 1580, sur: 210, local: 300, cur: "USD", tt: 5, free: 14, valid: "2026-11-20", ai: true, confirmed: false, why: L("Includes EXW pickup; check the trucking line", "شامل الاستلام EXW؛ راجع بند النقل") };
  f.vq = [...(f.vq || []), qid]; m.matched = f.id; m.quote = qid; m.status = "draft"; saveState(); toast(t("draft_ai")); render();
};

// ---------- Clients ----------
let CLIENT_STATUS = "";
VIEWS.clients = () => {
  let cs = visibleClients(); if (CLIENT_STATUS) cs = cs.filter(c => c.status === CLIENT_STATUS);
  const sec = c => LANG === "ar" ? DB.sectorsAr[c.sector] : DB.sectors[c.sector];
  return pageHead(t("clients_title"), ROLE === "sales" ? t("only_mine") : "", ROLE === "sales" || isAdmin() ? `<button class="btn btn-primary btn-sm" data-act="newclient">${ico("plus")} ${t("new_client")}</button>` : "") +
    `<section class="panel"><div class="panel-h"><div class="filters"><select id="cl-st" class="form-select form-select-sm" data-change="clst" aria-label="${t("status")}"><option value="">${t("status")}: ${t("all")}</option>${["lead", "contacted", "active", "repeat", "lost"].map(s => `<option value="${s}" ${CLIENT_STATUS === s ? "selected" : ""}>${t("cs_" + s)}</option>`).join("")}</select></div><span class="small-label">${cs.length}</span></div>
    <div class="tbl-wrap"><table class="table table-hover align-middle"><thead><tr><th>${t("company")}</th><th>${t("contact_person")}</th><th>${t("sector")}</th><th>${t("status")}</th><th>${t("owner")}</th><th>${t("next_follow")}</th></tr></thead><tbody>
    ${cs.map(c => `<tr><td><a class="row-link" href="#client-${c.id}">${esc(tx(c.company))}</a><div class="small-label">${esc(tx(c.products))}</div></td><td>${esc(tx(c.contact))}<div class="small-label ltr">${esc(c.phone)}</div></td><td class="small">${esc(sec(c))}</td><td>${pill(t("cs_" + c.status), statusKind(c.status))}</td><td>${avatar(c.owner)}</td><td class="num">${c.next_follow ? (c.next_follow <= TODAY ? pill(fdate(c.next_follow), "warn") : fdate(c.next_follow)) : "—"}</td></tr>`).join("")}</tbody></table></div></section>`;
};
HANDLERS.clst = el => { CLIENT_STATUS = el.value; render(); };
HANDLERS.newclient = () => {
  openModal(`<form data-submit="saveclient" novalidate><div class="modal-header"><h5 class="modal-title">${t("new_client")}</h5></div><div class="modal-body"><div class="grid g-2">
    <div><label class="form-label" for="nc-co">${t("company")} *</label><input id="nc-co" class="form-control" required></div>
    <div><label class="form-label" for="nc-ct">${t("contact_person")} *</label><input id="nc-ct" class="form-control" required></div>
    <div><label class="form-label" for="nc-ph">${t("phone")} *</label><input id="nc-ph" class="form-control ltr" required placeholder="+20 1xx xxx xxxx" data-change="dupcheck"></div>
    <div><label class="form-label" for="nc-em">${t("email")} *</label><input id="nc-em" type="email" class="form-control ltr" required></div>
    <div><label class="form-label" for="nc-sec">${t("sector")} *</label><select id="nc-sec" class="form-select" data-change="secother">${DB.sectors.map((s, i) => `<option value="${i}">${esc(LANG === "ar" ? DB.sectorsAr[i] : s)}</option>`).join("")}</select></div>
    <div id="nc-oth-w" hidden><label class="form-label" for="nc-oth">${t("sector_other")} *</label><input id="nc-oth" class="form-control"></div></div>
    <div id="nc-dup" class="gate-box bad mt-3" hidden></div><div id="nc-err" class="text-danger small mt-2" hidden></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">${t("cancel")}</button><button class="btn btn-primary">${t("save")}</button></div></form>`, "modal-lg");
};
const normPhone = p => p.replace(/\D/g, "").replace(/^0020|^20|^0/, "");
HANDLERS.dupcheck = el => { const n = normPhone(el.value), d = DB.clients.find(c => normPhone(c.phone) === n && n); const box = $("#nc-dup"); box.hidden = !d; if (d) box.textContent = t("dup_warning", { name: tx(d.company) }); };
HANDLERS.secother = el => { $("#nc-oth-w").hidden = +el.value !== DB.sectors.length - 1; };
HANDLERS.saveclient = () => {
  const v = id => $("#" + id).value.trim(), req = ["nc-co", "nc-ct", "nc-ph", "nc-em"]; let bad = false;
  req.forEach(id => { const e = $("#" + id); e.classList.toggle("is-invalid", !e.value.trim()); if (!e.value.trim()) bad = true; });
  if (+v("nc-sec") === DB.sectors.length - 1 && !v("nc-oth")) { $("#nc-oth").classList.add("is-invalid"); bad = true; }
  if (bad) return;
  const c = { id: "c" + (DB.clients.length + 1), company: L(v("nc-co"), v("nc-co")), contact: L(v("nc-ct"), v("nc-ct")), phone: v("nc-ph"), email: v("nc-em"), sector: +v("nc-sec"), status: "lead", owner: ROLE === "sales" ? me().id : "u1", next_follow: TODAY, next_order: "", source: "Manual", fwd: "—", credit_days: 0, credit_limit: 0, tax: "", products: L("", "") };
  DB.clients.push(c); closeModal(); saveState(); toast(t("saved")); setTimeout(() => go("client-" + c.id), 200);
};
VIEWS.client = id => {
  const c = client(id); if (!c || (ROLE === "sales" && c.owner !== me().id)) return noAccess();
  const acts = DB.activities.filter(a => a.client === id), fs = DB.files.filter(f => f.client === id);
  const sec = LANG === "ar" ? DB.sectorsAr[c.sector] : DB.sectors[c.sector];
  return `<div class="mb-2"><a href="#clients" class="small">← ${t("clients_title")}</a></div>` + pageHead(esc(tx(c.company)), `${esc(sec)} · ${esc(tx(c.products))}`, pill(t("cs_" + c.status), statusKind(c.status))) +
    `<div class="grid g-main"><div class="stack">${panel(t("files_for_client"), filesTable(fs), ROLE === "sales" || isAdmin() ? `<a class="btn btn-sm btn-primary" href="#rfq" data-act="rfqfor" data-c="${c.id}">${ico("plus")} ${t("new_file")}</a>` : "", true)}
      ${panel(t("activities"), acts.map(a => `<div class="tl-item">${avatar(a.by)}<div style="min-width:0"><div>${pill(t(a.type))} ${a.rating ? `<span class="small-label">${"★".repeat(a.rating)}</span>` : ""}</div><div class="mt-1">${esc(tx(a.text))}</div><div class="small-label">${fdate(a.at)}</div></div></div>`).join("") || `<div class="empty">—</div>`,
        ROLE === "sales" || isAdmin() ? `<button class="btn btn-sm btn-light" data-act="logact" data-c="${c.id}">${t("log_activity")}</button>` : "")}</div>
      ${panel(t("contact"), `<dl class="dl"><dt>${t("contact_person")}</dt><dd>${esc(tx(c.contact))}</dd><dt>${t("phone")}</dt><dd class="ltr">${esc(c.phone)}</dd><dt>${t("email")}</dt><dd class="ltr">${esc(c.email)}</dd>
        <dt>${t("owner")}</dt><dd>${avatar(c.owner)} ${esc(uname(c.owner))}</dd><dt>${t("source")}</dt><dd>${esc(c.source)}</dd><dt>${t("current_fwd")}</dt><dd>${esc(c.fwd)}</dd>
        <dt>${t("next_follow")}</dt><dd class="num">${fdate(c.next_follow)}</dd><dt>${t("next_order")}</dt><dd class="num">${fdate(c.next_order)}</dd>
        <dt>${t("credit")}</dt><dd>${c.credit_days ? `${money(c.credit_limit)} · ${c.credit_days} ${t("days")}` : "—"}</dd><dt>${t("tax_id")}</dt><dd class="mono">${esc(c.tax || "—")}</dd></dl>`)}</div>`;
};
HANDLERS.rfqfor = a => { RFQ = blankRFQ(); RFQ.client = a.dataset.c; go("rfq"); };
HANDLERS.logact = a => {
  openModal(`<form data-submit="saveact" data-c="${a.dataset.c}"><div class="modal-header"><h5 class="modal-title">${t("log_activity")}</h5></div><div class="modal-body">
    <div class="mb-2"><label class="form-label" for="la-type">${t("vtype")}</label><select id="la-type" class="form-select">${["act_call", "act_wa", "act_meeting", "act_email", "act_note"].map(k => `<option value="${k}">${t(k)}</option>`).join("")}</select></div>
    <div class="mb-2"><label class="form-label" for="la-text">${t("notes")}</label><textarea id="la-text" class="form-control" rows="3"></textarea></div>
    <div><label class="form-label" for="la-rate">${t("rating")}</label><select id="la-rate" class="form-select">${[0, 1, 2, 3, 4, 5].map(n => `<option value="${n}">${n ? "★".repeat(n) : "—"}</option>`).join("")}</select></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">${t("cancel")}</button><button class="btn btn-primary">${t("save")}</button></div></form>`);
};
HANDLERS.saveact = form => { const v = $("#la-text").value.trim(); if (!v) return $("#la-text").classList.add("is-invalid"); DB.activities.unshift({ client: form.dataset.c, type: $("#la-type").value, by: me().id, at: TODAY, text: L(v, v), rating: +$("#la-rate").value }); closeModal(); saveState(); setTimeout(render, 200); };

// ---------- Leads ----------
VIEWS.leads = () => {
  const ls = isAdmin() ? DB.leads : DB.leads.filter(l => l.to === me().id);
  const reps = DB.users.filter(u => u.gets_leads && u.active);
  return pageHead(t("leads_title"), t("leads_sub"), `<span class="small-label align-self-center">${t("next_in_rotation")}: ${avatar(DB.rotationNext)} ${esc(uname(DB.rotationNext))}</span>`) +
    `<section class="panel"><div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>${t("company")}</th><th>${t("message")}</th><th>${t("received")}</th><th>${t("assigned_to")}</th><th>${t("sla")}</th><th></th></tr></thead><tbody>
    ${ls.map(l => { const left = l.sla_h - l.age_h; return `<tr><td class="fw-semibold">${esc(tx(l.name))}<div class="small-label">${esc(l.contact)} · <span class="ltr">${esc(l.phone)}</span></div></td><td class="small" style="min-width:200px">${esc(tx(l.msg))}</td><td class="num small">${fdate(l.at)}</td><td>${avatar(l.to)}</td>
      <td>${l.contacted ? pill(t("contacted"), "ok") : left < 0 ? pill(t("sla_breached", { n: NF.format(-left) }), "bad") : pill(t("sla_left", { n: NF.format(left) }), "warn")}</td>
      <td>${!l.contacted && (isAdmin() || l.to === me().id) ? `<button class="btn btn-sm btn-light" data-act="contacted" data-l="${l.id}">${t("mark_contacted")}</button>` : ""}</td></tr>`; }).join("")}</tbody></table></div>
    <div class="panel-b border-top small"><strong>${t("rotation")}:</strong> ${reps.map(u => `${avatar(u.id)} ${esc(tx(u.name))}`).join(" → ")} · <span class="mono">POST /api/leads</span></div></section>`;
};
HANDLERS.contacted = a => { DB.leads.find(l => l.id === a.dataset.l).contacted = true; saveState(); render(); };

// ---------- Vendors ----------
VIEWS.vendors = () => pageHead(t("vendors_title"), "") + `<section class="panel"><div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>${t("vendor")}</th><th>${t("vtype")}</th><th>${t("country")}</th><th>${t("email")}</th><th>${t("quotes_6m")}</th><th>${t("won")}</th></tr></thead><tbody>
  ${DB.vendors.map(v => `<tr><td class="fw-semibold">${esc(v.name)}</td><td>${pill(t("vt_" + v.type))}</td><td>${esc(tx(v.country))}</td><td class="ltr small">${esc(v.contact)}</td><td class="num">${v.quotes}</td><td class="num">${v.won} <span class="small-label">(${Math.round((v.won / v.quotes) * 100)}%)</span></td></tr>`).join("")}</tbody></table></div></section>`;

// ---------- Invoices ----------
function invTotal(i, net) { const sub = i.lines.reduce((s, l) => s + l.q * l.p, 0); if (net) return sub; return sub + i.lines.reduce((s, l) => s + (l.vat ? l.q * l.p * 0.14 : 0), 0); }
function invStatus(i) { const tot = invTotal(i); return i.paid >= tot - 0.01 ? ["paid", "ok"] : i.paid > 0 ? ["partly", "warn"] : i.due < TODAY ? ["overdue", "bad"] : ["unpaid", ""]; }
function invoiceTable(invs) {
  if (!invs.length) return `<div class="empty">—</div>`;
  return `<div class="tbl-wrap"><table class="table table-hover align-middle"><thead><tr><th>${t("inv_no")}</th><th>${t("client")}</th><th>${t("total")}</th><th>${t("balance")}</th><th>${t("due_date")}</th><th>${t("status")}</th></tr></thead><tbody>
    ${invs.map(i => { const s = invStatus(i), tot = invTotal(i); return `<tr><td>${canOpen("invoice") ? `<a class="row-link mono" href="#invoice-${i.id}">${i.id}</a>` : `<span class="mono">${i.id}</span>`}<div class="small-label mono">${i.file}</div></td><td class="small">${esc(tx(client(i.client).company))}</td><td>${money(tot, i.cur)}</td><td>${money(tot - i.paid, i.cur)}</td><td class="num">${fdate(i.due)}</td><td>${pill(t(s[0]), s[1])}</td></tr>`; }).join("")}</tbody></table></div>`;
}
VIEWS.invoices = () => pageHead(t("invoices_title"), "") + `<section class="panel">${invoiceTable(DB.invoices)}</section>`;
const BANKS = { USD: ["Bank name (USD)", "Account no. ••••", "IBAN EG•• •••• •••• ••••", "SWIFT ••••••"], EUR: ["Bank name (EUR)", "Account no. ••••", "IBAN EG•• ••••", "SWIFT ••••••"], EGP: ["Bank name (EGP)", "Account no. ••••", "Branch ••••", ""] };
VIEWS.invoice = id => {
  const i = DB.invoices.find(x => x.id === id); if (!i) return noAccess();
  const c = client(i.client), sub = invTotal(i, true), tot = invTotal(i), vat = tot - sub, s = invStatus(i);
  return `<div class="mb-2"><a href="#invoices" class="small">← ${t("invoices_title")}</a></div>` + pageHead(`<span class="mono">${i.id}</span>`, `<a href="#file-${i.file}" class="mono">${i.file}</a>`, `${pill(t(s[0]), s[1])} <button class="btn btn-sm btn-light" data-act="pay" data-inv="${i.id}">${t("record_payment")}</button>`) +
    `<div class="paper"><div class="d-flex justify-content-between flex-wrap gap-3 mb-4"><div><div class="d-flex align-items-center gap-2"><span class="brand-mark">PC</span><span class="t fw-bold fs-5">Paradigm Cargo</span></div><div class="small mt-2">${t("tax_reg")}: <span class="mono">•••-•••-•••</span><br>${t("cr_no")}: <span class="mono">••••••</span></div></div>
      <div class="text-end"><div class="t fw-bold fs-5">${t("invoice")}</div><div class="small mono">${i.id}</div><div class="small">${t("date")}: ${fdate(i.date)}<br>${t("due_date")}: ${fdate(i.due)}<br>${t("e_inv")}: <span class="mono">${i.einv}</span></div></div></div>
      <div class="mb-3 small"><strong>${t("bill_to")}:</strong> ${esc(tx(c.company))}${c.tax ? ` · ${t("tax_id")} <span class="mono">${c.tax}</span>` : ""}<br><strong>${t("shipment")}:</strong> <span class="mono">${i.file}</span> · ${esc(port(file(i.file)?.origin))} → ${esc(port(file(i.file)?.dest))} · ${esc(file(i.file)?.ship?.bl || "")}</div>
      <table><thead><tr><th>${t("description")}</th><th>${t("qty")}</th><th>${t("unit_price")}</th><th>${t("vat")}</th><th>${t("amount")}</th></tr></thead><tbody>
        ${i.lines.map(l => `<tr><td>${esc(tx(l.d))}</td><td>${l.q}</td><td>${NF2.format(l.p)}</td><td>${l.vat ? NF2.format(l.q * l.p * 0.14) : t("exempt")}</td><td>${NF2.format(l.q * l.p)}</td></tr>`).join("")}</tbody></table>
      <div class="d-flex justify-content-end mt-3"><table style="max-width:320px"><tr><td>${t("subtotal")}</td><td class="text-end">${NF2.format(sub)} ${i.cur}</td></tr><tr><td>${t("vat")}</td><td class="text-end">${NF2.format(vat)} ${i.cur}</td></tr><tr><th>${t("total")}</th><th class="text-end">${NF2.format(tot)} ${i.cur}</th></tr></table></div>
      <div class="words mt-3"><div><strong>${t("in_words")}:</strong></div><div dir="ltr">${esc(totalInWords(tot, i.cur, "en"))}</div><div dir="rtl">${esc(totalInWords(tot, i.cur, "ar"))}</div></div>
      <div class="bank mt-3"><strong>${t("bank_details")} (${i.cur})</strong><div class="ltr">${BANKS[i.cur].filter(Boolean).join(" · ")}</div></div>
      <div class="small text-center mt-4" style="color:#666">${t("inv_footer")}</div></div>`;
};
HANDLERS.pay = a => {
  const i = DB.invoices.find(x => x.id === a.dataset.inv), bal = invTotal(i) - i.paid;
  openModal(`<form data-submit="savepay" data-inv="${i.id}"><div class="modal-header"><h5 class="modal-title">${t("record_payment")} · <span class="mono">${i.id}</span></h5></div><div class="modal-body"><label class="form-label" for="pay-amt">${t("amount")} (${i.cur})</label><input id="pay-amt" type="number" min="0" step="0.01" class="form-control num" value="${bal.toFixed(2)}"><div class="small-label mt-2">${t("balance")}: ${money(bal, i.cur, 1)}</div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">${t("cancel")}</button><button class="btn btn-primary">${t("save")}</button></div></form>`);
};
HANDLERS.savepay = form => { const i = DB.invoices.find(x => x.id === form.dataset.inv); i.paid += +$("#pay-amt").value || 0; closeModal(); saveState(); toast(t("saved")); setTimeout(render, 200); };

// ---------- Collections ----------
VIEWS.collections = () => {
  const dueTxt = d => { const n = daysBetween(TODAY, d); return n < 0 ? pill(t("days_late", { n: -n }), "bad") : n <= 3 ? pill(t("days_left", { n }), "warn") : `<span class="small-label">${t("days_left", { n })}</span>`; };
  return pageHead(t("collections_title"), "") + `<div class="stack">${panel(t("client_balances"), `<div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>${t("inv_no")}</th><th>${t("client")}</th><th>${t("balance")}</th><th>${t("due_date")}</th><th></th><th></th></tr></thead><tbody>
    ${DB.invoices.filter(i => invTotal(i) - i.paid > 0.01).sort((a, b) => a.due.localeCompare(b.due)).map(i => `<tr><td><a class="mono row-link" href="#invoice-${i.id}">${i.id}</a></td><td class="small">${esc(tx(client(i.client).company))}</td><td>${money(invTotal(i) - i.paid, i.cur, 1)}</td><td class="num">${fdate(i.due)}</td><td>${dueTxt(i.due)}</td><td><button class="btn btn-sm btn-light" data-act="pay" data-inv="${i.id}">${t("record_payment")}</button></td></tr>`).join("")}</tbody></table></div>`, "", true)}
    ${panel(t("vendor_due"), `<div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>${t("vendor")}</th><th>${t("file_no")}</th><th>${t("amount")}</th><th>${t("due_date")}</th><th></th><th></th></tr></thead><tbody>
    ${DB.vendorDues.map((d, i) => `<tr><td>${esc(vendor(d.vendor).name)}</td><td class="mono"><a href="#file-${d.file}">${d.file}</a></td><td>${money(d.amt, d.cur)}</td><td class="num">${fdate(d.due)}</td><td>${d.paid ? pill(t("paid"), "ok") : dueTxt(d.due)}</td><td>${d.paid ? "" : `<button class="btn btn-sm btn-light" data-act="payvendor" data-i="${i}">${t("pay_vendor")}</button>`}</td></tr>`).join("")}</tbody></table></div>`, "", true)}</div>`;
};
HANDLERS.payvendor = a => { DB.vendorDues[+a.dataset.i].paid = true; saveState(); render(); };

// ---------- Performance ----------
VIEWS.performance = () => {
  const ids = isAdmin() ? ["u1", "u2", "u3"] : [me().id];
  return pageHead(t("perf_title"), t("perf_sub")) + `<section class="panel"><div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>${t("sales_owner")}</th><th>${t("target")}</th><th>${t("achieved")}</th><th style="min-width:140px">%</th><th>${t("commission")}</th><th>${t("files_won")}</th><th>${t("leads_handled")}</th><th>${t("self_review")}</th><th>${t("mgr_review")}</th></tr></thead><tbody>
  ${ids.map(id => { const u = user(id), p = DB.perf[id], pc = Math.round((p.achieved / u.target) * 100); return `<tr><td>${avatar(id)} ${esc(tx(u.name))}</td><td>${money(u.target)}</td><td class="fw-semibold">${money(p.achieved)}</td>
    <td><div class="progress" style="height:8px" role="progressbar" aria-valuenow="${pc}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:${Math.min(pc, 100)}%"></div></div><span class="small-label num">${pc}%</span></td>
    <td>${u.commission ? money(p.achieved * u.commission / 100) + ` <span class="small-label">(${u.commission}%)</span>` : "—"}</td><td class="num">${p.files}</td><td class="num">${p.leads}</td><td>${p.self ? pill(t("submitted"), "ok") : pill(t("pending"), "warn")}</td><td>${p.mgr ? pill(t("submitted"), "ok") : pill(t("pending"), "warn")}</td></tr>`; }).join("")}</tbody></table></div></section>`;
};

// ---------- Users ----------
const ALL_ROLES = ["admin", "sales", "pricing", "pricing_review", "ops", "customs", "accounts"];
VIEWS.users = () => pageHead(t("users_title"), t("users_sub")) + `<div class="ai-box mb-3 small">${t("admin_note")}</div><section class="panel"><div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>${t("name")}</th><th>${t("initials")}</th><th>${t("roles")}</th><th>${t("target")}</th><th>${t("commission")}</th><th>${t("gets_leads")}</th></tr></thead><tbody>
  ${DB.users.map(u => `<tr><td>${avatar(u.id)} ${esc(tx(u.name))}<div class="small-label ltr">${esc(u.email)}</div></td><td class="mono">${u.initials}</td>
    <td style="min-width:260px">${u.roles.length ? "" : pill(t("no_roles"), "bad") + "<br>"}<div class="d-flex flex-wrap gap-1 mt-1">${ALL_ROLES.map(r => `<input type="checkbox" class="btn-check" id="ur-${u.id}-${r}" ${u.roles.includes(r) ? "checked" : ""} data-change="urole" data-u="${u.id}" data-r="${r}"><label class="btn btn-sm btn-outline-primary py-0" for="ur-${u.id}-${r}">${roleName(r)}</label>`).join("")}</div></td>
    <td>${u.roles.includes("sales") ? money(u.target) : "—"}</td><td>${u.roles.includes("sales") ? (u.commission || 0) + "%" : "—"}</td>
    <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="gl-${u.id}" ${u.gets_leads ? "checked" : ""} data-change="uleads" data-u="${u.id}" aria-label="${t("gets_leads")}"></div></td></tr>`).join("")}</tbody></table></div></section>`;
HANDLERS.urole = el => { const u = user(el.dataset.u); u.roles = el.checked ? [...u.roles, el.dataset.r] : u.roles.filter(r => r !== el.dataset.r); saveState(); render(); };
HANDLERS.uleads = el => { user(el.dataset.u).gets_leads = el.checked; saveState(); };

// ---------- Settings ----------
VIEWS.config = () => {
  const s = DB.settings, ai = s.ai;
  return pageHead(t("config_title"), "") + `<div class="grid g-2">
    ${panel(t("cfg_margin"), `<div class="input-group" style="max-width:240px"><span class="input-group-text">$</span><input id="cfg-margin" type="number" min="0" class="form-control num" value="${s.minMargin}" data-change="cfgmargin"></div>`)}
    ${panel(t("cfg_ai"), `<div class="small-label mb-2">${t("ai_model")}: <span class="mono">${esc(ai.model)}</span></div>
      ${["f1", "f2", "f3"].map(k => `<div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" id="cfg-${k}" ${ai[k] ? "checked" : ""} data-change="cfgai" data-k="${k}"><label class="form-check-label" for="cfg-${k}">${t("ai_" + k)}</label></div>`).join("")}
      <label class="form-label mt-2" for="cfg-ailimit">${t("ai_limit")}</label><input id="cfg-ailimit" type="number" min="0" class="form-control num" style="max-width:160px" value="${ai.limit}" data-change="cfgailimit"><div class="small-label mt-1">${t("ai_spent")}: ${money(ai.spent, "USD", 1)}</div>`)}
    ${panel(t("cfg_rates"), `<div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>${t("currency")}</th><th>1 USD =</th><th>${t("status")}</th><th></th></tr></thead><tbody>${Object.entries(DB.rates).map(([c, r]) => `<tr><td class="mono">${c}</td><td><input type="number" step="0.0001" class="form-control form-control-sm num" style="max-width:130px" value="${r.v}" ${r.locked ? "" : "disabled"} data-change="cfgrate" data-c="${c}" aria-label="${c}"></td><td>${r.locked ? pill(t("locked_rate"), "warn") : pill(t("auto"), "ok")}</td><td><button class="btn btn-sm btn-light" data-act="lockrate" data-c="${c}">${r.locked ? t("unlock") : t("lock")}</button></td></tr>`).join("")}</tbody></table></div><div class="small-label p-3">${t("updated_daily")}</div>`, "", true)}
    ${panel(t("cfg_bank"), Object.entries(BANKS).map(([c, b]) => `<div class="mb-2"><span class="mono fw-semibold">${c}</span> <span class="small ltr">${b.filter(Boolean).join(" · ")}</span></div>`).join("") + `<div class="small-label mt-2">${t("cfg_company")}: ${t("tax_reg")} · ${t("cr_no")}</div>`)}</div>`;
};
HANDLERS.cfgmargin = el => { DB.settings.minMargin = +el.value || 0; saveState(); toast(t("saved")); };
HANDLERS.cfgai = el => { DB.settings.ai[el.dataset.k] = el.checked; saveState(); toast(t("saved")); };
HANDLERS.cfgailimit = el => { DB.settings.ai.limit = +el.value || 0; saveState(); toast(t("saved")); };
HANDLERS.cfgrate = el => { DB.rates[el.dataset.c].v = +el.value; saveState(); toast(t("saved")); };
HANDLERS.lockrate = a => { const r = DB.rates[a.dataset.c]; r.locked = !r.locked; saveState(); render(); };

// ---------- AI usage ----------
VIEWS.ai = () => pageHead(t("nav_ai"), "") + `<div class="grid g-main"><section class="panel"><div class="panel-h"><h2>${t("ai_runs")}</h2></div><div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>${t("date")}</th><th>${t("feature")}</th><th>${t("owner")}</th><th>${t("file_no")}</th><th>${t("tokens")}</th><th>${t("cost_usd")}</th><th>${t("run_status")}</th></tr></thead><tbody>
  ${DB.aiRuns.map(r => `<tr><td class="num small">${fdate(r.at)}</td><td class="small">${t(r.f)}</td><td>${avatar(r.user)}</td><td class="mono small">${r.file ? `<a href="#file-${r.file}">${r.file}</a>` : "—"}</td><td class="num">${NF.format(r.tokens)}</td><td class="num">${NF2.format(r.cost)}</td><td>${pill(t(r.status), { rs_accepted: "ok", rs_rejected: "bad", rs_failed: "bad", rs_done: "info" }[r.status])}</td></tr>`).join("")}</tbody></table></div></section>${panel(t("cfg_ai"), aiSummary())}</div>`;
