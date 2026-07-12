# Incident Response Runbook

Use this when something may have harmed data, access, privacy, or production availability.

## Severity Levels

- SEV-1: possible cross-organization data exposure, production data loss, credential leak, or total outage.
- SEV-2: major workflow unavailable, storage failure, failed deployment with user impact, or scheduler stopped for more than one day.
- SEV-3: isolated user issue, recoverable command failure, or warning that has not affected users.

## First Actions

1. Stop the bleeding before fixing root cause.
2. Preserve logs.
3. Do not delete evidence.
4. Do not rotate or edit multiple settings at once.
5. Write down times, affected users, affected organizations, and actions taken.
6. Inform affected pilot users when appropriate.

## Credentials

Rotate credentials only through Laravel Cloud or the relevant provider console.

Never paste secrets into chat, tickets, screenshots, logs, commits, or runbooks.

After rotation:

1. Redeploy or restart if Laravel Cloud requires it.
2. Run `php artisan operations:verify`.
3. Verify affected workflows.

## Compromised User

1. Disable the affected user if access is unsafe.
2. Reset the password through the normal reset flow or trusted owner recovery if appropriate.
3. Review activity logs and recent data changes.
4. Preserve application logs.
5. Notify affected pilot users if data may have been viewed or changed.

## Lost Owner Access

1. Confirm the request comes from the real pilot owner.
2. Run from a trusted console:

```bash
php artisan pilot:reset-owner-password owner@example.com
```

3. Enter the new password only through the hidden prompt.
4. Do not place the password in shell history or tickets.
5. Ask the owner to log in and change the password if needed.

## Possible Organization-Data Exposure

1. Treat as SEV-1.
2. Stop deployments and preserve logs.
3. Disable affected users if access is ongoing.
4. Capture exact URLs, account IDs, times, and screenshots if available.
5. Review authorization tests before releasing a fix.
6. Inform affected pilot users when appropriate.

## Broken Deployment

1. Stop additional deployments.
2. Preserve deployment and application logs.
3. Roll back through Laravel Cloud if safe.
4. Run `php artisan operations:verify`.
5. Smoke-test login, dashboard, private documents, and scheduler list.

## Scheduler Stopped

1. Confirm scheduler status in Laravel Cloud.
2. Run `php artisan schedule:list`.
3. Run safe manual commands if needed:

```bash
php artisan contracts:expire
php artisan payments:mark-overdue
```

4. Check logs for non-zero exits.
5. Keep one application replica while `CACHE_STORE=file`.

## Storage Failure

1. Stop document uploads and replacements.
2. Preserve logs.
3. Run `php artisan operations:verify`.
4. Verify `PRIVATE_DOCUMENTS_DISK` is not local in production.
5. Check object-storage provider status.
6. Restore missing objects only from a proven backup or version.

## Database Failure

1. Stop writes if data integrity is uncertain.
2. Preserve logs.
3. Verify Laravel Cloud database status.
4. Use the backup and recovery runbook before any restore.
5. Run `php artisan operations:verify` after recovery.
