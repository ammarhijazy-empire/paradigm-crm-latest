# Deploying on cPanel

## 1. What the hosting needs
| Need | Where in cPanel |
|---|---|
| PHP **8.3** (8.4 fine) with `pdo_mysql, mbstring, intl, gd, zip, bcmath, fileinfo, openssl, curl, imap` (imap only for the rates mailbox) | *Select PHP Version* / *MultiPHP Manager* + *Extensions* |
| MySQL 8 or MariaDB 10.6+ database + user with ALL PRIVILEGES | *MySQL® Databases* |
| *Git™ Version Control* + *Cron Jobs* (Terminal **not** needed) | — |
| Composer — not needed: the deploy downloads `~/composer.phar` itself if the server has none (signature checked) | — |
| `rsync` on the server (standard on cPanel) | — |
| A subdomain, e.g. `crm.paradigmcargo.com`, with **document root = `crm/public`** | *Domains* |
| SSL certificate for the subdomain | *SSL/TLS Status* → AutoSSL |

Node is **not** needed on the server: the built CSS/JS (`public/build`) is in the repository.

## 2. First install
1. **Database:** nothing to do — the first deploy creates database `<cpaneluser>_crm` and its user through cPanel's API, with a random password written straight into `.env` (nobody types or sees it). To use your own database instead, fill `DB_*` in `~/crm/.env` before deploying.
2. **Domain:** the app is served from `public_html` of the account's primary domain (`.cpanel.yml`: `LINK_PUBLIC_HTML=1`; the old `public_html` is kept as `public_html.before-crm-<date>`). Set `CRM_URL` in `.cpanel.yml` to the final address. For a subdomain instead, set `LINK_PUBLIC_HTML=0` and point the subdomain's document root to `crm/public`.
3. **Clone:** *Git Version Control* → *Create* → clone URL `https://github.com/ammarhijazy-empire/paradigm-crm-latest.git`, repository path `repositories/paradigm-crm-latest`. (Private repo: use a GitHub fine-grained token with read access as the password, or a deploy key.)
4. **`.env`:** the first deploy creates `~/crm/.env` (mode 600) with the database, `APP_KEY` and push keys filled. Afterwards open it in *File Manager → Edit* to add `MAIL_PASSWORD` (Google app password) and `AI_*`, then deploy again.
5. **Deploy:** *Git Version Control* → *Manage* → *Pull or Deploy* → **Deploy HEAD Commit**. This runs `.cpanel.yml` → `deploy/deploy.sh`, which copies the code to `~/crm`, runs `composer install --no-dev`, migrations, reference data (roles, ports, settings) and caches. Log: `~/crm-deploy.log`.
   - If your PHP is not the default `php`, add before the bash line in `.cpanel.yml`: `- export PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php`.
6. **Cron (one line, every minute):** *Cron Jobs* → Common settings *Once Per Minute*:
   ```
   cd ~/crm && php artisan schedule:run >> /dev/null 2>&1
   ```
   With `QUEUE_VIA_SCHEDULER=true` this one cron also sends emails, push notifications and runs AI jobs.
7. **First account:** open `https://crm.paradigmcargo.com/register` and sign up with your `@paradigmcargo.com` email. **The first account becomes management (admin).** Everyone after that signs up and waits for management to give them roles (*Users* page).
8. Open **Settings**: company data, tax numbers, bank accounts per currency, minimum margin, VAT, AI switches and monthly AI limit, website lead keys.

## 3. Updating
Push to `main` on GitHub → cPanel *Git Version Control* → *Update from Remote* → *Deploy HEAD Commit*. The site shows a short maintenance page while migrations run. `.env`, uploads and logs are never touched.

## 4. Staging copy (recommended before go-live)
Second subdomain `crm-staging…` → document root `crm-staging/public`, its own database and `.env` with `CRM_STAGING_BANNER=true`, `APP_DEBUG=false`. Deploy with `bash ~/repositories/paradigm-crm-latest/deploy/deploy.sh ~/crm-staging`. Demo data for training: `php artisan db:seed --class=Database\\Seeders\\DemoSeeder` (set `DEMO_PASSWORD` first; **never on production**).

## 5. Checklist after install
- [ ] `https://` works and `http://` redirects
- [ ] Register first admin, then sign out/in
- [ ] Settings saved; exchange rates refreshed (*Settings → Refresh rates*)
- [ ] Test email: create a client file and send an RFQ to your own email as vendor; check *Outbox*
- [ ] Allow notifications in the browser; mention yourself in a file note → push arrives
- [ ] `~/crm/storage/logs/` has no errors; `php artisan schedule:list` shows the jobs
- [ ] Backups appear in `~/crm/storage/app/backups` the next morning

## 6. Without Terminal: running one-off commands
cPanel's *Cron Jobs* can run any artisan command once. Add a job for the next minute, wait until it has run, then **delete it**:
```
cd ~/crm && php artisan legacy:import --dry-run > ~/import-report.txt 2>&1
```
Read the result in *File Manager* (`~/import-report.txt`). Same way for `php artisan crm:backup`, `php artisan queue:retry all`, etc. After editing `.env` in File Manager, click **Deploy HEAD Commit** again (it refreshes the cached settings).
