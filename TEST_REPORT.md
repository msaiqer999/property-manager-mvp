# Test Report

Date: 2026-06-06  
Project: Property Manager MVP

## Summary

The project has been hardened at the code/package level and includes automated
Feature tests for the requested smoke checks. Runtime execution could not be
completed in this Codex environment because `php`, `composer`, and `npm` are not
installed or available in PATH.

This report intentionally does not claim successful runtime verification that
was not possible on this machine.

## Commands Requested

### Environment Tool Checks

Executed:

```bash
php -v
composer --version
npm --version
where.exe php
where.exe composer
where.exe npm
```

Result:

- `php`: not found
- `composer`: not found
- `npm`: not found
- `where.exe`: could not find `php`, `composer`, or `npm`

Because these tools are missing, the following commands could not be executed:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
npm install
npm run build
php artisan serve
```

## Code Hardening Completed

- Updated `docs/known-limitations.md` so it no longer describes the project as a
  partial scaffold.
- Registered Laravel console commands in `bootstrap/app.php`.
- Added `config/hashing.php`.
- Hardened the reports PDF Blade template so it works with both Eloquent models
  and database query result rows.
- Added Feature tests covering:
  - all demo users can log in
  - owner can open core pages
  - manager cannot access users
  - accountant cannot edit contracts
  - caretaker cannot access reports
  - cross-organization access is forbidden
  - contract PDF route responds
  - payment receipt PDF route responds
  - report PDF route responds

## Automated Tests Added

Files:

- `tests/Feature/MvpSmokeTest.php`
- `tests/Feature/PdfExportTest.php`
- Existing tests retained:
  - `tests/Feature/AuthenticationTest.php`
  - `tests/Feature/OrganizationScopeTest.php`
  - `tests/Feature/RoleAccessTest.php`

These should be run with:

```bash
php artisan test
```

## Demo Accounts To Verify After Runtime Setup

All demo accounts use password `password`.

- `owner@example.com`
- `manager@example.com`
- `accountant@example.com`
- `caretaker@example.com`

## Pages To Verify After Runtime Setup

- Dashboard: `/`
- Buildings: `/buildings`
- Units: `/units`
- Tenants: `/tenants`
- Contracts: `/contracts`
- Payments: `/payments`
- Expenses: `/expenses`
- Reports: `/reports`
- Users: `/users`
- Activity Logs: `/activity-logs`

## Role Restrictions To Verify

- Owner can access everything.
- Manager cannot access `/users`.
- Accountant cannot open contract edit pages.
- Caretaker cannot access `/reports`.

These restrictions are covered by `tests/Feature/MvpSmokeTest.php`.

## Organization Isolation

Cross-organization show-page access is covered by:

- `tests/Feature/MvpSmokeTest.php`
- `tests/Feature/OrganizationScopeTest.php`

## PDF Routes

PDF response checks are covered by:

- contract PDF: `contracts.pdf`
- payment receipt PDF: `payments.receipt`
- report PDF: `reports.pdf`

Test file:

- `tests/Feature/PdfExportTest.php`

## Mobile Layout

The Blade layout is mobile-first:

- sticky horizontal navigation
- large tap targets
- responsive dashboard cards
- horizontally scrollable tables
- stacked mobile filters and forms
- RTL `dir` readiness based on locale

Visual confirmation still requires running the app in a browser because this
environment cannot start Laravel.

## Known Remaining Limitations

- Runtime verification is pending until PHP, Composer, npm, and PostgreSQL are
  installed.
- Reports are MVP summaries, not audited accounting reports.
- Contract/payment workflows do not yet cover amendments, refunds, reversals, or
  proration.
- Full Laravel Policies are recommended before production.
- Browser automation tests are not included.

## Recommended Next Steps

1. Install PHP 8.2+, Composer, PostgreSQL, Node.js, and npm.
2. Run:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
npm install
npm run build
php artisan serve
```

3. Open `http://127.0.0.1:8000`.
4. Verify the demo accounts and pages listed above.
5. Run a browser/mobile QA pass.
6. Move authorization to Laravel Policies before production launch.
