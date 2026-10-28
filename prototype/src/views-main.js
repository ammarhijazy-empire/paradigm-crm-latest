// ---------- Login ----------
VIEWS.login = () => `
  <div class="login-wrap"><div class="login-card">
    <div class="d-flex align-items-center gap-2 mb-3"><span class="brand-mark">PC</span><strong>Paradigm Cargo</strong>
      <button class="btn btn-light btn-sm ms-auto" data-act="lang">${ico("globe")} ${t("lang_switch")}</button></div>
    <h1>${t("login_title")}</h1><p class="muted mb-3">${t("login_sub")}</p>
    <form id="login-form" novalidate>
      <div class="mb-3"><label class="form-label" for="lg-email">${t("email")}</label><input id="lg-email" type="email" class="form-control ltr" value="ammar@paradigmcargo.com" autocomplete="username"></div>
      <div class="mb-2"><label class="form-label" for="lg-pass">${t("password")}</label><input id="lg-pass" type="password" class="form-control" value="demo-password" autocomplete="current-password"></div>
      <div id="lg-err" class="text-danger small mb-2" hidden>${t("login_domain_err")}</div>
      <button class="btn btn-primary w-100 mt-2" type="submit">${t("login_btn")}</button>
      <div class="d-flex justify-content-between mt-3 small"><a href="#login">${t("forgot")}</a></div>
      <div class="small-label mt-3">${t("login_demo")}</div>
    </form>
  </div></div>`;
function bindLogin() {
  $("#login-form").addEventListener("submit", e => {
    e.preventDefault(); e.stopPropagation();
    const em = $("#lg-email").value.trim().toLowerCase();
    if (!em.endsWith("@paradigmcargo.com")) { $("#lg-err").hidden = false; return; }
    const u = DB.users.find(x => x.email === em);
    if (u && u.roles.length) { ROLE = u.roles[0]; DB.roleUser[ROLE] = u.id; }
    saveState(); location.hash = "dashboard";
  }, true);
}

// ---------- Shared tables ----------
function filesTable(files, opts = {}) {
  if (!files.length) return `<div class="empty">—</div>`;
  const showClient = canSeeContact() || true;
  return `<div class="tbl-wrap"><table class="table table-hover align-middle">
    <thead><tr><th>${t("file_no")}</th><th>${t("client")}</th><th>${t("service")}</th><th>${t("route")}</th><th>${t("stage")}</th>${opts.days ? `<th>${t("days_in_stage")}</th>` : ""}<th>${t("sales_owner")}</th><th>${t("eta")}</th></tr></thead>
    <tbody>${files.map(f => {
      const c = client(f.client), d = workDaysSince(f.since);
      return `<tr><td><a class="row-link mono" href="#file-${f.id}">${f.id}</a><div class="small-label mono">${f.ref}</div></td>
        <td class="client-cell">${showClient ? esc(tx(c.company)) : ""}</td>
        <td class="svc">${esc(t(SVC_KEY[f.service]))}${f.addons.length ? `<div class="small-label">+ ${f.addons.map(a => t(SVC_KEY[a])).join("، ")}</div>` : ""}</td>
        <td class="small route">${esc(port(f.origin))} <span class="muted">→</span> ${esc(port(f.dest))}</td>
        <td>${f.status === "lost" ? pill(t("s_lost"), "bad") : f.status === "closed" ? pill(t("s_closed"), "ok") : stageChip(f.stage)}</td>
        ${opts.days ? `<td>${d > 3 ? pill(d + " " + t("days"), "warn") : `<span class="num">${d}</span>`}</td>` : ""}
        <td>${avatar(f.sales)}</td><td class="num">${fdate(f.ship?.eta)}</td></tr>`;
    }).join("")}</tbody></table></div>`;
}
function kpi(label, val, note = "", kind = "") { return `<div class="panel kpi"><div class="lbl">${label}</div><div class="val ${kind ? "text-" + kind : ""}">${val}</div>${note ? `<div class="note">${note}</div>` : ""}</div>`; }
function panel(title, body, extra = "", flush = false) { return `<section class="panel"><div class="panel-h"><h2>${title}</h2>${extra}</div><div class="panel-b ${flush ? "flush" : ""}">${body}</div></section>`; }

function marginOf(f) { return sellTotalUSD(f) - costTotalUSD(f); }

