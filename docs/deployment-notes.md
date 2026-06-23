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

Current application stores uploaded proofs and invoices on the local private disk.

For production:

- Prefer private storage.
- Serve payment proofs and expense invoices only through authorized application
  routes.
- Restrict access by organization and role.
- Consider S3-compatible object storage.

## Scheduler

The registered scheduled commands are:

- `contracts:expire` daily at `00:30`.
- `payments:mark-overdue` daily at `01:00`.

Reminder emails, summary emails, and temporary-file cleanup jobs are not
registered in code yet and should not be represented as active scheduler work.

Example cron:

```cron
* * * * * cd /var/www/property-manager && php artisan schedule:run >> /dev/null 2>&1
```

## Backups

Back up:

- PostgreSQL database
- uploaded files
- `.env` secrets in secure secret storage

Recommended:

- daily database backups
- weekly full backups
- restore test at least monthly

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
