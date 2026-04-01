# Multi-Tenant (Company-Based) Setup

This document describes the company-based multi-tenant architecture and how to use it.

## Architecture Overview

- **Central database** (default `mysql` / `mysql_central`): Stores `companies`, `company_otp_verifications`, `subscription_plans`, `plan_features`, `company_subscriptions`, `admin_notifications`. Configure the database name with `DB_DATABASE` or `CENTRAL_DB_DATABASE` in `.env` (create this database once on the server).
- **Per-company (tenant) databases**: Each company has its own database (e.g. `{DB_DATABASE_PREFIX}_company_15454`). Same table structure as your app (users, bikes, riders, etc.). The correct DB is selected at runtime from the URL `company_slug`.

## URL Structure

- **Company registration (public)**: `/company/register` (Step 1 → OTP → Step 3 details → Pending).
- **Company login**: `/app/{company_id}/login` (e.g. `efds.com/app/15454/login`). Login page shows company logo and branding.
- **Company app**: `/app/{company_id}/home`, `/app/{company_id}/bikes`, etc. All routes under `/app/{company_id}/` use the tenant DB and require auth.
- **Admin (global)**: `/admin/companies` to list, view, approve, or reject companies. Uses central DB and existing auth.

## Registration Flow

1. **Step 1**: Company name, email, country, phone, password → OTP sent to email (modal or redirect to OTP page).
2. **Step 2**: User enters OTP; on success → Step 3.
3. **Step 3**: Country (read-only), city, full address, “Are you a Taxpayer?” (if yes: NTN, Tax registration date). Submit → company saved with status **Pending**.
4. Admin is notified (email if `MAIL_ADMIN_NOTIFICATION_EMAIL` is set). Company cannot log in until approved.

## Admin Approval

1. Go to **Admin → Companies** (`/admin/companies`). Filter by Pending / Approved / Rejected.
2. **Approve**: Creates the company’s database (schema cloned from central), creates the first user (company owner) in the tenant DB with the registered email/password.
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

## Tenant Migrations

Tenant DBs are created with the same schema as your app (excluding central-only tables). Run once:

```bash
php artisan tenant:stub-migrations
```

This fills `database/migrations_tenant/` from `database/migrations/` (excluding companies, OTP, subscription, admin_notifications). When a company is approved, the tenant database is created from the central schema (see `TenantService::createDatabaseForCompany`).

### Applying new migrations to every company database

After you add or change migrations under `database/migrations` for shared app tables:

1. Sync the tenant copy: `php artisan tenant:stub-migrations`
2. Run central migrations (if any): `php artisan migrate`
3. Run tenant migrations on **all** company databases: `php artisan tenants:migrate`

Optional: `php artisan tenants:migrate --company=123` or `--tenant=tenant_company_5` for a single database.

## Adding More Company Routes

Company-scoped routes live in `routes/company_app.php` and are loaded under `/app/{company_id}/`. To mirror more of your main app (e.g. riders, vouchers, settings-panel), copy the corresponding routes from `routes/web.php` (inside the auth group) into `routes/company_app.php`. Use the same controller classes; they will use the tenant connection when the request is under `/app/{company_id}/`.

## Subscription Tables (Design Only)

Tables `subscription_plans`, `plan_features`, `company_subscriptions` are in place for future use (plan features, expiry, trial/active/expired). No subscription logic is implemented yet.

## Optional Enhancements

- **Country/City**: Replace the text inputs on registration with a country dropdown and a city dropdown (or API) if needed.
- **Phone validation**: Add country-based phone validation (e.g. libphonenumber or a custom rule).
- **Approval/Rejection emails**: Send email to the company when approved or rejected (template and notification trigger can be added).
- **Menu in company app**: Ensure the main menu uses `company.*` route names and that `company_id` is in the URL (e.g. from `URL::defaults(['company_id' => ...])` in middleware).