// ---------- Dashboard ----------
VIEWS.dashboard = () => {
  const u = me(), open = DB.files.filter(f => f.status === "open");
  const head = pageHead(t("dash_title", { name: esc(tx(u.name)) }), isAdmin() ? t("dash_sub_admin") : t("dash_sub_role") + " · " + roleName(ROLE));
  const waiting = open.filter(f => workDaysSince(f.since) > 3);
  if (isAdmin()) {
    const profit = Object.values(DB.perf).reduce((s, p) => s + p.achieved, 0), goal = DB.users.reduce((s, x) => s + (x.target || 0), 0);
    const toCollect = DB.invoices.reduce((s, i) => s + toUSD(invTotal(i) - i.paid, i.cur), 0);
    const overdue = DB.invoices.filter(i => i.due < TODAY).reduce((s, i) => s + toUSD(invTotal(i) - i.paid, i.cur), 0);
    const max = Math.max(...STAGES.map(s => open.filter(f => f.stage === s.n).length), 1);
    const pipe = STAGES.map(s => { const c = open.filter(f => f.stage === s.n).length; return `<div class="pipe-row"><span>${stageChip(s.n)}</span><span class="pipe-bar"><span style="width:${(c / max) * 100}%"></span></span><span class="c">${c}</span></div>`; }).join("");
    const lostMax = Math.max(...DB.lostStats.map(x => x[1]));
    const lost = DB.lostStats.map(([k, n]) => `<div class="pipe-row"><span>${t(k)}</span><span class="pipe-bar"><span style="width:${(n / lostMax) * 100}%;background:var(--bad)"></span></span><span class="c">${n}</span></div>`).join("");
    const reps = ["u1", "u2", "u3"].map(id => { const p = DB.perf[id], tg = user(id).target, pc = Math.round((p.achieved / tg) * 100); return `<div class="mb-3"><div class="split small"><span>${avatar(id)} ${esc(uname(id))}</span><span class="num ltr">${money(p.achieved)} / ${money(tg)} · ${pc}%</span></div><div class="progress mt-1" style="height:8px" role="progressbar" aria-valuenow="${pc}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:${Math.min(pc, 100)}%"></div></div></div>`; }).join("");
    const lowMargin = open.filter(f => f.sell.length && marginOf(f) < DB.settings.minMargin);
    const payPending = open.filter(f => f.pay_terms && f.pay_terms.status === "pending");
    const appr = [...lowMargin.map(f => `<div class="split py-2 border-bottom"><span><a class="row-link mono" href="#file-${f.id}">${f.id}</a> · ${t("appr_margin")}</span><span>${money(marginOf(f))}</span></div>`), ...payPending.map(f => `<div class="split py-2 border-bottom"><a class="row-link mono" href="#file-${f.id}">${f.id}</a><span>${t("pay_terms")}</span></div>`)].join("") || `<div class="empty py-3">${t("none")}</div>`;
    return head + `<div class="grid g-4 mb-3">
        ${kpi(t("k_open_files"), open.length)}
        ${kpi(t("k_profit_month"), money(profit), t("k_goal", { goal: money(goal) }))}
        ${kpi(t("k_waiting"), waiting.length, t("k_waiting_note"), waiting.length ? "warning" : "")}
        ${kpi(t("k_collect"), money(toCollect), t("k_overdue_note", { amt: money(overdue) }))}
      </div>
      <div class="grid g-main mb-3">${panel(t("pipeline"), pipe)}${panel(t("profit_by_rep"), reps)}</div>
      <div class="mb-3">${panel(t("waiting_files"), filesTable(waiting, { days: 1 }), "", true)}</div>
      <div class="grid g-3">${panel(t("approvals"), appr)}${panel(t("lost_reasons"), lost)}${panel(t("nav_ai"), aiSummary())}</div>`;
  }
  if (ROLE === "sales") {
    const mine = open.filter(f => f.sales === u.id), p = DB.perf[u.id], pc = Math.round((p.achieved / u.target) * 100);
    const follow = DB.clients.filter(c => c.owner === u.id && c.next_follow && c.next_follow <= TODAY);
    const leads = DB.leads.filter(l => l.to === u.id && !l.contacted);
    return head + `<div class="grid g-4 mb-3">${kpi(t("k_my_files"), mine.length)}${kpi(t("k_followups"), follow.length, "", follow.length ? "warning" : "")}${kpi(t("k_target"), pc + "%", `${money(p.achieved)} / ${money(u.target)}`)}${kpi(t("k_new_leads"), leads.length, "", leads.length ? "warning" : "")}</div>
      <div class="grid g-main">${panel(t("my_queue"), filesTable(mine, { days: 1 }), `<a class="btn btn-primary btn-sm" href="#rfq">${ico("plus")} ${t("new_file")}</a>`, true)}
      ${panel(t("k_followups"), follow.map(c => `<div class="split py-2 border-bottom"><a class="row-link" href="#client-${c.id}">${esc(tx(c.company))}</a>${pill(t("cs_" + c.status), statusKind(c.status))}</div>`).join("") || `<div class="empty">${t("none")}</div>`)}</div>`;
  }
  if (ROLE === "pricing" || ROLE === "pricing_review") {
    const toPrice = open.filter(f => f.pricing === "pending"), review = open.filter(f => f.pricing === "review"), mails = DB.inbound.filter(m => m.status !== "confirmed");
    return head + `<div class="grid g-3 mb-3">${kpi(t("k_pricing_queue"), toPrice.length)}${kpi(t("k_rate_emails"), mails.length, "", mails.length ? "warning" : "")}${kpi(t("k_review_queue"), review.length)}</div>` + pricingDesk(true);
  }
  if (ROLE === "ops") {
    const st3 = open.filter(f => f.stage === 3), st4 = open.filter(f => f.stage === 4), blockers = open.reduce((s, f) => s + openBlockers(f), 0);
    return head + `<div class="grid g-3 mb-3">${kpi(t("k_bookings"), st3.length)}${kpi(t("k_arrivals"), st4.filter(f => f.ship?.eta && daysBetween(TODAY, f.ship.eta) <= 7).length)}${kpi(t("k_doc_blockers"), blockers, "", blockers ? "danger" : "")}</div>` + panel(t("my_queue"), filesTable([...st3, ...st4], { days: 1 }), "", true);
  }
  if (ROLE === "customs") {
    const st5 = open.filter(f => f.stage === 5), arriving = open.filter(f => f.stage === 4);
    return head + `<div class="grid g-3 mb-3">${kpi(t("k_clearance"), st5.length)}${kpi(t("k_release"), st5.filter(f => !f.checks.c5_release).length)}${kpi(t("k_arrivals"), arriving.length)}</div>` + panel(t("my_queue"), filesTable([...st5, ...arriving], { days: 1 }), "", true);
  }
  if (ROLE === "accounts") {
    const dueWeek = DB.invoices.filter(i => invTotal(i) > i.paid && daysBetween(TODAY, i.due) <= 7);
    const vd = DB.vendorDues.filter(d => !d.paid && daysBetween(TODAY, d.due) <= 7);
    const pt = open.filter(f => f.pay_terms && f.pay_terms.status === "pending");
    return head + `<div class="grid g-3 mb-3">${kpi(t("k_invoices_due"), dueWeek.length)}${kpi(t("k_vendor_dues"), vd.length, "", vd.length ? "warning" : "")}${kpi(t("k_payterms"), pt.length)}</div>
      <div class="grid g-2">${panel(t("client_balances"), invoiceTable(DB.invoices.filter(i => invTotal(i) > i.paid)), "", true)}${panel(t("my_queue"), filesTable(open.filter(f => f.stage === 6 || (f.stage === 5 && !f.checks.c5_release)), { days: 1 }), "", true)}</div>`;
  }
  return head;
};
function statusKind(s) { return { lead: "info", contacted: "warn", active: "ok", repeat: "rust", lost: "bad" }[s] || ""; }
function aiSummary() {
  const s = DB.settings.ai, pc = Math.round((s.spent / s.limit) * 100);
  return `<div class="split small mb-1"><span>${t("ai_spent")}</span><span>${money(s.spent, "USD", 1)} / ${money(s.limit)}</span></div>
    <div class="progress mb-3" style="height:8px"><div class="progress-bar" style="width:${pc}%"></div></div>
    <div class="small-label mb-2">${t("ai_model")}: <span class="mono">${esc(s.model)}</span></div>
    ${["f1", "f2", "f3"].map(k => `<div class="split py-1 small"><span>${t("ai_" + k)}</span>${s[k] ? pill(t("active"), "ok") : pill(t("no"))}</div>`).join("")}
    <a href="#ai" class="small d-inline-block mt-2">${t("ai_runs")} →</a>`;
}

