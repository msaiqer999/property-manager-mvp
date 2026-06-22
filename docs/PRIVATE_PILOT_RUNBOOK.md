# Private Pilot Runbook

This runbook is for a controlled private pilot of Property Manager with known users and real family-property data. It does not replace later production hardening for a public commercial launch.

## Supported Stack

- Ubuntu 22.04 or 24.04 LTS
- Nginx
- PHP-FPM 8.3
- PostgreSQL 14 or newer
- Node 20 for frontend builds
- Composer
- npm

PHP 8.3 is the supported private-pilot baseline. Local PHP 8.4 may run the
project, but Composer dependency resolution is constrained to PHP 8.3
compatibility. Production and CI must satisfy `composer check-platform-reqs`.

Required PHP extensions:

- `bcmath`
- `ctype`
- `curl`
- `dom`
- `fileinfo`
- `json`
- `mbstring`
- `openssl`
- `pdo_pgsql`
- `tokenizer`
- `xml`
- `zip`

## Pre-Deployment

1. Take a database backup and private-upload backup before every release.
2. Copy `.env.production.example` to `.env`.
3. Replace every placeholder value in `.env`.
4. Generate `APP_KEY` with `php artisan key:generate --show` or `php artisan key:generate`.
5. Keep `REGISTRATION_ENABLED=false`.
6. Keep `APP_DEBUG=false`.
7. Set `APP_URL` to the final HTTPS URL.
8. Set `APP_TIMEZONE=Asia/Dubai`.
9. Use a PostgreSQL application user with least privilege. Do not use the `postgres` superuser.
10. Confirm SMTP works before relying on password reset email.

## Deployment Steps

From the release directory:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Set writable permissions for:

- `storage`
- `bootstrap/cache`

Run production migrations only after a backup:

```bash
php artisan migrate --force
```

Build Laravel caches:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Create the first owner on a clean pilot database:

```bash
php artisan pilot:create-owner
```

Verify the health endpoint:

```text
https://your-pilot-domain.example/up
```

Smoke-test owner login, dashboard, building/unit/tenant creation, contract creation, payment recording, expense creation, reports, PDFs, Arabic/English switching, and mobile layout.

## Scheduler

Add the Laravel scheduler cron on the server:

```cron
* * * * * cd /var/www/property-manager/current && php artisan schedule:run >> /var/log/property-manager-scheduler.log 2>&1
```

Configured scheduled commands:

- `contracts:expire` runs daily at `00:30`.
- `payments:mark-overdue` runs daily at `01:00`.

The server timezone and `APP_TIMEZONE` must be aligned for the pilot. Use `Asia/Dubai` unless the pilot owner explicitly chooses another timezone.

Both scheduled commands are safe to rerun manually because they only update rows that still match their pending/current predicates. No queue worker is required while `QUEUE_CONNECTION=sync`.

Review `/var/log/property-manager-scheduler.log` after deployment and after the first overnight run.

## HTTPS And Session Safety

- Install a valid HTTPS certificate before exposing the pilot.
- Use `SESSION_SECURE_COOKIE=true`.
- Use `SESSION_SAME_SITE=lax`.
- Use `SESSION_ENCRYPT=true`.
- Configure Nginx/PHP-FPM so Laravel receives the intended HTTPS URL and host.
- If the app is behind a proxy or load balancer, configure trusted proxy behavior at the web-server/infrastructure layer until Laravel proxy middleware is explicitly added in a later batch.
- Security headers such as CSP, HSTS, X-Frame-Options, X-Content-Type-Options, and Referrer-Policy are a separate hardening batch and are not implemented in this repository yet.

## Private Uploads

Payment proofs and expense invoices are stored on the private local filesystem under `storage/app/private`.

Pilot rules:

- Back up `storage/app/private` every day.
- Do not use `php artisan storage:link` to expose private uploads.
- Do not move private uploads to the public disk.
- Secure authorized viewing/download routes are not implemented yet and remain a must-fix bounded batch.

## Backup Plan

- Daily PostgreSQL backup.
- Daily backup of `storage/app/private`.
- Pre-deployment database and private-upload backup before every release.
- Keep daily backups for 14 days.
- Keep weekly backups for 8 weeks.
- Store an encrypted off-server copy.
- Run a restore rehearsal at least monthly.
- Recovery Point Objective (RPO): 24 hours.
- Recovery Time Objective (RTO): 4 hours.
- Backup failures must notify the operator or owner responsible for the pilot.

## Restore Rehearsal

Never rehearse restore directly on production.

Use staging:

1. Restore the latest PostgreSQL backup into a staging database.
2. Restore private uploads into staging `storage/app/private`.
3. Point staging `.env` at the restored database and upload directory.
4. Verify organization, user, building, unit, tenant, contract, payment, expense, and activity-log counts.
5. Verify owner login.
6. Verify contracts, payment schedules, payment recording, expenses, and reports.
7. Verify contract, receipt, and report PDFs.
8. Record the restore duration and any manual fixes needed.

## Rollback

Use a release directory layout, for example:

```text
/var/www/property-manager/releases/2026-06-22-1200
/var/www/property-manager/current -> releases/2026-06-22-1200
```

Application rollback means repointing `current` to the previous release and clearing/rebuilding caches.

Database rollback is a separate decision. If migrations changed data or schema, restore from the pre-deployment backup only after testing the restore path on staging. Do not run blind production `php artisan migrate:rollback`.

## Pilot Acceptance Checklist

Pass all items before live pilot use:

- Owner can log in.
- Owner can create a building, unit, and tenant.
- Owner can create a contract and generated payment schedule is correct.
- Partial payment can be recorded.
- Full payment can be recorded.
- Payment proof image can be uploaded.
- Receipt PDF downloads for recorded money.
- Expense can be created.
- Expense can be voided with a reason.
- Tenant can be archived when lifecycle rules allow it.
- Contract can be terminated and future unpaid/partial payments become cancelled.
- Dashboard totals match the test records.
- Reports and PDFs download and match expected totals.
- Arabic and English UI can be switched.
- Mobile layout is usable for navigation, forms, and tables.
- Owner, manager, accountant, and caretaker permissions match the role model.
- Scheduler commands can be run manually on staging.
- Backup and restore rehearsal has completed successfully.

## Known Must-Fix Items Still Pending

- Dependency lockfiles and reproducible install policy.
- Secure authorized upload access for payment proofs and expense invoices.
- Authentication throttling for login and password reset.
- HTTPS/security headers/trusted proxy hardening.
