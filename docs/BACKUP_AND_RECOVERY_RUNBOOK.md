# Backup And Recovery Runbook

This runbook separates durability from recoverability.

- Durable storage means data is stored outside the application container.
- Database backup means a restorable copy of the database exists.
- Object backup means a restorable copy or version history of private documents exists.
- Restore means proving data can be recovered into a working environment.
- Redeployment means replacing application code. A redeploy is not a backup.
- Rollback means returning application code to a previous version. Database rollback is a separate decision.

## Closed-Beta Targets

- Database backup retention target: 14 days.
- RPO target: 24 hours maximum until restore behavior is proven.
- RTO target: 4 hours.
- Restore rehearsal: monthly and before major risky changes.
- Pre-deployment backup: required before destructive or high-risk migrations.

Must verify in Laravel Cloud:

- Serverless Postgres backup and point-in-time recovery behavior.
- Backup retention.
- Restore destination and steps.
- Who can trigger restore.
- Expected restore duration.

Object storage is durable, but no independent object backup is currently proven by this repository. Do not claim deleted private documents can be restored unless real object backup, versioning, or replication has been configured and tested.

Never delete the primary production database or primary private document bucket during recovery testing.

## Backup Inventory

Back up:

1. PostgreSQL database.
2. Private document object storage for payment proofs, expense invoices, and unit documents.
3. Production environment secrets in Laravel Cloud or another secure secret manager.
4. The deployed Git commit and build artifacts through normal deployment history.

Do not store credentials in this repository or in runbook screenshots.

## Restore Rehearsal

Use staging only:

1. Restore the latest database backup into a staging database.
2. Restore private documents into a staging bucket or verified staging storage location.
3. Point staging environment variables to the restored resources.
4. Run `php artisan operations:verify`.
5. Log in as the owner.
6. Verify buildings, units, tenants, contracts, payments, expenses, reports, PDFs, and private document downloads.
7. Record the restore start time, finish time, data timestamp, and manual fixes.

## Bad Deployment

1. Stop further deployments.
2. Preserve logs.
3. Roll back application code through Laravel Cloud deployment history if available.
4. Visit `/up`.
5. Run `php artisan operations:verify`.
6. Smoke-test owner login and critical workflows.

Data loss is usually not expected unless the bad deployment changed data.

## Bad Migration

1. Stop application writes if data or schema is affected.
2. Preserve logs and migration output.
3. Do not run blind `php artisan migrate:rollback` in production.
4. Restore from the pre-deployment database backup into staging first.
5. Decide whether production needs selective repair or full restore.

Data loss is possible up to the backup point.

## Accidental Database Deletion

1. Stop all writes.
2. Preserve logs and access records.
3. Restore from the latest Laravel Cloud database backup or PITR point into a safe destination.
4. Verify the restored data before redirecting production traffic.
5. Document the data-loss window.

Data loss is possible up to the RPO.

## Database Outage

1. Confirm the outage in Laravel Cloud.
2. Do not change application code first.
3. Preserve application logs.
4. Wait for provider recovery or restore to a replacement database if directed by the provider.
5. Run `php artisan operations:verify` after recovery.

Data loss depends on provider recovery and backup freshness.

## Missing Private Document

1. Confirm the user is authorized to access the record.
2. Confirm the application returns a missing-file response rather than a permission failure.
3. Stop replacing or deleting related documents until the cause is known.
4. Check whether object backup or versioning exists.
5. Restore only from a proven object backup or version.

If no object backup/version exists, the document may be unrecoverable.

## Broken Storage Environment Configuration

1. Stop uploads.
2. Preserve logs.
3. Compare Laravel Cloud environment variable names against `.env.production.example` without exposing values.
4. Revert to the last known-good storage configuration.
5. Run `php artisan operations:verify`.

Do not switch production to local private storage.

## Object-Storage Outage

1. Stop non-essential uploads.
2. Preserve logs.
3. Confirm provider status.
4. Avoid retry loops that create duplicate records.
5. Resume only after `php artisan operations:verify` passes.

Data loss is not expected if writes failed before records were saved, but interrupted replacement workflows must be reviewed.
