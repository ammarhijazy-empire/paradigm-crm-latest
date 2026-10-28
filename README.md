# Paradigm Cargo CRM

Internal CRM for Paradigm Cargo, Cairo. It replaces the old Supabase CRM.
Laravel 13 · PHP 8.3 · MySQL 8 / MariaDB 10.6+ · Bootstrap 5 (Arabic RTL + English) · runs on cPanel shared hosting.

| What | Where |
|---|---|
| Install on cPanel, update, cron, `.env` | [docs/DEPLOY.md](docs/DEPLOY.md) |
| Daily admin: users & roles, backups, AI, lead form, troubleshooting | [docs/OPERATIONS.md](docs/OPERATIONS.md) |
| Moving data from the old Supabase CRM | [docs/MIGRATION.md](docs/MIGRATION.md) |
| What the system does, by department | [docs/FEATURES.md](docs/FEATURES.md) |
| Known limits and things left for later | [docs/NOTES.md](docs/NOTES.md) |
| Clickable screen prototype (no backend) | [prototype/](prototype/) |

## Ingredients at a glance
- **7-stage shipment file** (Request → Pricing → Quotation → Booking → Shipping & customs → Delivery → Closing) with required ★ checks per stage, stage history and gates enforced on the server.
- **Roles:** management (`admin`, can do everything, every action logged), sales, pricing, pricing review, operations, customs, accounts. Sales see only their own clients; pricing never sees client contact details.
- **Pricing path:** RFQ to vendors → vendor quotes ranked → cost sheet → pricing review → quotation rounds (max 3) with minimum-margin approval → payment terms → reprice requests.
- **Money:** invoices with VAT, payments, collections, overdue alerts, Arabic/English PDF.
- **Leads:** website form API (`POST /api/leads`), round-robin assignment, response-time alerts.
- **Notifications:** in-app + browser push + email (queued outbox).
- **AI (through OmniRoute, OpenAI-compatible):** WhatsApp chat / voice note → RFQ form, vendor rate emails → ranked quotes, document check before customs. AI only suggests; a person confirms. Model is changed in `.env` only.

## Local development
```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# set DB_* (or DB_CONNECTION=sqlite), then:
php artisan migrate --seed
php artisan db:seed --class=Database\\Seeders\\DemoSeeder   # optional demo data (never on production)
npm run build          # or: npm run dev
php artisan serve
php artisan test       # 90 tests
```
Translations live in one source file: `tools/translations.py` → `python3 tools/translations.py` writes `lang/ar/*` and `lang/en/*`; `php tools/check_keys.php` finds missing keys.

## Security rules
- Secrets (DB password, SMTP, VAPID private key, AI key, IMAP password, Supabase keys, `APP_KEY`) live **only** in the server `.env`. Only `.env.example` is in git.
- Website lead keys are created in **Settings** and stored hashed.
- Uploaded documents are on a private disk and served only through permission checks.