// ---------- Files list ----------
let FILE_FILTER = { stage: "", status: "open" };
VIEWS.files = () => {
  let fs = visibleFiles();
  if (SEARCH_Q) { const q = SEARCH_Q.toLowerCase(); fs = fs.filter(f => [f.id, f.ref, tx(client(f.client).company), f.ship?.bl, f.ship?.booking].join(" ").toLowerCase().includes(q)); }
  if (FILE_FILTER.status) fs = fs.filter(f => f.status === FILE_FILTER.status);
  if (FILE_FILTER.stage) fs = fs.filter(f => f.stage === +FILE_FILTER.stage);
  const stOpts = STAGES.map(s => `<option value="${s.n}" ${FILE_FILTER.stage == s.n ? "selected" : ""}>${s.n} · ${t("st" + s.n)}</option>`).join("");
  return pageHead(t("files_title"), t("files_sub") + (ROLE === "sales" ? " · " + t("only_mine") : ""), ROLE === "sales" || isAdmin() ? `<a class="btn btn-primary btn-sm" href="#rfq">${ico("plus")} ${t("new_file")}</a>` : "") +
    `<section class="panel"><div class="panel-h"><div class="filters">
      <select id="ff-stage" class="form-select form-select-sm" data-change="ffilter" aria-label="${t("stage")}"><option value="">${t("stage")}: ${t("all")}</option>${stOpts}</select>
      <select id="ff-status" class="form-select form-select-sm" data-change="ffilter" aria-label="${t("status")}">${["", "open", "closed", "lost"].map(s => `<option value="${s}" ${FILE_FILTER.status === s ? "selected" : ""}>${t("status")}: ${s ? t("s_" + s) : t("all")}</option>`).join("")}</select>
      ${SEARCH_Q ? `<span class="pill info">“${esc(SEARCH_Q)}” <a href="#" data-act="clearq" class="text-reset">×</a></span>` : ""}
    </div><span class="small-label">${fs.length}</span></div><div class="panel-b flush">${filesTable(fs, { days: 1 })}</div></section>`;
};
HANDLERS.ffilter = () => { FILE_FILTER = { stage: $("#ff-stage").value, status: $("#ff-status").value }; render(); };
HANDLERS.clearq = () => { SEARCH_Q = ""; render(); };

VIEWS.ops = () => {
  const open = DB.files.filter(f => f.status === "open");
  return pageHead(t("nav_ops"), "") + `<div class="stack">${[3, 4, 5].map(n => panel(stageChip(n), filesTable(open.filter(f => f.stage === n), { days: 1 }), `<span class="small-label">${t("owner")}: ${roleName(STAGES[n - 1].owner)}</span>`, true)).join("")}</div>`;
};

// ---------- File detail ----------
let FILE_TAB = "overview";
VIEWS.file = id => {
  const f = file(id);
  if (!f || (ROLE === "sales" && f.sales !== me().id)) return noAccess();
  const c = client(f.client);
  const tabs = [["overview", "t_overview"], ["rfq", "t_rfq"], ["pricing", "t_pricing"], ["quote", "t_quote"], ["shipping", "t_shipping"], ["docs", "t_docs"], ["money", "t_money"], ["history", "t_history"]]
    .filter(([k]) => k !== "money" || canSeeMoney() || ROLE === "sales");
  if (!tabs.find(x => x[0] === FILE_TAB)) FILE_TAB = "overview";
  const status = f.status === "lost" ? pill(t("s_lost"), "bad") : f.status === "closed" ? pill(t("s_closed"), "ok") : stageChip(f.stage);
  const blockers = openBlockers(f);
  const head = `<div class="mb-2"><a href="#files" class="small">← ${t("files_title")}</a></div>` +
    pageHead(`<span class="mono">${f.id}</span> · ${esc(tx(c.company))}`, `<span class="mono">${f.ref}</span> · ${esc(t(SVC_KEY[f.service]))} · ${esc(port(f.origin))} → ${esc(port(f.dest))} · ${f.incoterm}`,
      `${status}${blockers ? pill(t("gate_need_blockers", { n: blockers }).split("—")[0], "bad") : ""}`);
  const stepper = `<div class="stepper mb-3">${STAGES.map(s => `<div class="step ${f.stage > s.n || f.status === "closed" ? "done" : f.stage === s.n && f.status === "open" ? "current" : ""}"><div class="n">${s.n}</div><div class="t">${t("st" + s.n)}</div><div class="o">${roleName(s.owner)}</div></div>`).join("")}</div>`;
  const nav = `<ul class="nav nav-tabs mb-3" role="tablist">${tabs.map(([k, l]) => `<li class="nav-item"><a class="nav-link ${FILE_TAB === k ? "active" : ""}" href="#" data-act="ftab" data-tab="${k}">${t(l)}${k === "docs" && blockers ? ` <span class="pill bad">${blockers}</span>` : ""}</a></li>`).join("")}</ul>`;
  return head + stepper + nav + `<div id="ftab-body">${FILE_TABS[FILE_TAB](f)}</div>`;
};
HANDLERS.ftab = a => { FILE_TAB = a.dataset.tab; render(); };

