# Laravel Cloud Demo Deployment

This guide prepares a private Indonesia Closed Pilot visual demo on Laravel Cloud from the `develop` branch.

Use this only for a temporary demo database for Indonesian contacts. Do not use these demo seeders on a real pilot database that contains landlord data.

## 1. Before You Deploy

1. Confirm the deployed branch is `develop`.
2. Confirm local verification passed:

```bash
php artisan test
```

```bash
npm.cmd run build
```

3. In Laravel Cloud, create or select the demo project.
4. Attach a PostgreSQL database for the demo.
5. Keep the app private or share the URL only with intended testers.

## 2. Required Environment Variables

Set these in Laravel Cloud before running migrations.

```env
APP_NAME="Property Manager"
APP_ENV=production
APP_KEY=base64:REPLACE_WITH_GENERATED_APP_KEY
APP_DEBUG=false
APP_URL=https://your-laravel-cloud-demo-url
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_FALLBACK_CURRENCY_CODE=
REGISTRATION_ENABLED=false

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=REPLACE_WITH_LARAVEL_CLOUD_DATABASE_HOST
DB_PORT=5432
DB_DATABASE=REPLACE_WITH_LARAVEL_CLOUD_DATABASE_NAME
DB_USERNAME=REPLACE_WITH_LARAVEL_CLOUD_DATABASE_USER
DB_PASSWORD=REPLACE_WITH_LARAVEL_CLOUD_DATABASE_PASSWORD

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_ENCRYPT=true

CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
PRIVATE_DOCUMENTS_DISK=local
UNIT_DOCUMENTS_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
```

Notes:

- Generate `APP_KEY` with Laravel Cloud or `php artisan key:generate --show`.
- Keep `APP_ENV=production` and `APP_DEBUG=false` for the shared demo.
- Keep `REGISTRATION_ENABLED=false` so visitors cannot create new organizations during the visual demo.
- `CACHE_STORE=database` is suitable for Laravel Cloud scheduler overlap locks because this repository includes the `cache` and `cache_locks` migrations.
- Keep `QUEUE_CONNECTION=sync`; no queue worker is required for this demo.
- Use one application replica for the closed demo unless shared-cache and scheduler scaling are separately approved.

## 3. Database Setup

Use a new empty PostgreSQL database for this demo. Do not point the demo deployment at a real pilot or production database.

The migration creates the application tables, global readiness tables, and cache lock tables required by scheduled tasks.

Run:

```bash
php artisan migrate --force
```

Do not run these commands on a real pilot or production database:

```bash
php artisan migrate:fresh
php artisan db:wipe
```

## 4. Seed The Demo Data

Run these commands after the migration:

```bash
php artisan db:seed --class=GlobalReadinessSeeder
```

```bash
php artisan db:seed --class=IndonesiaClosedPilotSeeder
```

The Indonesia demo seeder creates:

- Kos Putri Surabaya with 18 rooms
- Kontrakan Keluarga Malang with 6 units
- Ruko Sidoarjo with 4 units
- Gudang kecil Gresik with 2 units

It also creates sample tenants, contracts, payments, and expenses for a visual walkthrough.

## 5. Demo Login Credentials

Use these only for the temporary demo environment:

```text
Owner email: indonesia-owner@example.com
Password: password
```

```text
Manager email: indonesia-manager@example.com
Password: password
```

After the private review, either delete the demo environment or reset these passwords before any wider sharing.

## 6. Storage And Upload Limitations

For a visual demo, `PRIVATE_DOCUMENTS_DISK=local` is acceptable only if testers do not rely on uploaded files surviving redeploys, restarts, or rebuilds.

Laravel Cloud application filesystems can be ephemeral. Payment proofs, expense invoices, and unit documents stored on local disk may disappear after redeployment.

If testers must verify uploads, configure private S3-compatible storage and set:

```env
PRIVATE_DOCUMENTS_DISK=s3
UNIT_DOCUMENTS_DISK=s3
AWS_ACCESS_KEY_ID=REPLACE_WITH_PRIVATE_STORAGE_ACCESS_KEY
AWS_SECRET_ACCESS_KEY=REPLACE_WITH_PRIVATE_STORAGE_SECRET_KEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=REPLACE_WITH_PRIVATE_DEMO_BUCKET
AWS_ENDPOINT=REPLACE_WITH_S3_COMPATIBLE_ENDPOINT
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Do not use `php artisan storage:link` for payment proofs, expense invoices, or unit documents.

## 7. After Deployment Checks

1. Open the Laravel Cloud deployment page and confirm the deployed commit is the expected `develop` commit.
2. Visit `/up`.
3. Run:

```bash
php artisan operations:verify
```

4. Run:

```bash
php artisan schedule:list
```

5. Confirm `schedule:list` does not fail with a `cache_locks` error.
6. Log in as `indonesia-owner@example.com`.
7. Confirm the dashboard opens in Bahasa Indonesia.
8. Confirm dashboard values show `IDR` without unnecessary `.00` decimals.
9. Open Payments and confirm amounts show `IDR`, for example `IDR 955,000 / IDR 955,000`.
10. Open Units and Reports and confirm IDR formatting remains consistent.
11. Check Laravel Cloud logs for new errors.

## 8. What This Demo Is Not

This deployment is not a public Indonesia launch and not a real paid pilot database.

Do not add:

- payment gateway processing,
- AI features,
- public property listings,
- broker workflows,
- maintenance marketplace workflows,
- rent commission logic.

The demo message remains:

- Kami tidak mengambil bagian dari uang sewa Anda.
- Kami membantu Anda mengelola properti Anda.
