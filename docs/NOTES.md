# Notes, limits, and what's left

## Needs your values before go-live
- cPanel PHP version/extensions, database, cron (DEPLOY.md).
- SMTP: Google Workspace account + app password.
- OmniRoute URL + API key (AI stays off until set; everything works by hand without it).
- Company tax/commercial numbers, address and bank accounts (Settings) — they appear on invoices and quotations.
- Rates mailbox (IMAP) if you want vendor rate emails read automatically.
- Supabase connection details for the data import.

## Known limits
- **Shared hosting queue:** jobs run once a minute from cron, so emails/push/AI results can take up to ~1 minute.
- **Voice notes:** need a transcription model on OmniRoute (`AI_TRANSCRIBE_MODEL`). If OmniRoute has none, only text chats and screenshots work.
- **Scanned PDFs** without a text layer are sent whole to the vision model (Gemini reads PDFs; check the same works when you switch to another model through OmniRoute). Very large scans may hit the model's size limit — the check then fails and the person reviews by hand.
- **AI cost** is estimated from the `AI_PRICE_*` values in `.env`; update them when changing models.
- **Electronic invoice (ETA e-invoicing):** the invoice stores the e-invoice number (typed in by accounts) but is not submitted to the Egyptian Tax Authority's API automatically.
- **Backups:** `mysqldump` must be available to PHP on the host; if not, rely on cPanel backups.
- Tested with SQLite in development; production target MySQL 8 / MariaDB. Run the staging copy against the real MySQL before go-live.

## Ideas for later
- E-invoice submission to ETA.
- WhatsApp Business API (receive chats directly instead of pasting/uploading).
- Client portal for shipment tracking.
