# Paradigm Cargo CRM — front-end prototype

Clickable demo of every screen (Arabic RTL + English), Bootstrap 5.3.3, vanilla JS, sample data only.

- `src/app.css` — design tokens + component styles (becomes `resources/css/app.css`)
- `src/i18n.js` — every UI string in EN/AR (becomes `lang/en/*.php` + `lang/ar/*.php`)
- `src/data.js` — sample data (becomes seeders / factories)
- `src/core.js` — layout, roles, stage gates, RFQ check, total-in-words (gates move to `FileStageService`, RFQ check to `InquiryValidator`)
- `src/views-main.js`, `src/views-more.js` — screens (become Blade views)
- `build.py` — builds `dist/paradigm-crm.html` (one self-contained file)

Nothing here is the real backend: no saving to a database, no real permissions, no emails, no real AI calls.
