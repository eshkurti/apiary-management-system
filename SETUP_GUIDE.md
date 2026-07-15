# Local Environment Setup Guide

**Project:** Honigmanufaktur Lindenhof — Apiary Management System
**Stack:** Yii2 Advanced Template · PHP 8.2+ · MariaDB 10.11 · Composer

This guide takes a teammate from a fresh clone to a fully working local
environment with seed data, on a Windows machine that already has **PHP**,
**Composer**, and **MariaDB** installed and on the `PATH`. It assumes you are
comfortable with Yii2 basics but know nothing about this project's specific
setup quirks. Follow the steps **in order** — several of them depend on the
previous one.

All commands are run from the **project root** (the folder containing
`composer.json`, `yii`, and this file) unless stated otherwise. Examples use
**PowerShell**; they work the same in CMD or Git Bash.

> **Why some files are missing after cloning.** The `*-local.php` config files
> and the `backend/web/index.php` / `frontend/web/index.php` entry scripts are
> **git-ignored**. They do not exist in a fresh clone — they are *generated* in
> Step 2 by `php init`. Do not look for them before running `init`.

---

## 1. Install Composer dependencies

From the project root:

```powershell
composer install
```

This reads `composer.lock` and installs the exact dependency versions into
`vendor/` (Yii2, the RBAC component, the PDF export library, etc.). It can take
a couple of minutes on first run.

If Composer complains about a missing PHP extension, enable it in your
`php.ini`. This project needs the standard Yii2 set: `pdo_mysql`, `mbstring`,
`intl`, `openssl`, `ctype`, `fileinfo`, `gd` (used by the PDF exports).

---

## 2. Initialize the application (generate local config + entry scripts)

The advanced template keeps environment-specific files out of git. Generate
them with the bundled `init` command and choose the **Development** environment:

```powershell
php init --env=Development --overwrite=All
```

This creates, among others:

- `common/config/main-local.php`, `backend/config/main-local.php`,
  `frontend/config/main-local.php` (and their `params-local.php` siblings)
- `backend/web/index.php` and `frontend/web/index.php` (the entry scripts)
- A randomly generated `cookieValidationKey` in the backend and frontend
  `main-local.php` files
- Writable `runtime/` and `web/assets/` directories

You now have a runnable skeleton. Next, point it at a database.

---

## 3. Set up the local MariaDB database

### 3.1 Create the database and a dedicated user

Run the bundled setup script once, as root. **PowerShell does not support `<`
for input redirection** (it's a reserved-but-unimplemented operator — this was
hit and confirmed directly), so pipe the file in instead:

```powershell
Get-Content local-db-setup.sql | mysql -u root -p
```

In CMD or Git Bash, the more familiar `mysql -u root -p < local-db-setup.sql`
works as expected.

This creates the `apiary` database and an `apiary` MariaDB user with broad
privileges (not just scoped to `apiary`.*). The broad grant is deliberate —
see the comment at the top of `local-db-setup.sql` and Step 4 below for why a
database-scoped grant isn't enough. If you'd rather see exactly what it does
or run it by hand, open the file; it's four short SQL statements.

> Yii does **not** create the database for you — it must exist before you run
> migrations. Using `utf8mb4` matters: the data contains German text
> (`Blütenhonig`, `Veterinäramt`, `§§`).

> **Starting over / re-running this script:** `local-db-setup.sql` uses
> `CREATE DATABASE IF NOT EXISTS` and `CREATE USER IF NOT EXISTS`, so it's
> safe to re-run without erroring — but that also means it will **not** reset
> an existing `apiary` database. If you want a genuinely clean slate (e.g.
> testing the whole setup flow again from scratch), drop it first:
> ```powershell
> mysql -u root -p -e "DROP DATABASE IF EXISTS apiary;"
> ```
> then run the setup script again before `php yii migrate`.

### 3.2 Database connection — no editing needed

`common/config/main-local.php` (generated in Step 2) already defaults to the
exact `apiary`/`apiary_secret` credentials `local-db-setup.sql` creates, via
the `environments/dev/common/config/main-local.php` template — so as long as
you ran the script in 3.1 with the default database/user names, there's
nothing to edit here. Only touch this file if you changed the database name,
username, or password in `local-db-setup.sql` first.

Mail is already configured with `useFileTransport => true`, so account
verification and other emails are written to `common/runtime/mail/*.eml`
instead of being sent — what you want for local development.

> The `authManager` is already configured as `yii\rbac\DbManager` in the shared
> `common/config/main.php`, so RBAC reads its roles and permissions from the
> database. That is why the RBAC tables must exist before any project migration
> runs (next step).

---

## 4. Run the migrations

Run the application migrations from `console/migrations/` — this is a single
step:

```powershell
php yii migrate
```

