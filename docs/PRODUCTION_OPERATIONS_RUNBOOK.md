# Production Operations Runbook

This runbook is for the closed beta operator. It keeps checks simple, safe, and repeatable.

Do not change production settings while investigating a problem unless the step explicitly says to do so.

## Daily Quick Checks

1. Open GitHub Actions and confirm the latest `develop` workflow completed successfully.
2. Open Laravel Cloud and confirm the latest deployment is healthy.
3. Visit `/up` on the production URL.
4. Open production logs and check for new `error` or repeated `warning` entries.
5. Confirm recent uploads and downloads still work through the application.
6. Confirm the scheduler ran overnight.

## Weekly Checks

1. Run `php artisan operations:verify` from the Laravel Cloud Commands tab.
2. Run `php artisan schedule:list` and confirm:
   - `contracts:expire:daily` runs daily at `00:30`.
   - `payments:mark-overdue:daily` runs daily at `01:00`.
3. Confirm database backup status in Laravel Cloud.
4. Confirm private object-storage backup or versioning status with the storage provider.
5. Review unresolved warnings from the last week.

## Before Deployment

1. Confirm GitHub Actions passed on the commit being deployed.
2. Confirm no one is actively entering pilot data if the deployment includes risky changes.
3. Confirm a recent database backup exists.
4. Confirm private document object storage has a current recovery path.
5. Do not run `migrate:fresh`, `db:wipe`, or demo seeders against production.

## After Deployment

1. Confirm Laravel Cloud reports the deployment as successful.
2. Visit `/up`.
3. Run `php artisan operations:verify`.
4. Run `php artisan schedule:list`.
5. Log in as the owner and check the dashboard.
6. Upload and download one test private document only if it belongs to a safe test record.
7. Check production logs for new errors.

## GitHub Actions

1. Open the repository in GitHub.
2. Select **Actions**.
3. Open the latest workflow for the deployed branch.
4. Confirm tests and build completed successfully.
5. Stop if the deployed commit does not match the passing workflow.

## Laravel Cloud Deployment

Must verify in Laravel Cloud:

1. The deployed branch and commit are expected.
2. Environment variables are present without exposing their values.
3. `QUEUE_CONNECTION=sync`.
4. `CACHE_STORE=file`.
5. `PRIVATE_DOCUMENTS_DISK` is not `local` in production.
6. The scheduler is enabled and visible in logs.

## Safe Commands

Run these from the Laravel Cloud Commands tab or a trusted server console:

```bash
php artisan operations:verify
php artisan schedule:list
php artisan contracts:expire
php artisan payments:mark-overdue
```

Normal success:

- `operations:verify` prints only `PASS` lines.
- `contracts:expire` prints inspected, expired, skipped, and failed counts.
- `payments:mark-overdue` prints affected count and `status: complete`.

Stop and investigate if any command prints `FAIL`, returns a non-zero exit code, or logs repeated warnings.

## Logs

Use Laravel Cloud log viewing first. Do not rely on files inside the application container as the only log source.

Look for:

- scheduled command failures,
- storage verification failures,
- database connection failures,
- repeated private document delete warnings.

The app should not log credentials, bucket names, database names, object keys, tenant names, phone numbers, or uploaded-file contents.

## Scheduler And Replica Rule

Closed beta rule: keep one application replica while `CACHE_STORE=file`.

The scheduled commands use `withoutOverlapping()` to avoid local overlap on one replica. Do not add `onOneServer()` while the cache is file-based.

On Laravel Cloud, scheduler overlap locks currently use the database-backed environment cache. The `cache` and `cache_locks` tables are required for `php artisan schedule:list` and scheduled tasks while `withoutOverlapping()` is enabled. Do not delete these tables.

Scaling above one replica requires a separate tested change:

1. Move cache to a shared central store such as database or Valkey/Redis.
2. Add `onOneServer()` to scheduled maintenance tasks.
3. Verify scheduler behavior under more than one replica.
4. Update this runbook after testing.

## When To Stop

Stop making changes and preserve logs if:

- backups cannot be confirmed,
- private storage verification fails,
- a deployment changed data unexpectedly,
- a user reports seeing another organization's data,
- the scheduler repeatedly fails,
- production environment values are unclear.