const FILE_TABS = {};
FILE_TABS.overview = f => {
  const c = client(f.client), st = STAGES[f.stage - 1];
  const items = st.items.map(it => {
    const own = itemOwner(f.stage, it), ok = canTick(f.stage, it) && f.status === "open", id = "ck-" + it[0];
    return `<div class="check-item"><input class="form-check-input" type="checkbox" id="${id}" ${f.checks[it[0]] ? "checked" : ""} ${ok ? "" : "disabled"} data-change="tick" data-file="${f.id}" data-key="${it[0]}">
      <label for="${id}"><div>${t(it[0])}${it[1] ? `<span class="req" title="${t("required")}">★</span>` : ""}</div><div class="who">${roleName(own)}${ok ? "" : " · " + t("only_owner_tick", { role: roleName(own) })}</div></label></div>`;
  }).join("");
  const probs = gateProblems(f), movable = canMove(f) && f.status === "open";
  const gate = f.status !== "open" ? (f.lost ? `<div class="gate-box bad"><strong>${t("s_lost")}</strong> · ${t("stage")} ${f.lost.stage} · ${t(f.lost.cat)}<div class="mt-1">${esc(tx(f.lost.reason))}</div></div>` : "")
    : probs.length ? `<div class="gate-box bad"><strong>${t("gate_blocked")}</strong><ul>${probs.map(p => `<li>${esc(p)}</li>`).join("")}</ul></div>` : `<div class="gate-box ok">${t("gate_ready")}</div>`;
  const moveBtns = f.status === "open" ? `<div class="d-flex gap-2 flex-wrap mt-3">
      <button class="btn btn-primary btn-sm" data-act="move" data-file="${f.id}" ${movable ? "" : "disabled"} title="${movable ? "" : esc(t("gate_not_owner", { role: roleName(st.owner) }))}">${f.stage < 7 ? t("move_next", { n: f.stage + 1 }) : t("s_closed")}</button>
      ${isAdmin() && f.stage > 1 ? `<button class="btn btn-light btn-sm" data-act="moveback" data-file="${f.id}">${t("move_back")}</button>` : ""}
      ${(isAdmin() || ROLE === "sales") ? `<button class="btn btn-outline-danger btn-sm ms-auto" data-act="lost" data-file="${f.id}">${t("mark_lost")}</button>` : ""}
    </div>${movable ? "" : `<div class="small-label mt-2">${t("gate_not_owner", { role: roleName(st.owner) })}</div>`}` : "";
  const contact = canSeeContact()
    ? `<dl class="dl"><dt>${t("contact_person")}</dt><dd>${esc(tx(c.contact))}</dd><dt>${t("phone")}</dt><dd class="ltr">${esc(c.phone)}</dd><dt>${t("email")}</dt><dd class="ltr">${esc(c.email)}</dd></dl>`
    : `<div class="locked">${t("hidden_pricing")}</div>`;
  const s = f.ship || {};
  return `<div class="grid g-main">
    ${panel(`${t("checklist")} · ${stageChip(f.stage)}`, items + `<div class="mt-3">${gate}</div>` + moveBtns, `<span class="small-label">${t("owner")}: ${roleName(st.owner)}</span>`)}
    <div class="stack">
      ${panel(t("client"), `<div class="fw-semibold mb-2">${ROLE === "sales" || isAdmin() || ROLE === "accounts" ? `<a class="row-link" href="#client-${c.id}">${esc(tx(c.company))}</a>` : esc(tx(c.company))}</div>` + contact)}
      ${panel(t("shipment"), `<dl class="dl">
        <dt>${t("service")}</dt><dd>${esc(t(SVC_KEY[f.service]))}${f.addons.length ? " + " + f.addons.map(a => esc(t(SVC_KEY[a]))).join("، ") : ""}</dd>
        <dt>${t("volume")}</dt><dd class="ltr">${esc(volumeSummary(f))}</dd>
        <dt>${t("commodity")}</dt><dd>${esc(tx(f.inq.commodity))}</dd>
        <dt>${t("carrier")}</dt><dd>${esc(s.carrier || "—")}</dd>
        <dt>${t("bl")}</dt><dd class="mono">${esc(s.bl || "—")}</dd>
        <dt>${t("etd")}</dt><dd class="num">${fdate(s.atd || s.etd)}</dd><dt>${t("eta")}</dt><dd class="num">${fdate(s.eta)}</dd>
        <dt>${t("acid")}</dt><dd class="mono">${esc(f.acid || "—")}</dd>
        <dt>${t("sales_owner")}</dt><dd>${avatar(f.sales)} ${esc(uname(f.sales))}</dd><dt>${t("ops_owner")}</dt><dd>${avatar(f.ops)} ${esc(uname(f.ops))}</dd></dl>`)}
    </div></div>`;
};
HANDLERS.tick = el => {
  const f = file(el.dataset.file), st = STAGES[f.stage - 1], it = st.items.find(i => i[0] === el.dataset.key);
  if (!it || !canTick(f.stage, it)) { el.checked = !el.checked; toast(t("only_owner_tick", { role: roleName(itemOwner(f.stage, it)) }), true); return; }
  f.checks[it[0]] = el.checked;
  f.history.push([TODAY, me().id, L((el.checked ? "Ticked: " : "Unticked: ") + DICT[it[0]][0], (el.checked ? "علّم على: " : "شال العلامة: ") + DICT[it[0]][1])]);
  saveState(); render();
};
HANDLERS.move = a => {
  const f = file(a.dataset.file), probs = gateProblems(f);
  if (!canMove(f)) return toast(t("gate_not_owner", { role: roleName(STAGES[f.stage - 1].owner) }), true);
  if (probs.length) return toast(t("gate_blocked") + " " + probs[0], true);
  if (f.stage === 7) { f.status = "closed"; f.history.push([TODAY, me().id, L("File closed", "الملف اتقفل")]); }
  else { f.stage++; f.since = TODAY; f.history.push([TODAY, me().id, L("Moved to stage " + f.stage, "اتنقل للمرحلة " + f.stage)]); }
  saveState(); toast(f.status === "closed" ? t("s_closed") : t("moved_ok", { n: f.stage })); render();
};
HANDLERS.moveback = a => { const f = file(a.dataset.file); f.stage--; f.since = TODAY; f.history.push([TODAY, me().id, L("Moved back to stage " + f.stage, "رجع للمرحلة " + f.stage)]); saveState(); render(); };
HANDLERS.lost = a => {
  const f = file(a.dataset.file);
  openModal(`<form data-submit="lostsave" data-file="${f.id}"><div class="modal-header"><h5 class="modal-title">${t("lost_title")}</h5></div><div class="modal-body">
    <div class="mb-3"><label class="form-label" for="lost-cat">${t("lost_cat")}</label><select id="lost-cat" class="form-select">${["lc_price", "lc_time", "lc_competitor", "lc_cancel", "lc_other"].map(k => `<option value="${k}">${t(k)}</option>`).join("")}</select></div>
    <div><label class="form-label" for="lost-reason">${t("lost_reason")}</label><textarea id="lost-reason" class="form-control" rows="3" required></textarea></div>
    <div class="small-label mt-2">${t("stage")}: ${f.stage} · ${t("st" + f.stage)}</div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">${t("cancel")}</button><button class="btn btn-danger">${t("mark_lost")}</button></div></form>`);
};
HANDLERS.lostsave = form => {
  const r = $("#lost-reason").value.trim(); if (!r) { $("#lost-reason").classList.add("is-invalid"); return; }
  const f = file(form.dataset.file); f.status = "lost"; f.lost = { stage: f.stage, cat: $("#lost-cat").value, reason: L(r, r) };
  f.history.push([TODAY, me().id, L("Marked lost", "اتسجل كخسارة")]); closeModal(); saveState(); toast(t("lost_ok")); setTimeout(render, 200);
};

