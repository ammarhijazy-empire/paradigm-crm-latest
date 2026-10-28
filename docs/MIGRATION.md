# Migrating from the old Supabase CRM

`php artisan legacy:import` reads the old Supabase (Postgres) database **read-only** and copies everything into the new MySQL database.

## How to run
1. Install the new system and create the first management account (DEPLOY.md). The import needs it as the "system" user.
2. Fill the `LEGACY_*` lines in `.env` (Supabase → Project settings → Database → connection info; Storage URL + a key with read access to Storage), then `php artisan config:cache`.
   The server's PHP needs the `pdo_pgsql` extension for this step only (cPanel *Select PHP Version → Extensions*). If the host cannot enable it, run the import on a computer that can, pointing `DB_*` at the cPanel MySQL (*Remote MySQL* access), or import into a local copy and upload the dump.
3. **Rehearse:** `php artisan legacy:import --dry-run` — reads and maps everything, prints the report, saves nothing.
4. **Import:** `php artisan legacy:import`. Whole run is one transaction: if anything unexpected stops it, nothing is saved.
5. Read the report table (old rows / imported / failed per table). Lines starting `•` are notes (e.g. duplicate client merged), `✗` are rows that failed with the reason.
6. Spot-check: a few files at each stage, a client's history, invoice balances, documents open.
7. Freeze the old CRM (read-only), run `php artisan legacy:import` once more to pick up the last changes — **it is repeatable**: old→new ids are kept in `legacy_ids`, so a second run updates instead of duplicating.
8. Remove the `LEGACY_*` lines from `.env`, `php artisan config:cache`.

Run one part only: `--only=users,vendors,clients,files,pricing,money,activity,documents,rates,counters` (comma list, in this order).

## Passwords
Not copied (Supabase Auth hashes are not transferable). Users exist with their roles; each person uses **Forgot password** on first login.

## Mapping
| Old (Supabase) | New | Notes |
|---|---|---|
| `profiles` | `users` + roles | matched by email; `roles` array → roles; target, commission, active, gets_leads kept |
| `vendors` | `vendors` | Arabic type names → type keys; city goes into notes; email lower-cased |
| `clients` | `clients` | sector names → sector keys (unknown → *other* + original text); notes → first activity; **same phone = same client** (merged, reported as a note) |
| `files` (+ `inq`, `checks`, `ship`, `quote` JSON) | `files`, `file_booking` | number `PC-YY-NNN` and sales ref kept; services → main service + add-ons (FCL/LCL/AIR/LAND/BULK/RORO/SE/Courier; CC→clearance, TRK→trucking, INS→insurance); port names → port codes; free time → days; check names mapped one-to-one (contact→first_contact … feedback→feedback_logged); ACID number → *ACID / Nafeza* check; status done→closed |
| `vendor_quotes`, `cost_lines` + `cost_line_actual`, `quote_lines`, `quote_rounds`, `file_pay_terms`, `cost_reviews`, `reprice_requests` | same names | payment methods: cash_in_advance, against_documents, on_arrival, credit |
| `invoices`, `invoice_lines`, `payments` | same names | invoice totals recalculated from lines + VAT; status from payments (issued / partly paid / paid); old `eta_no` → e-invoice number |
| `activities`, `file_history`, `file_notes`, `file_updates`, `file_events` | same names | |
| `documents` + Supabase Storage files | `documents` + private disk | each file downloaded from Storage |
| `exchange_rates`, `settings.min_margin` | `exchange_rates`, Settings | |
| `ref_counters` | `ref_counters` | numbering continues after the highest imported number |

Tested in `tests/Feature/LegacyImportTest.php` (sample of every table, run twice, no duplicates).
