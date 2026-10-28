# Operations guide

All commands run from `~/crm` — in Terminal if the host has it, otherwise as a one-time cron job (DEPLOY.md §6). If PHP is not the default, use the full path (e.g. `/opt/cpanel/ea-php83/root/usr/bin/php`).

## Users and roles
- New staff sign up at `/register` with their `@paradigmcargo.com` email (other domains are refused). They have no roles until management assigns them.
- **Users** page (management only): roles (more than one allowed), active/inactive, sales target, commission %, "receives website leads".
- Roles: `admin` (management: everything, all departments, every action logged), `sales`, `pricing`, `pricing_review`, `ops`, `customs`, `accounts`.
- Deactivating a user signs them out at their next request. Their clients stay; move them by editing the client owner.
- You cannot remove your own admin role (prevents locking everyone out).
- Forgotten password: "Forgot password" on the login page (needs working SMTP).

## Cron, queue and "restarting"
- Everything runs from the single cron line `php artisan schedule:run` (see DEPLOY.md). List: `php artisan schedule:list`.
- Emails/push/AI sit in the `jobs` table until the next minute. Stuck? Check: `php artisan queue:failed`; retry all: `php artisan queue:retry all`.
- After changing `.env`: `php artisan config:cache` then `php artisan queue:restart`.
- The *Outbox* page (management) shows every email with status; failed ones can be resent.

## Backups and restore
- Daily 02:30 Cairo: `crm:backup` writes `storage/app/backups/db-YYYYMMDD-HHMMSS.sql.gz`, keeps 14 days (`--keep=N`). Run any time: `php artisan crm:backup`.
- Uploaded documents are in `storage/app/private` — include `~/crm/storage` in cPanel's backup (cPanel *Backup* / JetBackup covers the home folder).
- Download a copy off the server weekly.
- **Restore:**
  ```bash
  php artisan down
  gunzip -c storage/app/backups/db-20261102-023000.sql.gz | mysql -u DB_USER -p DB_NAME
  php artisan migrate --force && php artisan optimize:clear && php artisan up
  ```

## AI (OmniRoute)
- Connection in `.env`: `AI_BASE_URL` (ending in `/v1`), `AI_API_KEY`, `AI_MODEL`, optional `AI_VISION_MODEL`, `AI_TRANSCRIBE_MODEL`.
- **Switch model (e.g. Gemini → Claude):** change `AI_MODEL` (and `AI_VISION_MODEL` if set) to the model name OmniRoute expects, then `php artisan config:cache && php artisan queue:restart`. No code change. Update `AI_PRICE_*` so the cost tracking stays right.
- **Turn features on/off:** *Settings → AI*: WhatsApp → RFQ, vendor rate emails, document check, and the monthly USD limit. When the limit is reached AI stops until next month; work continues by hand.
- **Usage:** *AI usage* page — every run with feature, model, tokens, cost, result, and who confirmed it.
- Prompts are versioned files in `resources/prompts/*.v1.md`. To change one, add `*.v2.md` and change the `VERSION` constant in that feature class (`app/Ai/Features/…`) (keeps history comparable).
- Document check: "blocker" findings stop stage 4 → 5 until fixed or dismissed with a written reason by ops/customs/management.
- Rate emails: set `RATES_MAILBOX_ENABLED=true` and the IMAP values (Google Workspace: IMAP on + app password). Checked every 5 minutes; each email becomes a suggested vendor quote that pricing confirms.

## Website lead form
- *Settings → Lead form keys* → create key → copy it once (only a hash is stored).
- The website posts JSON to `https://crm.paradigmcargo.com/api/leads` with header `X-Lead-Key: <key>`:
  ```json
  {"company":"…","contact_person":"…","phone":"…","email":"…","message":"…","source":"website"}
  ```
  `company` required; `phone` or `email` required. Limited to 20 requests/minute per IP. Keep the key in the website's server code, never in browser JavaScript.
- Leads rotate between active sales users with "receives website leads". Unanswered after `lead_sla_hours` (Settings) → management is alerted.

## Exchange rates
Refreshed daily 08:00 from the source URL in Settings; a rate marked **locked** is not overwritten. Manual refresh on the Settings page.

## Troubleshooting
| Symptom | Look at |
|---|---|
| 500 error page | `storage/logs/laravel-*.log` |
| Emails not arriving | *Outbox* status/error; SMTP values; cron running? |
| No push notifications | VAPID keys set? browser permission? site on https? |
| AI button says "not configured" | `AI_BASE_URL`/`AI_API_KEY` set, `config:cache` run, feature on in Settings, monthly limit not reached |
| Changes in `.env` ignored | `php artisan config:cache` |
| Page looks unstyled | `public/build` deployed? Subdomain root is `crm/public`? |