FILE_TABS.rfq = f => {
  const q = f.inq, probs = inquiryProblems(f);
  const cargoKey = { general: "cg_general", haz: "cg_haz", reefer: "cg_reefer", fragile: "cg_fragile", liquid: "cg_liquid", oog: "cg_oog" }[q.cargo];
  const pm = { cia: "pm_cia", cad: "pm_cad", arrival: "pm_arrival", credit: "pm_credit" }[q.pay];
  return `<div class="grid g-main">${panel(t("t_rfq"), `<dl class="dl">
      <dt>${t("direction")}</dt><dd>${t(f.dir)}</dd>
      <dt>${t("main_service")}</dt><dd>${esc(t(SVC_KEY[f.service]))}</dd><dt>${t("addons")}</dt><dd>${f.addons.map(a => esc(t(SVC_KEY[a]))).join("، ") || "—"}</dd>
      <dt>${t("volume")}</dt><dd class="ltr">${esc(volumeSummary(f))}</dd>
      <dt>${t("origin")}</dt><dd>${esc(port(f.origin))}${f.pickup ? `<div class="small-label">${t("pickup_addr")}: ${esc(f.pickup)}</div>` : ""}</dd>
      <dt>${t("destination")}</dt><dd>${esc(port(f.dest))}${f.delivery ? `<div class="small-label">${t("delivery_addr")}: ${esc(port(f.delivery))}</div>` : ""}</dd>
      <dt>${t("incoterm")}</dt><dd>${f.incoterm}</dd><dt>${t("cargo_type")}</dt><dd>${t(cargoKey)}</dd><dt>${t("commodity")}</dt><dd>${esc(tx(q.commodity))}</dd>
      <dt>${t("ready_date")}</dt><dd class="num">${fdate(q.ready)}</dd><dt>${t("price_by")}</dt><dd class="num">${fdate(q.price_by)}</dd>
      ${q.free ? `<dt>${t("free_time")}</dt><dd class="num">${q.free}</dd>` : ""}
      <dt>${t("freight_terms")}</dt><dd>${t(q.freight)}</dd><dt>${t("pay_method")}</dt><dd>${t(pm)}${q.credit_days ? ` · ${q.credit_days} ${t("days")}` : ""}</dd></dl>`)}
    ${panel(t("rfq_check"), probs.length ? `<div class="gate-box bad"><ul class="mt-0">${probs.map(p => `<li>${esc(p)}</li>`).join("")}</ul></div>` : `<div class="gate-box ok">${t("rfq_ok")}</div>`)}</div>`;
};

