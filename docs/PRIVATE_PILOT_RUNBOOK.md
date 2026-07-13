# Private Pilot Runbook

This runbook is for a controlled private pilot of Property Manager with known users and real family-property data. It does not replace later production hardening for a public commercial launch.

Canonical production operations, recovery, and incident steps live in:

- [Production Operations Runbook](PRODUCTION_OPERATIONS_RUNBOOK.md)
- [Backup And Recovery Runbook](BACKUP_AND_RECOVERY_RUNBOOK.md)
- [Incident Response Runbook](INCIDENT_RESPONSE_RUNBOOK.md)

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

Create each real pilot owner as a separate organization:

```bash
php artisan pilot:create-owner
```

Repeat the command safely for owner #1, owner #2, and owner #3. Each run must
create one organization and one active owner user for that organization.

Pilot account rules:

- Never place real pilot users inside the demo organization.
- Keep `REGISTRATION_ENABLED=false`.
- Use the real pilot owner's organization name, owner name, and email.
- Enter the temporary password only through the hidden command prompt.
- Never place the password in shell history, tickets, screenshots, chat, logs, or
  repository files.
- Deliver the temporary password through a separate trusted channel from the
  production link.
- Require the owner operationally to change the temporary password immediately
  after first login from **Change password** inside the authenticated app.
- Use forgot-password only after SMTP delivery has been proven in the target
  environment.
- If email reset is unavailable, use `php artisan pilot:reset-owner-password`
  from a trusted console.

Verify the health endpoint:

```text
https://your-pilot-domain.example/up
```

Smoke-test owner login, password change, dashboard, building/unit/tenant creation, contract creation, payment recording, expense creation, reports, PDFs, Arabic/English switching, and mobile layout.

## First Three Owner Launch Checklist

Before inviting each of the first three real owners:

1. Confirm the latest deployment is healthy and `/up` returns success.
2. Confirm `php artisan operations:verify` passes.
3. Confirm `php artisan schedule:list` shows the expected daily scheduled tasks.
4. Confirm database backups are enabled with the closed-beta retention target.
5. Confirm private document storage is durable and not local.
6. Run `php artisan pilot:create-owner` once for that owner.
7. Confirm the new owner belongs to a new organization, not the demo organization.
8. Send the production link and temporary password through separate trusted channels.
9. Ask the owner to log in and immediately use **Change password**.
10. Ask the owner to use the in-app Feedback button for bugs, confusion, or suggestions.
11. Tell the owner that closed-beta support is limited and is not guaranteed 24/7.
12. Record the owner, organization, invitation time, and support contact channel in the operator's private pilot tracker.

## Scheduler

Must verify that the Laravel scheduler is enabled in the hosting environment. On a traditional server, add the scheduler cron:

```cron
* * * * * cd /var/www/property-manager/current && php artisan schedule:run >> /var/log/property-manager-scheduler.log 2>&1
```

Configured scheduled commands:

- `contracts:expire` runs daily at `00:30`.
- `payments:mark-overdue` runs daily at `01:00`.

The server timezone and `APP_TIMEZONE` must be aligned for the pilot. Use `Asia/Dubai` unless the pilot owner explicitly chooses another timezone.

Both scheduled commands are safe to rerun manually because they only update rows that still match their pending/current predicates. No queue worker is required while `QUEUE_CONNECTION=sync`.

Review `/var/log/property-manager-scheduler.log` after deployment and after the first overnight run.

Closed beta must remain on one application replica while `CACHE_STORE=file`. The scheduled commands use `withoutOverlapping()` only. Scaling above one replica requires a shared central cache and a separately tested `onOneServer()` change.

## HTTPS And Session Safety

- Install a valid HTTPS certificate before exposing the pilot.
- Use `SESSION_SECURE_COOKIE=true`.
- Use `SESSION_SAME_SITE=lax`.
- Use `SESSION_ENCRYPT=true`.
- Configure Nginx/PHP-FPM so Laravel receives the intended HTTPS URL and host.
- If the app is behind a proxy or load balancer, configure trusted proxy behavior at the web-server/infrastructure layer until Laravel proxy middleware is explicitly added in a later batch.
- Security headers such as CSP, HSTS, X-Frame-Options, X-Content-Type-Options, and Referrer-Policy are a separate hardening batch and are not implemented in this repository yet.

