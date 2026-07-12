# Runtime Verification

This document explains how to verify the Laravel Property Manager MVP without
adding new business features.

## Required Tools

- PHP 8.3
- Composer
- Node.js 20 or newer
- npm
- PostgreSQL for local app usage, based on `.env.example`
- SQLite PHP extensions for automated tests

PHP 8.3 is the supported verification baseline. Local PHP 8.4 may run the
project, but Composer dependency resolution is constrained to PHP 8.3
compatibility. CI and production must satisfy `composer check-platform-reqs`.

## Local App Setup

From the project root:

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Create a PostgreSQL database matching the values in `.env`, then run:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

Open:

```text
http://127.0.0.1:8000
```

## Run Tests

The existing `phpunit.xml` is configured to use SQLite in memory for tests.

```bash
php artisan test
```

## Frontend Build

Install frontend dependencies and build production assets:

```bash
npm ci
npm run build
```

For local frontend development:

```bash
npm run dev
```

## CI Verification

The GitHub Actions workflow runs:

- `composer install`
- `composer validate --strict`
- `composer check-platform-reqs`
- `php artisan test`
- `npm ci`
- `npm run build`

Tests use SQLite in CI. Do not treat the application as runtime verified until
the workflow completes successfully.

## Private Pilot Runtime Checks

- Verify `/up` after deployment.
- Run `php artisan operations:verify` from a trusted production command console.
- Run `php artisan schedule:list` and confirm the two daily maintenance commands are listed.
- Verify payment proof and expense invoice downloads through the application,
  not through `/storage`.
- Verify `php artisan payments:mark-overdue` and `php artisan contracts:expire`
  can run safely on staging.
- Rehearse `php artisan pilot:reset-owner-password {email}` only against a
  non-production owner account, entering the new password through the hidden
  prompt.

For production operations, backup, and incident handling, use:

- [Production Operations Runbook](PRODUCTION_OPERATIONS_RUNBOOK.md)
- [Backup And Recovery Runbook](BACKUP_AND_RECOVERY_RUNBOOK.md)
- [Incident Response Runbook](INCIDENT_RESPONSE_RUNBOOK.md)