FILE_TABS.pricing = f => {
  const quotes = (f.vq || []).map(id => ({ id, ...DB.vendorQuotes[id] }));
  const ranked = rankQuotes(quotes);
  const canPrice = isAdmin() || ROLE === "pricing";
  const vqTable = ranked.length ? `<div class="small-label px-3 pt-3">${t("vq_rank_note")}</div><div class="tbl-wrap"><table class="table align-middle mt-2"><thead><tr><th>#</th><th>${t("vendor")}</th><th>${t("carrier")}</th><th>${t("freight")}</th><th>${t("surcharges")}</th><th>${t("total_usd")}</th><th>${t("tt")}</th><th>${t("free_days")}</th><th>${t("validity")}</th><th>${t("why")}</th><th></th></tr></thead><tbody>
    ${ranked.map((q, i) => `<tr><td><span class="rank-badge ${i === 0 && q.ok ? "first" : ""}">${q.ok ? i + 1 : "—"}</span></td>
      <td>${esc(vendor(q.vendor).name)}<div class="small-label mono">${q.ref}</div></td><td>${esc(q.carrier)}</td><td>${money(q.freight)}</td><td>${money(q.sur)}</td><td class="fw-semibold">${money(q.total)}</td>
      <td class="num">${q.tt} ${t("days")}</td><td class="num">${q.free}</td><td class="num">${q.valid < TODAY ? pill(fdate(q.valid), "bad") : fdate(q.valid)}</td>
      <td class="small" style="min-width:180px">${q.confirmed ? "" : pill(t("draft_ai"), "info ai") + "<br>"}${esc(tx(q.why))}</td>
      <td>${f.chosen === q.id ? pill(t("chosen"), "ok") : canPrice && f.status === "open" ? (q.confirmed ? `<button class="btn btn-sm btn-outline-primary" data-act="choose" data-file="${f.id}" data-q="${q.id}">${t("choose_cost")}</button>` : `<button class="btn btn-sm btn-light" data-act="confirmq" data-q="${q.id}">${t("confirm_quote")}</button>`) : ""}</td></tr>`).join("")}</tbody></table></div>` : `<div class="empty">—</div>`;
  const showAct = canSeeMoney();
  const costs = `<div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>${t("line")}</th><th>${t("vendor")}</th><th>${t("est")}</th>${showAct ? `<th>${t("actual")}</th>` : ""}<th>${t("currency")}</th></tr></thead><tbody>
    ${(f.costs || []).map(c => `<tr><td>${t(c.type)}</td><td class="small">${esc(vendor(c.vendor)?.name || "—")}</td><td class="num">${NF.format(c.est)}</td>${showAct ? `<td class="num">${c.act != null ? NF.format(c.act) : "—"}</td>` : ""}<td>${c.cur}</td></tr>`).join("") || `<tr><td colspan="5" class="empty">—</td></tr>`}
    <tr class="fw-semibold"><td colspan="2">${t("total")} (USD)</td><td>${money(costTotalUSD(f))}</td>${showAct ? `<td>${(f.costs || []).some(c => c.act != null) ? money(costTotalUSD(f, "act")) : "—"}</td>` : ""}<td></td></tr></tbody></table></div>`;
  const statusMap = { draft: ["pending", ""], pending: ["k_pricing_queue", "warn"], review: ["pq_review", "info"], priced: ["reviewed_ok", "ok"], won: ["c2_approval", "ok"], lost: ["s_lost", "bad"] };
  const sm = statusMap[f.pricing] || ["pending", ""];
  const reviewBtns = f.pricing === "review" && (isAdmin() || ROLE === "pricing_review") ? `<button class="btn btn-sm btn-primary" data-act="review_ok" data-file="${f.id}">${t("approve")}</button> <button class="btn btn-sm btn-light" data-act="review_back" data-file="${f.id}">${t("return")}</button>` : "";
  const sendBtns = canPrice && f.status === "open" ? `<button class="btn btn-sm btn-light" data-act="rfqvendors" data-file="${f.id}">${ico("inbox")} ${t("send_rfq_vendors")}</button>${["pending", "draft"].includes(f.pricing) && f.costs.length ? ` <button class="btn btn-sm btn-primary" data-act="toreview" data-file="${f.id}">${t("send_review")}</button>` : ""}` : "";
  const pt = f.pay_terms || { method: f.inq.pay };
  const needAcc = ["arrival", "credit"].includes(pt.method);
  const ptBox = `<div class="small mb-2">${t({ cia: "pm_cia", cad: "pm_cad", arrival: "pm_arrival", credit: "pm_credit" }[pt.method])}${pt.days ? ` · ${pt.days} ${t("days")}` : ""}</div>
    <div class="small-label mb-2">${needAcc ? t("pt_need_acc") : t("pt_skip")}</div>
    ${needAcc ? (pt.status === "approved" ? pill(t("pt_approved"), "ok") : `${pill(t("pt_pending"), "warn")} ${isAdmin() || ROLE === "accounts" ? `<button class="btn btn-sm btn-primary ms-2" data-act="ptok" data-file="${f.id}">${t("approve")}</button>` : ""}`) : ""}`;
  return `<div class="stack">${panel(t("vq_title"), vqTable, sendBtns, true)}
    <div class="grid g-main">${panel(t("cost_lines"), costs, `<span>${pill(t(sm[0]), sm[1])} ${reviewBtns}</span>`, true)}${panel(t("pay_terms"), ptBox)}</div></div>`;
};
function rankQuotes(qs) {
  return qs.map(q => ({ ...q, total: toUSD(q.freight + q.sur + q.local, q.cur), ok: q.valid >= TODAY }))
    .sort((a, b) => (b.ok - a.ok) || (a.total - b.total) || (a.tt - b.tt) || (b.free - a.free) || (b.valid > a.valid ? 1 : -1));
}
HANDLERS.choose = a => { const f = file(a.dataset.file), q = DB.vendorQuotes[a.dataset.q]; f.chosen = a.dataset.q; const fr = f.costs.find(c => c.type === "ct_freight"); if (fr) { fr.est = q.freight + q.sur; fr.vendor = q.vendor; } else f.costs.unshift({ type: "ct_freight", est: q.freight + q.sur, cur: q.cur, vendor: q.vendor }); f.history.push([TODAY, me().id, L("Cost chosen: " + q.ref, "التكلفة اتعتمدت: " + q.ref)]); saveState(); render(); };
HANDLERS.confirmq = a => { DB.vendorQuotes[a.dataset.q].confirmed = true; const m = DB.inbound.find(x => x.quote === a.dataset.q); if (m) m.status = "confirmed"; saveState(); toast(t("saved")); render(); };
HANDLERS.toreview = a => { const f = file(a.dataset.file); f.pricing = "review"; f.history.push([TODAY, me().id, L("Sent for review", "اتبعت للمراجعة")]); saveState(); toast(t("saved")); render(); };
HANDLERS.review_ok = a => { const f = file(a.dataset.file); f.pricing = "priced"; f.history.push([TODAY, me().id, L("Cost approved", "التكلفة اتوافق عليها")]); saveState(); toast(t("reviewed_ok")); render(); };
HANDLERS.review_back = a => { const f = file(a.dataset.file); f.pricing = "pending"; f.history.push([TODAY, me().id, L("Returned to pricing", "رجع للتسعير")]); saveState(); render(); };
HANDLERS.ptok = a => { const f = file(a.dataset.file); f.pay_terms = { ...(f.pay_terms || { method: f.inq.pay }), status: "approved" }; saveState(); toast(t("pt_approved")); render(); };
HANDLERS.rfqvendors = a => {
  const f = file(a.dataset.file);
  openModal(`<div class="modal-header"><h5 class="modal-title">${t("send_rfq_vendors")}</h5></div><div class="modal-body">
    <div class="small-label mb-2">${t("subject")}: <span class="mono">${f.ref}-OPS</span></div>
    ${DB.vendors.filter(v => ["agent", "line", "air", "cfs"].includes(v.type)).map(v => `<div class="form-check"><input class="form-check-input" type="checkbox" id="rv-${v.id}" ${["v1", "v3"].includes(v.id) ? "checked" : ""}><label class="form-check-label" for="rv-${v.id}">${esc(v.name)} <span class="small-label">· ${esc(tx(v.country))}</span></label></div>`).join("")}
    <div class="ai-box mt-3 small">${t("hidden_pricing")}</div></div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">${t("cancel")}</button><button class="btn btn-primary" data-act="rfqsent" data-file="${f.id}">${t("send")}</button></div>`);
};
HANDLERS.rfqsent = a => { const f = file(a.dataset.file); f.checks.c2_rates = true; if (f.pricing === "draft") f.pricing = "pending"; f.history.push([TODAY, me().id, L("RFQ emailed to vendors", "الطلب اتبعت للموردين")]); closeModal(); saveState(); toast(t("saved")); setTimeout(render, 200); };