Answer `yes` to apply all of them.

> **Do not run `php yii migrate --migrationPath=@yii/rbac/migrations` first.**
> Older notes about this project (including an earlier version of this file)
> said to run Yii's own RBAC framework migration before the project
> migrations, on the theory that `authManager` needs its tables to exist
> first. That's no longer correct: the project's own first migration,
> `m260614_000001_create_rbac_tables`, already creates those exact tables
> itself — its docblock says so explicitly ("inlines that step so a single
> `php yii migrate` bootstraps the entire application from scratch"). Running
> the framework migration first creates the RBAC tables early, and then the
> project's own migration fails with `Base table or view already exists:
> auth_rule`, which cancels every migration after it — you'd end up with only
> 2 of 20 migrations applied and a completely broken install. This was
> confirmed by actually running both orders: the two-step order fails at
> migration 3; a single `php yii migrate` applies all 20 cleanly.

Migrations run in order:

1. `m130524_201442_init` — create the `user` table
2. `m190124_110200_*` — add the email verification column
3. `m260615_000001_rbac_seed` — create the four roles
   (`administrator`, `headBeekeeper`, `fieldBeekeeper`, `customer`) and assign
   **`administrator` to user id 1** (see Step 5)
4. `m260616_000001_rbac_permissions` — create granular permissions and attach
   them to the roles
5. `m260616_000002_create_domain_tables` — apiary stands, colonies, inspections,
   treatments, batches, products, orders, customers, etc.
6. `m260616_000003_seed_demo_data` — the demo dataset used by the smoke test
7. `m260617_*` / `m260618_*` — the `treatment_product` reference table,
   wholesale pricing, inspection feeding, treatment receipt/vet fields, batch
   review note, and product review-unpublish flag
8. `m260621_000001_seed_demo_users` — seeds one demo account per non-admin RBAC
   role (`headBeekeeper`, `fieldBeekeeper`, `customer`), plus the linked CRM
   `Customer` record for the customer account
9. `m260621_000002_clean_demo_customers` / `m260621_000004_seed_demo_orders` /
   `m260621_000005_fix_tierhalter_name` — demo data cleanup and the sample
   order set
10. `m260703_000001_create_database_accounts` — creates the three least-privilege
    MariaDB accounts described under **Database access model** in the README

> **Migration 10 needs a genuinely privileged DB user, not just rights on the
> `apiary` schema.** It runs `CREATE USER`, `GRANT ... WITH GRANT OPTION`, and
> `FLUSH PRIVILEGES` — three separate global administrative privileges that
> `GRANT ALL PRIVILEGES ON apiary.*` (Step 3.1) does not include, since none of
> them are scoped to a single database. If you followed Step 3.1 exactly and
> migrate as the `apiary` user, this migration fails three times in a row with
> three different "access denied" errors as you chase each privilege
> individually — confirmed by actually hitting all three. Rather than widen the
> `apiary` account's privileges piecemeal, just grant it comprehensively before
> running migrations, in the same `mysql -u root -p` shell from Step 3.1:
> ```sql
> GRANT ALL PRIVILEGES ON *.* TO 'apiary'@'localhost' WITH GRANT OPTION;
> FLUSH PRIVILEGES;
> ```
> This migration doesn't block anything else if it's skipped — it only
> provisions the three `lindenhof_*` accounts used by the actual deployed
> server's connection-layer wiring (see **Database access model** in the
> README), which nothing in local dev references. If migrations 1–9 and 11+
> all applied, the application is fully functional either way.

After this completes, the schema, reference data, and demo accounts are all in
place — **you can log in immediately, no manual account creation needed.**

---

## 5. Log in

Two migrations (`m260614_000002_seed_admin_user` and `m260621_000001_seed_demo_users`)
already seeded four working accounts, all sharing one demo password:

| Username         | Role            | Password      |
|-------------------|-----------------|---------------|
| `admin`           | administrator   | `COSD@groupb` |
| `headBeekeeper`   | headBeekeeper   | `COSD@groupb` |
| `fieldBeekeeper`  | fieldBeekeeper  | `COSD@groupb` |
| `customer`        | customer        | `COSD@groupb` |

Log in to the backend (Step 6 below) with `admin` / `COSD@groupb` and you're an
administrator immediately — no SQL, no password hashing, nothing to hand-create.

> **Creating an additional account beyond the seeded four:** the RBAC seed
> hard-assigns the `administrator` role to **user id 1**, so on a clean install
> the first admin migration claims that automatically. To grant a role to a
> further account you create later, assign it directly:
>
> ```sql
> INSERT INTO `auth_assignment` (`item_name`, `user_id`, `created_at`)
> VALUES ('administrator', '<the_user_id>', UNIX_TIMESTAMP());
> ```
>
> The other roles work the same way — use `headBeekeeper`, `fieldBeekeeper`, or
> `customer` as the `item_name`.

---

## 6. Start the development servers

The backend (staff) and frontend (public shop) are two separate Yii
applications with their own web roots. Run each behind PHP's built-in server, on
its own port. Use **two terminals** (each server runs in the foreground).

**Backend** (staff admin):

```powershell
php -S 127.0.0.1:8081 -t backend/web
```

**Frontend** (public shop):

```powershell
php -S 127.0.0.1:8080 -t frontend/web
```

Then open:

| Application | URL                       | Default landing page          |
|-------------|---------------------------|-------------------------------|
| Backend     | http://127.0.0.1:8081     | Operations dashboard (login)  |
| Frontend    | http://127.0.0.1:8080     | Public honey shop             |

Keep both terminals open while developing. Stop a server with `Ctrl+C`.

---

## 7. The local cookie-path fix (required for backend login)

**Symptom this fixes:** with the PHP built-in server, you submit the backend
login form with correct credentials and get a **400 "Unable to verify your data
submission"** error (CSRF validation failure), or get bounced straight back to
the login page without one — either way, you never reach the dashboard. This
was confirmed live: it reproduces in a clean incognito window with no stale
cookies involved.

**Cause:** the production deployment serves the backend under a `/admin` path
behind a reverse proxy, so `backend/config/main.php` deliberately pins its
session, identity, and CSRF cookies to `path => '/admin'` — that's correct for
production and should not be changed. PHP's built-in server (`php -S`, what
this guide has you use for local development) has no path-based routing at
all; it serves the backend from the domain root of its own port instead. A
cookie scoped to `/admin` is never sent back by the browser on a request to
`/`, so the CSRF token can't be validated and login fails every time.

**The fix** lives in `backend/config/main-local.php` (generated by `php init`
from the `environments/dev/backend/config/main-local.php` template), which
overrides the cookie paths back to `/` — specifically for local dev, without
touching the production-facing value in `backend/config/main.php`:

```php
'request' => [
    'cookieValidationKey' => '...',   // generated by php init — leave as-is
    'csrfCookie' => ['path' => '/'],
],
'user' => [
    'identityCookie' => ['path' => '/'],
],
'session' => [
    'cookieParams' => ['path' => '/'],
],
```

Because this lives in the template `php init` copies from, a fresh clone gets
it automatically — no manual step needed. If you ever find login looping or
hitting a CSRF 400 on the built-in server, this is the first thing to check:
confirm `backend/config/main-local.php` still has these three `path => '/'`
overrides (they can be lost if you regenerate that file without diffing it
first). (The frontend uses distinct cookie names — `advanced-frontend`,
`_identity-frontend`, `_csrf-frontend` — so the two apps do not clash even when
both are served from `/`.)

---

## 8. Smoke test checklist

The `m260616_000003_seed_demo_data` migration ships a realistic dataset:
**6 apiary stands** (`LIN-S-001`…`006`), **12 colonies** (`LIN-C-001`…`012`),
inspections, **8 treatments**, **2 batches** (`LIN-2026-001` released,
`LIN-2026-002` pending), **1 published product**, **3 customers**, and
**2 orders**. Click through these to confirm everything works:

1. **Backend login** — go to http://127.0.0.1:8081, log in as `admin` with
   the password from Step 5 (`COSD@groupb`). You should land on the operations dashboard showing
   open orders, low-stock alerts, colonies in Wartezeit, and pending batches.
2. **Colony Stockkarte** — Production Management → Colonies → open
   **`LIN-C-007`** → Stockkarte. It should show an **active withdrawal period**
   (the MAQS treatment applied 2026-06-01) with a Wartezeit expiry date.
3. **Release gate** — Compliance → Release Gate. Batch **`LIN-2026-002`** should
   be *pending release* with failing conditions (HACCP not confirmed, missing
   label fields), while **`LIN-2026-001`** is already *released*.
4. **Bestandsbuch export** — Compliance → Bestandsbuch, pick a stand (or
   *all stands*) and a date range, and download both the **CSV** and the **PDF**.
   The PDF should match the LWG Bayern layout; the CSV should open cleanly in
   Excel (UTF-8 with BOM, German characters intact).
5. **Public shop** — go to http://127.0.0.1:8080. The product **“Lindenhof
   Wildflower Honey 500g”** should be listed; open it and confirm the
   traceability panel shows lot **`LIN-2026-001`**, harvest date, and water
   content inherited from the batch.
6. **Orders board** — back in the backend, Orders should show **`ORD-2026-0001`**
   (shipped) and **`ORD-2026-0002`** (received, wholesale Bäckerei Weber).

If all six pass, the environment is wired up correctly end-to-end (field record
→ compliance gate → shop product → order).

---

## 9. Project structure overview

Standard Yii2 advanced layout — three applications sharing one database and one
set of models:

```
common/
    models/        Shared ActiveRecord models (the domain lives here):
                   ApiaryStand, Colony, Treatment, TreatmentProduct, Batch,
                   Product, Order, OrderItem, Customer, User, ...
    config/        Shared config (main.php defines the DbManager authManager)
console/
    migrations/    All database migrations (schema + RBAC seed + demo data)
    controllers/   Console commands
backend/
    controllers/   Staff web controllers
    views/         Staff view files (one folder per controller)
    services/      PdfExportService (Bestandsbuch / Stockkarte PDFs)
    config/        Backend config (main.php holds the cookie-path fix — Step 7)
    web/           Backend entry script + web assets
frontend/
    controllers/   Public-shop web controllers
    views/         Public-shop view files
    web/           Frontend entry script + web assets
environments/      Templates that `php init` copies into the *-local.php files
```

### Which backend section maps to which module

The three requirement modules map onto controllers like this:

| Module                    | Backend controllers / services                                                                                   | Key models                                              |
|---------------------------|-----------------------------------------------------------------------------------------------------------------|---------------------------------------------------------|
| **Production Management** | `CompanyProfileController`, `ApiaryStandController`, `ColonyController`, `InspectionController`, `TreatmentController`, `BatchController` (harvest + batch details), `DashboardController` | `ApiaryStand`, `Colony`, `Inspection`, `Treatment`, `TreatmentProduct`, `Batch` |
| **Compliance**            | `ComplianceController` (release gate, release/re-release, Bestandsbuch & Stockkarte export, recall trace), the disease-flag actions on `ColonyController`, and `backend/services/PdfExportService` | `Batch`, `Colony`, `Treatment`, `CompanyProfile`        |
| **Ecommerce**             | Backend: `ProductController`, `OrderController`, `CustomerController`. Frontend: `ShopController`, `CartController`, `CheckoutController`, `AccountController` | `Product`, `Order`, `OrderItem`, `Customer`             |

Controllers enforce **granular RBAC permissions** (e.g. `recordTreatment`,
`evaluateReleaseGate`, `releaseBatch`, `manageProducts`), not roles directly.
The permission-to-role mapping lives in
`console/migrations/m260616_000001_rbac_permissions.php`.

---

## 10. The `treatment_product` reference table (and how to extend it)

The treatment form (US-PM-04 / US-PM-08) offers a dropdown of **approved German
veterinary products**. Picking one autofills the medicinal product name and its
typical withdrawal period, duration, quantity, and supplier — all of which
remain editable. That dropdown is driven by the **`treatment_product`** table,
created and seeded by
`console/migrations/m260617_000001_create_treatment_product.php`.

Columns:

| Column                    | Meaning                                            |
|---------------------------|----------------------------------------------------|
| `treatment_type`          | `varroa` or `tracheenmilbe`                         |
| `product_name`            | Medicinal product (e.g. `Oxuvar 5.7%`)             |
| `typical_withdrawal_days` | Default Wartezeit in days (autofilled)             |
| `typical_duration_days`   | Default treatment duration in days                 |
| `typical_quantity`        | Default quantity per colony (free text)            |
| `supplier_name`           | Default supplier                                    |
| `supplier_address`        | Default supplier address                           |

> In German practice the same substance is approved for both Varroa and
> Tracheenmilbe, so the seed inserts **one row per `treatment_type`** for each
> product. Follow that convention when adding products.

### To add a newly approved product

**Option A — quick local addition (SQL):** insert one row per treatment type.

```sql
INSERT INTO `treatment_product`
    (`treatment_type`, `product_name`, `typical_withdrawal_days`,
     `typical_duration_days`, `typical_quantity`, `supplier_name`, `supplier_address`)
VALUES
    ('varroa',        'VarroMed', 0, 6, '15–45 ml per colony', 'BeeVital GmbH', 'Wegscheid 8, 5360 St. Wolfgang, Austria'),
    ('tracheenmilbe', 'VarroMed', 0, 6, '15–45 ml per colony', 'BeeVital GmbH', 'Wegscheid 8, 5360 St. Wolfgang, Austria');
```

**Option B — reproducible (preferred for the team):** add a new migration so the
product exists in every environment. Create
`console/migrations/mYYMMDD_HHMMSS_add_<product>_treatment_product.php` with a
`safeUp()` that `batchInsert`s the same rows (mirroring the original seed
migration), and run `php yii migrate`. This keeps the reference data in version
control rather than living only in one developer's database.

After adding rows, reload the treatment form — the new product appears in the
dropdown for its treatment type and autofills the values you supplied.
