# Deployment Notes

## Recommended Environment

- Ubuntu 22.04 or 24.04 LTS
- Nginx
- PHP 8.3
- PHP-FPM
- PostgreSQL 14 or newer
- Redis for cache/queues, optional but recommended
- Supervisor for queue workers, if queues are added

PHP 8.3 is the supported deployment baseline. Local PHP 8.4 may run the project,
but Composer dependency resolution is constrained to PHP 8.3 compatibility.
Production and CI must satisfy `composer check-platform-reqs`.

## Server Setup Checklist

1. Install PHP extensions required by Laravel:
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
2. Create PostgreSQL database and user.
3. Configure `.env`.
4. Install Composer dependencies with the committed lockfile.
5. Build frontend assets with the committed npm lockfile.
6. Run migrations.
7. Configure the Laravel scheduler.

Operational deployment, backup, and incident procedures are maintained in:

- [Production Operations Runbook](PRODUCTION_OPERATIONS_RUNBOOK.md)
- [Backup And Recovery Runbook](BACKUP_AND_RECOVERY_RUNBOOK.md)
- [Incident Response Runbook](INCIDENT_RESPONSE_RUNBOOK.md)

## Deployment Commands

Typical deployment sequence:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Do not run `php artisan storage:link` for payment proofs or expense invoices.
Use it only if genuinely public assets are added later and require the public
disk.

## Real Pilot Onboarding

Use this sequence for the Abu Dhabi pilot or any real pilot database:

1. Keep the application unavailable publicly or behind restricted access.
2. Set all production environment values, including `REGISTRATION_ENABLED=false`.
3. Run `php artisan config:clear`.
4. Run `php artisan config:cache`.
5. Run migrations only: `php artisan migrate --force`.
6. Run `php artisan pilot:create-owner` from the trusted server console.
7. Verify owner login through restricted access.
8. Verify as a guest that GET and POST `/register` return 404.
9. Verify the health route at `/up`.
10. Only then expose the application to intended pilot users.
11. Keep `REGISTRATION_ENABLED=false`.

If an existing pilot owner loses access, run
`php artisan pilot:reset-owner-password {email}` from a trusted server console.
The new password is entered through hidden prompts only; never pass it in shell
history. Existing sessions may persist until expiry. This command does not
enable public registration.

Never run php artisan migrate:fresh, php artisan db:wipe, or demo seeders against the real pilot database.

Local demo environments may use `REGISTRATION_ENABLED=true` and
`php artisan migrate:fresh --seed`.

Automated tests should explicitly configure registration state and use an
isolated test database.

## File Storage

Private documents use the disk configured by `PRIVATE_DOCUMENTS_DISK`. Local
development defaults to `local`; production should use a private durable
S3-compatible disk such as `s3`.

For production:

- Set `PRIVATE_DOCUMENTS_DISK=s3` after configuring the private object-storage
  credentials and bucket.
- Serve payment proofs, expense invoices, and unit documents only through
  authorized application routes.
- Restrict access by organization and role.
- Do not expose this bucket publicly.
- Keep legacy local objects available until old rows have been retired or
  migrated; rows with no stored disk value still fall back to local storage.

Laravel Cloud and similar platforms have ephemeral application filesystems, so
production uploads must not depend on `storage/app/private` as the only durable
copy.

## Scheduler

The registered scheduled commands are:

- `contracts:expire:daily` daily at `00:30`.
- `payments:mark-overdue:daily` daily at `01:00`.

Reminder emails, summary emails, and temporary-file cleanup jobs are not
registered in code yet and should not be represented as active scheduler work.

The scheduled commands use `withoutOverlapping()` only. Do not add `onOneServer()` while `CACHE_STORE=file`. Closed beta must remain on one application replica until a shared cache such as database or Valkey/Redis is configured and tested.

Example cron:

```cron
* * * * * cd /var/www/property-manager && php artisan schedule:run >> /dev/null 2>&1
```

## Backups

Use the canonical [Backup And Recovery Runbook](BACKUP_AND_RECOVERY_RUNBOOK.md).

Back up:

- PostgreSQL database
- private document object storage
- `.env` secrets in secure secret storage

Recommended:

- database backups retained for at least 14 days during closed beta
- RPO target of 24 hours maximum until restore behavior is proven
- RTO target of 4 hours
- restore rehearsal at least monthly

Must verify Laravel Cloud serverless Postgres backups/PITR and object-storage recovery behavior. A redeploy is not a backup.

## Security Checklist

- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Use HTTPS only.
- Use strong database passwords.
- Restrict upload file types and storage access.
- Add complete authorization tests before launch.
- Configure error monitoring.

## Performance Notes

- Compile Tailwind with Vite instead of CDN.
- Add database indexes listed in database documentation.
- Cache config/routes/views in production.
- Paginate all large lists.
- Avoid generating very large PDFs synchronously; queue them later if needed.