FILE_TABS.quote = f => {
  const cost = costTotalUSD(f), sell = sellTotalUSD(f), m = sell - cost, min = DB.settings.minMargin;
  const canEdit = (isAdmin() || ROLE === "sales") && f.status === "open" && f.stage <= 2;
  const showCost = ROLE !== "customs" && ROLE !== "ops";
  const lines = `<div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>${t("line")}</th><th>${t("selling")}</th><th>${t("currency")}</th><th class="text-end">USD</th></tr></thead><tbody>
    ${f.sell.map((l, i) => `<tr><td>${esc(tx(l.line))}</td><td style="max-width:140px">${canEdit ? `<input type="number" min="0" class="form-control form-control-sm num" value="${l.amt}" data-change="sellamt" data-file="${f.id}" data-i="${i}" aria-label="${t("selling")}">` : `<span class="num">${NF.format(l.amt)}</span>`}</td><td>${l.cur}</td><td class="text-end">${money(toUSD(l.amt, l.cur))}</td></tr>`).join("") || `<tr><td colspan="4" class="empty">—</td></tr>`}
    </tbody></table></div>`;
  const summary = showCost ? `<dl class="dl mb-3"><dt>${t("selling")}</dt><dd class="fw-semibold">${money(sell)}</dd><dt>${t("cost")}</dt><dd>${money(cost)}</dd><dt>${t("margin")}</dt><dd class="fw-bold ${m < min ? "text-danger" : "text-success"}">${money(m)}</dd></dl>
    <div class="small-label mb-2">${t("min_margin", { amt: money(min) })}</div>${f.sell.length ? (m < min ? `<div class="gate-box bad">${t("margin_low")}</div>` : `<div class="gate-box ok">${t("margin_ok")}</div>`) : ""}` : `<div class="locked">${t("hidden_for_role")}</div>`;
  const rounds = `<div class="tbl-wrap"><table class="table"><thead><tr><th>${t("round")}</th><th>${t("our_price")}</th><th>${t("counter")}</th><th>${t("outcome")}</th></tr></thead><tbody>${f.rounds.map((r, i) => `<tr><td class="num">${i + 1}</td><td>${money(r.our)}</td><td>${money(r.counter)}</td><td class="small">${esc(tx(r.outcome))}</td></tr>`).join("") || `<tr><td colspan="4" class="empty">—</td></tr>`}</tbody></table></div><div class="small-label p-3">${t("max_rounds")}</div>`;
  const acts = canEdit && f.sell.length ? `<button class="btn btn-sm btn-light" data-act="quotepdf" data-file="${f.id}" data-l="en">${t("pdf_en")}</button> <button class="btn btn-sm btn-light" data-act="quotepdf" data-file="${f.id}" data-l="ar">${t("pdf_ar")}</button>` : "";
  return `<div class="grid g-main"><div class="stack">${panel(t("selling"), lines, acts, true)}${panel(t("rounds"), rounds, canEdit ? `<button class="btn btn-sm btn-light" data-act="asklower" data-file="${f.id}">${t("ask_lower")}</button>` : "", true)}</div>${panel(t("margin"), summary)}</div>`;
};
HANDLERS.sellamt = el => { const f = file(el.dataset.file); f.sell[+el.dataset.i].amt = +el.value || 0; saveState(); render(); };
HANDLERS.asklower = a => { const f = file(a.dataset.file); f.pricing = "pending"; f.history.push([TODAY, me().id, L("Sales asked pricing for a lower cost", "المبيعات طلبت تكلفة أقل من التسعير")]); saveState(); toast(t("saved")); render(); };
HANDLERS.quotepdf = a => {
  const f = file(a.dataset.file), c = client(f.client), l = a.dataset.l, prev = LANG; LANG = l;
  const html = `<div class="paper" dir="${l === "ar" ? "rtl" : "ltr"}"><div class="d-flex justify-content-between mb-3"><div><div class="t fw-bold fs-5">Paradigm Cargo</div><div class="small">${t("app_name")}</div></div><div class="text-end small mono">${f.ref}<br>${fdate(TODAY)}</div></div>
    <div class="mb-3"><strong>${t("client")}:</strong> ${esc(tx(c.company))} · ${esc(tx(c.contact))}</div>
    <div class="small mb-3">${esc(t(SVC_KEY[f.service]))} · ${esc(port(f.origin))} → ${esc(port(f.dest))} · ${f.incoterm} · ${esc(volumeSummary(f))}</div>
    <table><thead><tr><th>${t("line")}</th><th>${t("amount")}</th></tr></thead><tbody>${f.sell.map(x => `<tr><td>${esc(tx(x.line))}</td><td class="num">${NF.format(x.amt)} ${x.cur}</td></tr>`).join("")}</tbody></table>
    <div class="small mt-3">${t("etd")}: ${fdate(f.ship?.etd)} · ${t("tt")}: ${f.chosen ? DB.vendorQuotes[f.chosen].tt + " " + t("days") : "—"} · ${t("validity")}: ${f.chosen ? fdate(DB.vendorQuotes[f.chosen].valid) : "—"}</div></div>`;
  LANG = prev;
  openModal(`<div class="modal-header"><h5 class="modal-title">${t("quote_pdf")} · ${l.toUpperCase()}</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="${t("close")}"></button></div><div class="modal-body" style="background:var(--surface-2)">${html}</div>`, "modal-lg");
};

FILE_TABS.shipping = f => {
  const s = f.ship || (f.ship = {}), edit = (isAdmin() || ROLE === "ops") && f.status === "open";
  const fld = (k, lbl, type = "text") => `<div><label class="form-label" for="sh-${k}">${t(lbl)}</label><input id="sh-${k}" type="${type}" class="form-control form-control-sm ${type === "text" ? "ltr" : ""}" value="${esc(s[k] || "")}" ${edit ? "" : "disabled"} data-change="shipfld" data-file="${f.id}" data-k="${k}"></div>`;
  return `<div class="grid g-main">${panel(t("booking"), `<div class="grid g-2">${fld("carrier", "carrier")}${fld("booking", "booking_no")}${fld("vessel", "vessel")}${fld("bl", "bl")}${fld("etd", "etd", "date")}${fld("atd", "gate_need_etd", "date")}${fld("eta", "eta", "date")}<div><label class="form-label" for="sh-acid">${t("acid")}</label><input id="sh-acid" class="form-control form-control-sm mono" value="${esc(f.acid || "")}" ${edit ? "" : "disabled"} data-change="acidfld" data-file="${f.id}"></div></div>
    ${s.etd && f.chosen ? `<div class="small-label mt-3">${t("eta")} = ${t("etd")} + ${t("tt")} (${DB.vendorQuotes[f.chosen].tt} ${t("days")})</div>` : ""}`)}
    ${panel(t("client_updates"), `<div class="tl-item"><span class="tl-dot"></span><div><div>${t("prealert")} · ${t("bl")} ${esc(s.bl || "—")}</div><div class="small-label">${fdate(s.atd)}</div></div></div>`, (isAdmin() || ROLE === "ops" || ROLE === "customs") ? `<button class="btn btn-sm btn-light" data-act="sendupdate" data-file="${f.id}">${t("send_update")}</button>` : "")}</div>`;
};
HANDLERS.shipfld = el => { const f = file(el.dataset.file); f.ship[el.dataset.k] = el.value; saveState(); };
HANDLERS.acidfld = el => { const f = file(el.dataset.file); f.acid = el.value; saveState(); };
HANDLERS.sendupdate = a => { const f = file(a.dataset.file); f.history.push([TODAY, me().id, L("Update sent to client", "تحديث اتبعت للعميل")]); saveState(); toast(t("saved")); };