## Private Uploads

Payment proofs, expense invoices, and unit documents are stored on the disk
configured by `PRIVATE_DOCUMENTS_DISK`. Local development can use `local`, but a
real pilot on Laravel Cloud or another ephemeral-filesystem platform must use a
private durable object-storage disk such as `s3`.

Pilot rules:

- Set `PRIVATE_DOCUMENTS_DISK=s3` only after the private bucket and credentials
  are configured.
- Back up the private document bucket every day.
- Do not use `php artisan storage:link` to expose private uploads.
- Do not move private uploads to the public disk.
- Payment proofs are downloaded only through `payments.proof.download`.
- Expense invoices are downloaded only through `expenses.invoice.download`.
- Unit documents are downloaded only through `unit-documents.download`.
- The application authorizes the underlying payment or expense before checking
  stored path validity or file existence. Unit document downloads authorize the
  unit before checking storage.
- Existing payment and expense rows with no stored disk value are legacy local
  files and must remain restorable until they are retired or migrated.

Use `php artisan storage:link` only if the app later adds genuinely public
assets that require the public disk. It must not expose payment proofs or
expense invoices.

## Trusted Owner Recovery

If a pilot owner loses access and email recovery is unavailable, a trusted
server operator may reset the password from the server console:

```bash
php artisan pilot:reset-owner-password owner@example.com
```

Rules:

- Run it only from a trusted server console by an operator authorized to manage
  the private pilot.
- The command prompts for the new password using hidden input and asks for
  confirmation.
- Never supply the password as a command-line argument, paste it into shell
  history, write it in tickets, or log it.
- The command only resets an existing user whose role is `owner`.
- It writes a structured security notice with user ID and organization ID only;
  the log write is not a database row and does not provide transactional audit
  persistence.
- Existing sessions may persist until normal expiry because the current session
  architecture does not provide a safe targeted invalidation path.
- The command does not enable public registration.
- After a trusted reset, ask the owner to log in and use **Change password** if
  the reset password was temporary.

## Contract Termination

Contract termination behavior must be verified exactly as implemented:

- Payments due on or before the termination date remain collectible.
- Future unpaid payments become `cancelled`.
- Future partial payments remain `partial` and retain the actual paid income.

## Backup Plan

Use the canonical [Backup And Recovery Runbook](BACKUP_AND_RECOVERY_RUNBOOK.md).

Closed-beta targets:

- Keep database backups for at least 14 days.
- Recovery Point Objective (RPO): 24 hours maximum until restore behavior is proven.
- Recovery Time Objective (RTO): 4 hours.
- Run a restore rehearsal monthly and before major risky changes.
- Take a pre-deployment backup before destructive or high-risk migrations.

Must verify serverless Postgres backup/PITR behavior in Laravel Cloud. Object storage is durable, but independent object backup is not proven until configured and tested with the storage provider.

## Restore Rehearsal

Never rehearse restore directly on production.

Use staging:

1. Restore the latest PostgreSQL backup into a staging database.
2. Restore private document objects into the staging object-storage bucket or,
   for legacy local files, into staging `storage/app/private`.
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
- Contract can be terminated: due-on/before payments remain collectible, future
  unpaid payments become cancelled, and future partial payments remain partial.
- Dashboard totals match the test records.
- Reports and PDFs download and match expected totals.
- Arabic and English UI can be switched.
- Mobile layout is usable for navigation, forms, and tables.
- Owner, manager, accountant, and caretaker permissions match the role model.
- Scheduler commands can be run manually on staging.
- Backup and restore rehearsal has completed successfully.
- `php artisan operations:verify` passes in the production environment.

## Known Must-Fix Items Still Pending

- HTTPS/security headers/trusted proxy hardening.
