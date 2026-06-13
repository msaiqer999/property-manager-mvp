# Deployment Notes

## Recommended Environment

- Ubuntu 22.04 or 24.04 LTS
- Nginx
- PHP 8.2 or newer
- PHP-FPM
- PostgreSQL 14 or newer
- Redis for cache/queues, optional but recommended
- Supervisor for queue workers, if queues are added

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
4. Install Composer dependencies.
5. Build frontend assets if Vite is configured.
6. Run migrations.
7. Link storage.
8. Configure queue and scheduler if background jobs are added.

## Deployment Commands

Typical deployment sequence:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If using Vite:

```bash
npm ci
npm run build
```

## File Storage

Current application stores uploaded proofs and invoices on the local private disk.

For production:

- Prefer private storage.
- Serve files through signed routes.
- Restrict access by organization and role.
- Consider S3-compatible object storage.

## Scheduler

Add Laravel Scheduler for:

- Marking payments overdue daily.
- Sending contract ending reminders.
- Sending overdue payment summaries.
- Cleaning old temporary files.

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
- Add rate limiting to login and password reset routes.
- Add complete authorization tests before launch.
- Configure error monitoring.

## Performance Notes

- Compile Tailwind with Vite instead of CDN.
- Add database indexes listed in database documentation.
- Cache config/routes/views in production.
- Paginate all large lists.
- Avoid generating very large PDFs synchronously; queue them later if needed.