FILE_TABS.docs = f => {
  const canUp = isAdmin() || ROLE === "ops" || ROLE === "customs";
  const canDismiss = canUp;
  const list = `<div class="tbl-wrap"><table class="table align-middle"><thead><tr><th>${t("doc_type")}</th><th>${t("file_no")}</th><th>${t("uploaded_by")}</th><th>${t("date")}</th></tr></thead><tbody>
    ${f.docs.map(d => `<tr><td>${t(d.type)}</td><td class="mono small">${esc(d.name)}</td><td>${avatar(d.by)}</td><td class="num">${fdate(d.at)}</td></tr>`).join("") || `<tr><td colspan="4" class="empty">—</td></tr>`}</tbody></table></div>
    ${canUp ? `<div class="p-3 border-top d-flex gap-2 flex-wrap align-items-end"><div><label class="form-label" for="up-type">${t("doc_type")}</label><select id="up-type" class="form-select form-select-sm">${["dt_ci", "dt_pl", "dt_co", "dt_bl", "dt_awb", "dt_msds", "dt_other"].map(k => `<option value="${k}">${t(k)}</option>`).join("")}</select></div>
      <div style="min-width:0;flex:1 1 200px"><label class="form-label" for="up-file">${t("upload")}</label><input id="up-file" type="file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.xlsx" data-change="upload" data-file="${f.id}"></div></div>` : ""}`;
  const issues = f.dc.length ? f.dc.map(i => `<div class="issue"><span class="sev ${i.sev}"></span><div class="flex-grow-1" style="min-width:0">
      <div class="split"><span>${pill(t("sev_" + i.sev), i.sev === "blocker" ? "bad" : i.sev === "warning" ? "warn" : "info")} <span class="small-label">${t(i.doc)}</span></span>
      ${i.status === "open" && canDismiss && i.sev !== "info" ? `<button class="btn btn-sm btn-light" data-act="dismiss" data-file="${f.id}" data-i="${i.id}">${t("dismiss")}</button>` : i.status !== "open" ? pill(t(i.status), "ok") : ""}</div>
      <div class="mt-1">${esc(tx(i.text))}</div>${i.reason ? `<div class="small-label mt-1">${t("dismiss_reason")} ${esc(i.reason)} · ${esc(uname(i.by))}</div>` : ""}</div></div>`).join("") : `<div class="empty">—</div>`;
  const aiOn = DB.settings.ai.f3;
  return `<div class="grid g-main">${panel(t("docs_title"), list, "", true)}
    ${panel(t("dc_title") + " " + pill(t("ai_suggests"), "info"), `${f.dc_at ? `<div class="small-label mb-2">${t("dc_last", { when: fdate(f.dc_at) })}</div>` : ""}${issues}<div class="small-label mt-3">${t("dc_blocker_note")}</div>`, aiOn && canUp && f.docs.length ? `<button class="btn btn-sm btn-light" data-act="recheck" data-file="${f.id}">${ico("spark")} ${t("dc_run")}</button>` : "")}</div>`;
};
HANDLERS.upload = el => {
  const f = file(el.dataset.file), fl = el.files[0]; if (!fl) return;
  f.docs.push({ type: $("#up-type").value, name: fl.name, by: me().id, at: TODAY }); f.history.push([TODAY, me().id, L("Uploaded " + fl.name, "رفع " + fl.name)]); saveState(); toast(t("saved")); render();
};
HANDLERS.dismiss = a => {
  openModal(`<form data-submit="dismisssave" data-file="${a.dataset.file}" data-i="${a.dataset.i}"><div class="modal-header"><h5 class="modal-title">${t("dismiss")}</h5></div><div class="modal-body"><label class="form-label" for="dis-r">${t("dismiss_reason")}</label><textarea id="dis-r" class="form-control" rows="3"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">${t("cancel")}</button><button class="btn btn-primary">${t("confirm")}</button></div></form>`);
};
HANDLERS.dismisssave = form => {
  const r = $("#dis-r").value.trim(); if (!r) { $("#dis-r").classList.add("is-invalid"); return; }
  const f = file(form.dataset.file), i = f.dc.find(x => x.id === form.dataset.i); i.status = "dismissed"; i.reason = r; i.by = me().id;
  f.history.push([TODAY, me().id, L("Document issue dismissed: " + r, "مشكلة مستند اتجاهلت: " + r)]); closeModal(); saveState(); setTimeout(render, 200);
};
HANDLERS.recheck = a => {
  const f = file(a.dataset.file); a.disabled = true; a.textContent = t("wa_running");
  setTimeout(() => { const hasPl2 = f.docs.filter(d => d.type === "dt_pl").length > 1; f.dc.forEach(i => { if (i.sev === "blocker" && i.status === "open" && hasPl2) i.status = "fixed"; }); f.dc_at = TODAY + " " + new Date().toTimeString().slice(0, 5); DB.aiRuns.unshift({ at: f.dc_at, f: "ai_f3", user: me().id, file: f.id, tokens: 19800, cost: 0.1, status: "rs_done" }); DB.settings.ai.spent += 0.1; saveState(); render(); }, 900);
};

FILE_TABS.money = f => {
  const invs = DB.invoices.filter(i => i.file === f.id), inv = invs.reduce((s, i) => s + toUSD(invTotal(i, true), i.cur), 0), act = costTotalUSD(f, "act");
  return `<div class="grid g-main">${panel(t("invoices_title"), invoiceTable(invs), "", true)}
    ${panel(t("actual_profit"), canSeeMoney() ? `<dl class="dl"><dt>${t("invoiced")}</dt><dd>${money(inv)}</dd><dt>${t("actual_costs")}</dt><dd>${money(act)}</dd><dt>${t("actual_profit")}</dt><dd class="fw-bold ${inv - act >= 0 ? "text-success" : "text-danger"}">${invs.length ? money(inv - act) : "—"}</dd></dl><div class="small-label mt-2">${t("subtotal")} · USD</div>` : `<div class="locked">${t("hidden_for_role")}</div>`)}</div>`;
};

FILE_TABS.history = f => {
  const mention = s => esc(s).replace(/@([\p{L}]+)/gu, '<span class="mention">@$1</span>');
  return `<div class="grid g-2">${panel(t("t_history"), [...f.history].reverse().map(h => `<div class="tl-item"><span class="tl-dot"></span><div><div>${esc(tx(h[2]))}</div><div class="small-label">${fdate(h[0])} · ${esc(uname(h[1]))}</div></div></div>`).join(""))}
    ${panel(t("notes"), f.notes.map(n => `<div class="tl-item">${avatar(n[1])}<div><div>${mention(tx(n[2]))}</div><div class="small-label">${fdate(n[0])}</div></div></div>`).join("") + `<form data-submit="addnote" data-file="${f.id}" class="mt-3"><label class="form-label" for="note-t">${t("notes")} (@)</label><textarea id="note-t" class="form-control" rows="2"></textarea><button class="btn btn-sm btn-primary mt-2">${t("add")}</button></form>`)}</div>`;
};
HANDLERS.addnote = form => { const v = $("#note-t").value.trim(); if (!v) return; const f = file(form.dataset.file); f.notes.push([TODAY, me().id, L(v, v)]); saveState(); render(); };
