import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

// ---------- Shell ----------
document.addEventListener('click', (e) => {
    if (e.target.closest('#menu-btn')) { $('#rail')?.classList.toggle('open'); $('#scrim').hidden = !$('#rail').classList.contains('open'); }
    if (e.target.closest('#scrim')) { $('#rail').classList.remove('open'); $('#scrim').hidden = true; }
    const c = e.target.closest('[data-confirm]');
    if (c && !window.confirm(c.dataset.confirm)) e.preventDefault();
});
document.addEventListener('change', (e) => {
    const el = e.target.closest('[data-autosubmit]');
    if (el && el.form) el.form.requestSubmit ? el.form.requestSubmit() : el.form.submit();
});

// ---------- Repeating rows (cost lines, quote lines) ----------
$$('[data-rows]').forEach((form) => {
    const body = $('[data-rows-body]', form), tpl = $('[data-rows-template]', form);
    let next = $$('[data-row]', body).length;
    const add = (vals) => {
        const html = tpl.innerHTML.replaceAll('__I__', String(next++));
        const wrap = document.createElement('div'); wrap.innerHTML = html.trim();
        const row = wrap.firstElementChild; body.appendChild(row);
        if (vals) Object.entries(vals).forEach(([k, v]) => { const f = $(`[data-f="${k}"]`, row); if (f) f.value = v; });
        return row;
    };
    $('[data-rows-add]', form)?.addEventListener('click', () => add());
    form.addEventListener('click', (e) => { if (e.target.closest('[data-rows-remove]')) e.target.closest('[data-row]').remove(); });
    $('[data-fill-from-costs]', form)?.addEventListener('click', (e) => {
        JSON.parse(e.currentTarget.dataset.fillFromCosts).forEach((c) => add({ section: c.section, currency: c.currency, amount: c.amount, en: c.en, ar: c.ar }));
    });
    if (!$$('[data-row]', body).length && form.dataset.rows === 'quote') add();
});

// ---------- AI run polling (document check) ----------
$$('[data-poll-reload]').forEach((el) => {
    const t = setInterval(async () => {
        try {
            const r = await fetch(el.dataset.pollReload, { headers: { Accept: 'application/json' } });
            const j = await r.json();
            if (!['queued', 'running'].includes(j.status)) { clearInterval(t); location.reload(); }
        } catch { clearInterval(t); }
    }, 4000);
});

// ---------- Client duplicate check ----------
const dupBox = $('#dup-box');
if (dupBox) {
    const check = async () => {
        const p = $('#phone')?.value || '', m = $('#email')?.value || '';
        if (p.length < 6 && m.length < 5) return;
        const u = new URL(dupBox.dataset.url, location.origin); u.searchParams.set('phone', p); u.searchParams.set('email', m);
        if (dupBox.dataset.ignore) u.searchParams.set('ignore', dupBox.dataset.ignore);
        const r = await fetch(u, { headers: { Accept: 'application/json' } }); const list = await r.json();
        dupBox.hidden = !list.length;
        dupBox.innerHTML = list.map((c) => `<div>${esc(dupBox.dataset.label)}: <strong>${esc(c.company)}</strong> · ${esc(c.owner || '')}</div>`).join('');
    };
    $('#phone')?.addEventListener('change', check); $('#email')?.addEventListener('change', check);
}

// ---------- Phone push ----------
const pushBtn = $('#push-enable');
if (pushBtn && 'serviceWorker' in navigator && 'PushManager' in window) {
    const key = document.querySelector('meta[name="vapid-key"]')?.content;
    if (!key) pushBtn.disabled = true;
    pushBtn.addEventListener('click', async () => {
        try {
            const reg = await navigator.serviceWorker.register('/sw.js');
            const perm = await Notification.requestPermission();
            if (perm !== 'granted') throw new Error('denied');
            const raw = atob(key.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4 - key.length % 4) % 4));
            const sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: Uint8Array.from([...raw].map((c) => c.charCodeAt(0))) });
            await fetch(pushBtn.dataset.url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, body: JSON.stringify(sub.toJSON()) });
            pushBtn.textContent = pushBtn.dataset.done; pushBtn.disabled = true;
        } catch { pushBtn.textContent = pushBtn.dataset.fail; }
    });
}

// ---------- Quote request form ----------
const form = $('#rfq-form');
if (form) rfqForm(form);

