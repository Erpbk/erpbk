# Company Isolation Setup (Single ERPBK DB)

This document describes the production architecture using one shared `erpbk` database with `company_id` isolation, plus a separate admin database.

## Architecture Overview

- **ERPBK shared database** (`mysql` / `mysql_central`): Stores all business data. Tenant isolation is handled via `company_id` in application tables.
- **Admin database** (`mysql_admin`): Stores admin panel auth/content tables (`admin_users`, blogs, testimonials, policies, admin mirror company list).

## URL Structure

- **Company registration (public)**: `/company/register` (Step 1 → OTP → Step 3 details → Pending).
- **Company login**: `/app/{company_slug}/login` (e.g. `/app/efds/login`).
- **Company app**: `/app/{company_slug}/home`, `/app/{company_slug}/bikes`, etc. Routes stay in the shared DB and are filtered by `company_id`.
- **Admin (global)**: `/admin/companies` to list, view, approve, or reject companies. Uses central DB and existing auth.

## Registration Flow

1. **Step 1**: Company name, email, country, phone, password → OTP sent to email (modal or redirect to OTP page).
2. **Step 2**: User enters OTP; on success → Step 3.
3. **Step 3**: Country (read-only), city, full address, “Are you a Taxpayer?” (if yes: NTN, Tax registration date). Submit → company saved with status **Pending**.
4. Admin is notified (email if `MAIL_ADMIN_NOTIFICATION_EMAIL` is set). Company cannot log in until approved.

## Admin Approval

1. Go to **Admin → Companies** (`/admin/companies`). Filter by Pending / Approved / Rejected.
2. **Approve**: Creates the first company owner user in `erpbk.users` with `company_id = companies.id`.
3. **Reject**: Sets status to Rejected; optional reason stored.

After approval, the company can log in at `/app/{company_id}/login`.

## Fresh / empty central database

The `database/migrations` folder contains many **incremental** migrations (`ALTER TABLE`, indexes, etc.) that assume **base tables already exist** (from older migrations or a restored database). If you create an empty MySQL database and run `php artisan migrate` from scratch, you may see **“table doesn’t exist”** until the full schema is present.

**Practical options:**

1. **Restore a SQL dump** from staging/production into your central DB, then run `php artisan migrate` for new changes only.
2. **Only need multi-tenant registration?** Ensure at least the central tables for companies exist — run the `2026_03_18_*` migrations (or the paths documented in deploy notes). The `companies` table is created by `2026_03_18_100000_create_companies_table.php`.

## Environment

In `.env`:

- `DB_DATABASE` – Name of the **central** database (must exist; not a tenant DB).
- `CENTRAL_DB_DATABASE` – Optional; if set, overrides `DB_DATABASE` for central/tenant host credentials only (same MySQL server).
- `DB_DATABASE_PREFIX` – Prefix for tenant DB names (default `tenant` → `tenant_company_1`, etc.).
- `MAIL_ADMIN_NOTIFICATION_EMAIL` – Receives “new company registered” emails (optional).
- `ADMIN_DATABASE_URL` – Optional full DSN for the admin database (recommended on Laravel Cloud for the second DB).
- `ADMIN_DB_HOST` / `ADMIN_DB_PORT` / `ADMIN_DB_DATABASE` / `ADMIN_DB_USERNAME` / `ADMIN_DB_PASSWORD` – Admin DB credentials when DSN is not used.

## Migrations

With single-DB company isolation, only these commands are needed:

```bash
php artisan migrate --force
php artisan admin:migrate --force
```

## Laravel Cloud (central + admin DB)

When deploying to Laravel Cloud with two databases:

1. Keep your existing central DB variables as-is (`DB_*` or `DATABASE_URL`).
2. Set admin DB variables for the second database (`ADMIN_DATABASE_URL` or `ADMIN_DB_*`).
3. Use this deploy migration command so both DBs are migrated:

```bash
php artisan app:migrate-all --force
```

If you only need the admin DB migration step manually, run:

```bash
php artisan admin:migrate --force
```

## Adding More Company Routes

Company-scoped routes live under `/app/{company_slug}/`. Controllers must enforce `company_id` scoping for all tenant data access.

## Subscription Tables (Design Only)

Tables `subscription_plans`, `plan_features`, `company_subscriptions` are in place for future use (plan features, expiry, trial/active/expired). No subscription logic is implemented yet.

## Optional Enhancements

- **Country/City**: Replace the text inputs on registration with a country dropdown and a city dropdown (or API) if needed.
- **Phone validation**: Add country-based phone validation (e.g. libphonenumber or a custom rule).
- **Approval/Rejection emails**: Send email to the company when approved or rejected (template and notification trigger can be added).
- **Menu in company app**: Ensure the main menu uses `company.*` route names and that `company_id` is in the URL (e.g. from `URL::defaults(['company_id' => ...])` in middleware).