function rfqForm(form) {
    const T = JSON.parse(form.dataset.i18n);
    const today = form.dataset.today;
    let rows = JSON.parse(form.dataset.rows || '[]');
    const SEA = ['fcl', 'lcl', 'bulk', 'roro', 'se'];
    const CONT = ["20'GP", "40'GP", "40'HC", "45'HC", "20'RF", "40'RF"];
    const OTHER_INCO = ['EXW', 'FCA', 'CPT', 'CIP', 'DAP', 'DPU', 'DDP'];
    const service = () => $('input[name="service"]:checked', form)?.value || '';
    const addons = () => $$('input[name="addons[]"]:checked', form).map((x) => x.value);
    const val = (id) => $('#' + id, form)?.value?.trim() || '';

    const blankRow = (s) => s === 'fcl' ? { type: "40'HC", count: 1 } : s === 'se' ? { type: 'Flat Rack', count: 1 } : s === 'land' ? { truck: 'FTL', count: 1, kg: '' } : s === 'bulk' ? { tonnes: '' } : s === 'roro' ? { count: 1, l: '', w: '', h: '', kg: '' } : { pieces: '', l: '', w: '', h: '', kg: '' };
    const fieldsFor = (s) => ({ fcl: [['type', CONT], 'count'], se: [['type', ['Flat Rack', 'Open Top']], 'count'], lcl: ['pieces', 'l', 'w', 'h', 'kg'], air: ['pieces', 'l', 'w', 'h', 'kg'], courier: ['pieces', 'l', 'w', 'h', 'kg'], land: [['truck', ['FTL', 'LTL']], 'count', 'kg'], bulk: ['tonnes'], roro: ['count', 'l', 'w', 'h', 'kg'] }[s] || []);
    const n = (x) => parseFloat(x) || 0;
    const chargeable = (s, r) => {
        const vol = n(r.l) * n(r.w) * n(r.h) * Math.max(1, n(r.pieces));
        if (s === 'lcl') return [Math.max(vol / 1e6, n(r.kg) / 1000), 'W/M', vol / 1e6];
        if (s === 'air') return [Math.max(n(r.kg), vol / 6000), 'kg'];
        if (s === 'courier') return [Math.ceil(Math.max(n(r.kg), vol / 5000) * 2) / 2, 'kg'];
        return null;
    };

    function renderRows() {
        const s = service(), box = $('#rows', form);
        $('#row-add', form).hidden = !s; $('#rows-hint', form).hidden = !!s;
        box.innerHTML = s ? rows.map((r, i) => {
            const cells = fieldsFor(s).map((f) => {
                const [k, opts] = Array.isArray(f) ? f : [f, null];
                const name = `inq[rows][${i}][${k}]`, id = `r${i}-${k}`;
                return `<div><label class="form-label" for="${id}">${esc(T[k])}</label>` + (opts
                    ? `<select id="${id}" name="${name}" dir="ltr" class="form-select form-select-sm" data-k="${k}" data-i="${i}">${opts.map((o) => `<option ${r[k] === o ? 'selected' : ''}>${esc(o)}</option>`).join('')}</select>`
                    : `<input id="${id}" name="${name}" type="number" min="0" step="any" class="form-control form-control-sm num" value="${esc(r[k] ?? '')}" data-k="${k}" data-i="${i}">`) + '</div>';
            }).join('');
            const c = chargeable(s, r);
            const ch = c ? `<div><div class="form-label">${esc(T.chargeable)}</div><div class="fw-semibold num ltr pt-1">${c[0] ? c[0].toFixed(s === 'lcl' ? 3 : 1) + ' ' + c[1] + (s === 'lcl' ? ` · ${c[2].toFixed(3)} ${esc(T.cbm)}` : '') : '—'}</div></div>` : '';
            return `<div class="row-line">${cells}${ch}<div><button type="button" class="btn btn-sm btn-light w-100" data-del="${i}">${esc(T.remove)}</button></div></div>`;
        }).join('') : '';
    }

    function applyVisibility() {
        const s = service(), a = addons(), inco = val('incoterm'), cargo = val('inq-cargo'), pay = val('inq-pay');
        const show = (k, on) => $$(`[data-show="${k}"]`, form).forEach((el) => { el.hidden = !on; });
        show('pickup', inco === 'EXW' || s === 'courier' || (a.includes('trucking') && $('#dir-export')?.checked));
        show('delivery', a.includes('trucking') || s === 'courier');
        show('haz', cargo === 'hazardous'); show('reefer', cargo === 'reefer'); show('oog', cargo === 'oog');
        show('free', s === 'fcl' || s === 'se'); show('credit', pay === 'credit');
        // Incoterms: all 11 for sea, 7 for other modes
        $$('#incoterm option[data-other]', form).forEach((o) => { o.hidden = s && !SEA.includes(s) && o.dataset.other === '0'; });
        if (s && !SEA.includes(s) && inco && !OTHER_INCO.includes(inco)) $('#incoterm', form).value = '';
        // Places by mode
        const kinds = !s ? null : ['air', 'courier'].includes(s) ? ['air', 'city'] : s === 'land' ? ['city'] : ['sea'];
        $$('select[data-ports] option[data-kind]', form).forEach((o) => { o.hidden = kinds && !kinds.includes(o.dataset.kind) && !o.selected; });
    }

    // Mirrors App\Domain\InquiryValidator::problems()
    function problems() {
        const p = [], s = service(), a = addons();
        if (!val('client_id') && !$('input[type=hidden][name=client_id]', form)) p.push(T.p_client);
        if (!s) p.push(a.length === 1 && a[0] === 'insurance' ? T.p_ins_alone : T.p_service);
        if (!rows.length) p.push(T.p_rows);
        rows.forEach((r, i) => {
            const pos = (k) => n(r[k]) > 0;
            const ok = { fcl: CONT.includes(r.type) && pos('count'), se: ['Flat Rack', 'Open Top'].includes(r.type) && pos('count'), lcl: pos('pieces') && pos('l') && pos('w') && pos('h') && pos('kg'), land: ['FTL', 'LTL'].includes(r.truck) && pos('count') && pos('kg'), bulk: pos('tonnes'), roro: pos('count') && pos('l') && pos('w') && pos('h') && pos('kg') };
            ok.air = ok.courier = ok.lcl;
            if (s && !ok[s]) p.push(T.p_row_incomplete.replace(':n', i + 1));
        });
        if (!val('origin')) p.push(T.p_origin);
        if (!val('destination')) p.push(T.p_dest);
        const inco = val('incoterm');
        if (!inco || (s && !SEA.includes(s) && !OTHER_INCO.includes(inco))) p.push(T.p_incoterm);
        if (inco === 'EXW' && !val('pickup_address')) p.push(T.p_exw);
        if (a.includes('trucking') && !val('delivery_address')) p.push(T.p_delivery);
        if (s === 'courier' && (!val('pickup_address') || !val('delivery_address'))) p.push(T.p_courier_addr);
        const ready = val('inq-ready');
        if (!ready) p.push(T.p_ready); else if (ready < today && !form.querySelector('input[name=_method]')) p.push(T.p_ready_past);
        const cargo = val('inq-cargo');
        if (cargo === 'hazardous' && (!val('inq-un') || !val('inq-imo') || !val('inq-pg') || (!$('#msds', form).files.length && !val('msds-doc')))) p.push(T.p_haz);
        if (cargo === 'reefer' && val('inq-temp') === '') p.push(T.p_temp);
        if (cargo === 'oog' && (!val('oog-l') || !val('oog-w') || !val('oog-h'))) p.push(T.p_oog);
        if ((s === 'fcl' || s === 'se') && !val('inq-free_days')) p.push(T.p_free);
        if (!val('inq-freight_terms')) p.push(T.p_freight);
        const pay = val('inq-pay');
        if (!pay) p.push(T.p_pay); else if (pay === 'credit' && !val('inq-credit_days')) p.push(T.p_credit);
        return p;
    }

    function refresh() {
        applyVisibility();
        const p = problems(), aiPending = !!val('ai_run_id') && !$('#ai_checked', form).checked;
        $('#prob-count').textContent = p.length; $('#prob-count').className = 'pill ' + (p.length ? 'bad' : 'ok');
        $('#problems').innerHTML = p.length ? `<ul class="small ps-3 mb-0">${p.map((x) => `<li class="mb-1">${esc(x)}</li>`).join('')}</ul>` : `<div class="gate-box ok">${esc(T.ok)}</div>`;
        if (aiPending) $('#problems').innerHTML += `<div class="gate-box bad mt-2">${esc(T.confirm_ai)}</div>`;
        const send = $('#btn-send'); if (send) send.disabled = !!p.length || aiPending;
    }

    form.addEventListener('change', (e) => {
        const t = e.target;
        if (t.name === 'service') { rows = [blankRow(t.value)]; renderRows(); }
        if (t.dataset.i !== undefined) { rows[+t.dataset.i][t.dataset.k] = t.tagName === 'SELECT' ? t.value : t.value; renderRows(); }
        if (t.id === 'ai_checked') $$('.ai-field', form).forEach((el) => el.classList.toggle('ai-field', !t.checked));
        refresh();
    });
    form.addEventListener('input', (e) => { if (e.target.dataset.i !== undefined) rows[+e.target.dataset.i][e.target.dataset.k] = e.target.value; refresh(); });
    form.addEventListener('click', (e) => {
        const d = e.target.closest('[data-del]'); if (d) { rows.splice(+d.dataset.del, 1); renderRows(); refresh(); }
    });
    $('#row-add', form).addEventListener('click', () => { rows.push(blankRow(service())); renderRows(); refresh(); });
    renderRows(); refresh();

    // ----- Fill from WhatsApp (AI) -----
    const wa = $('#wa-form');
    if (!wa) return;
    wa.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = $('#wa-run'), status = $('#wa-status');
        const fd = new FormData(wa); if (val('client_id')) fd.append('client_id', val('client_id'));
        btn.disabled = true; status.textContent = T.reading;
        try {
            const r = await fetch(wa.action, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' } });
            const j = await r.json(); if (!r.ok) throw new Error(j.message || T.failed);
            const out = await poll(j.status_url);
            fillFromAi(j.id, out);
            bootstrap.Modal.getInstance($('#m-wa'))?.hide();
        } catch (err) { status.textContent = err.message || T.failed; }
        finally { btn.disabled = false; }
    });
    async function poll(url) {
        for (let i = 0; i < 90; i++) {
            await new Promise((ok) => setTimeout(ok, 2000));
            const j = await (await fetch(url, { headers: { Accept: 'application/json' } })).json();
            if (j.status === 'done') return j.output;
            if (j.status === 'failed') throw new Error(j.error || T.failed);
        }
        throw new Error(T.failed);
    }
    function mark(key) { $$(`[data-ai="${key}"]`, form).forEach((el) => el.classList.add('ai-field')); }
    function setVal(id, v, key) { const el = $('#' + id, form); if (el && v != null && v !== '') { el.value = v; mark(key); } }
    function fillFromAi(runId, o) {
        $('#ai_run_id', form).value = runId; $('#ai_checked', form).checked = false;
        if (o.direction) { $('#dir-' + o.direction, form).checked = true; }
        if (o.service) { $('#svc-' + o.service, form).checked = true; mark('service'); }
        if (o.addons?.length) { o.addons.forEach((a) => { const c = $('#add-' + a, form); if (c) c.checked = true; }); mark('addons'); }
        if (o.rows?.length) { rows = o.rows; mark('rows'); } else if (o.service) rows = [blankRow(o.service)];
        setVal('origin', o.origin, 'origin'); setVal('destination', o.destination, 'destination'); setVal('incoterm', o.incoterm, 'incoterm');
        setVal('pickup_address', o.pickup_address, 'pickup_address'); setVal('delivery_address', o.delivery_address, 'delivery_address');
        const q = o.inq || {};
        setVal('inq-commodity', q.commodity, 'inq.commodity'); setVal('inq-cargo', q.cargo, 'inq.cargo'); setVal('inq-ready', q.ready, 'inq.ready');
        setVal('inq-price_by', q.price_by, 'inq.price_by'); setVal('inq-free_days', q.free_days, 'inq.free_days'); setVal('inq-freight_terms', q.freight_terms, 'inq.freight_terms');
        setVal('inq-pay', q.pay, 'inq.pay'); setVal('inq-credit_days', q.credit_days, 'inq.credit_days'); setVal('inq-notes', q.notes, 'inq.notes');
        renderRows(); if (o.rows?.length) mark('rows');
        $('#ai-box-wrap').hidden = false;
        $('#ai-box-title').textContent = T.filled.replace(':n', (o.filled || []).length);
        let body = '';
        if (!o.origin && o.origin_text) body += `<div>${esc(o.origin_text)} → ?</div>`;
        if (!o.destination && o.destination_text) body += `<div>→ ${esc(o.destination_text)} ?</div>`;
        (o.transcripts || []).forEach((t) => { body += `<div class="mb-1"><strong>${esc(T.transcript)} (${esc(t.name)}):</strong> ${esc(t.text)}</div>`; });
        if (o.still_to_ask?.length) body += `<strong>${esc(T.ask)}:</strong><ul class="mb-0">${o.still_to_ask.map((x) => `<li>${esc(x)}</li>`).join('')}</ul>`;
        $('#ai-box-body').innerHTML = body;
        refresh();
    }
}
