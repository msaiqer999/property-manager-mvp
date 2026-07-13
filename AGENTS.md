# AGENTS.md

Operating constitution for Codex and other coding agents working in this repository.

Canonical references:

- [README](README.md)
- [Platform Architecture](docs/platform-architecture.md)
- [Payment Domain Boundaries](docs/payment-domain-boundaries.md)
- [Package Entitlements Strategy](docs/package-entitlements-strategy.md)
- [Domain Glossary](docs/domain-glossary.md)
- [Workspace vs Property Owner](docs/workspace-vs-property-owner.md)
- [Private Pilot Runbook](docs/PRIVATE_PILOT_RUNBOOK.md)
- [Production Operations Runbook](docs/PRODUCTION_OPERATIONS_RUNBOOK.md)
- [Backup And Recovery Runbook](docs/BACKUP_AND_RECOVERY_RUNBOOK.md)
- [Incident Response Runbook](docs/INCIDENT_RESPONSE_RUNBOOK.md)
- [Known Limitations](docs/known-limitations.md)
- [Business Logic](docs/business-logic.md)
- [CI Workflow](.github/workflows/ci.yml)

## 1. Project Identity

This repository is Property Manager MVP: a Laravel 12, PHP 8.3 baseline, PostgreSQL, Blade, Tailwind CSS through Vite, mobile-first PWA application.

It is a closed-beta SaaS for small property owners. The current market focus is the Basic Owner closed beta. The current deployment target is Laravel Cloud.

## 2. Current Product Scope

Implemented core scope includes organizations and users, buildings and units, tenants, long-term contracts, payment schedules and rent records, overdue tracking, expenses, reports, private documents, roles and permissions, activity logs, localization and RTL, Quick Start, Pilot Guide, beta feedback, and production operations and recovery.

Future modules are planned, but they must not be implemented without an approved sprint.

## 3. Mandatory Workflow Rules

- Work on `develop` unless an explicit instruction says otherwise.
- Never modify `main` directly.
- Never commit, push, merge, rebase, or open a pull request unless explicitly requested.
- Always verify repository, branch, tracking branch, HEAD, and working-tree status before changes.
- Stop when unrelated or unexpected changes exist.
- Preserve valid partial work after interruptions.
- Do not duplicate migrations, files, services, tests, or helpers.
- Read-only audit instructions must not modify any file.
- Do not expand the sprint scope.
- Do not perform production actions.
- Never expose or request secrets.
- Never run destructive commands against a real database.

Commands that must not be run against real pilot or production data:

```bash
php artisan migrate:fresh
php artisan db:wipe
php artisan db:seed
```

## 4. Architectural Guardrails

- `Organization` is the workspace, security, and future billing boundary.
- User role `owner` means account administrator, not necessarily legal property owner.
- Organization type is separate from user role.
- Legal owner/client and portfolio entities are future concepts.
- Organization isolation is mandatory in queries, policies, controllers, reports, downloads, and tests.
- Long-term rental and short-stay rental are separate domains.
- A booking must never be modeled as a short contract.
- Rent payments, future booking payments, and SaaS subscription billing are separate financial domains.
- The platform records rent collections but does not currently process, hold, split, or distribute rent money.
- Revenue model is fixed subscriptions and clear module upgrades.
- No commission from rent, booking income, maintenance jobs, or cleaning jobs.
- Future packages must use capabilities and limits, not hard-coded commercial package names.
- Avoid speculative tables, abstractions, and unused future infrastructure.
- Preserve mobile-first, PWA, Capacitor readiness, and future API compatibility.

## 5. Security And Privacy

- Every user must access only their organization's data.
- Authorization must be enforced in backend code.
- Sensitive actions require policies or equivalent authorization.
- Important changes should remain auditable.
- Private files must use the configured private document disk.
- Production private documents must use durable S3-compatible storage.
- Never create public object URLs for private documents.
- Never use `storage:link` for payment proofs, expense invoices, or unit documents.
- Downloads must pass through authorized application routes.
- Do not log credentials, private paths, file contents, phone numbers, or sensitive personal data.
- Never commit `.env` files or real secrets.

## 6. Database And Financial Integrity

- Use transactions for multi-record financial and contract operations.
- Use locking where concurrent updates can corrupt state.
- Preserve idempotency for scheduled commands and retried operations.
- Migrations must be backward-compatible whenever possible.
- Never create destructive migrations without explicit approval and a recovery plan.
- Expense voiding and financial-history preservation must remain intact.
- Do not silently delete historical financial records.

Commands that must not be run against real pilot or production data:

```bash
php artisan migrate:fresh
php artisan db:wipe
php artisan db:seed
```

## 7. Storage Rules

- `PRIVATE_DOCUMENTS_DISK` is the canonical disk for new sensitive uploads.
- Null legacy payment and expense disk values fall back to local storage.
- Existing `UnitDocument` rows continue using their stored disk.
- New paths must be organization- and entity-scoped.
- Original filenames must not control storage paths.
- Replacement must upload first, persist safely, then delete the old file.
- Failed database persistence must clean up newly uploaded objects.
- Durable object storage is not automatically an independent backup.

## 8. Scheduler And Production Operations

Scheduled commands:

- `payments:mark-overdue` at `01:00`
- `contracts:expire` at `00:30`

Rules:

- Scheduled commands use `withoutOverlapping`.
- Do not add `onOneServer` until a shared central cache and multiple replicas are approved.
- `cache` and `cache_locks` tables are required for scheduler locks.
- Closed beta should remain on one app replica unless architecture is updated.
- Queue remains `sync` unless a real async requirement is approved.
- `operations:verify` is the safe non-destructive production verification command.
- Public `/up` must remain minimal and must not expose infrastructure details.
- Production database backup retention target is 14 days.
- RPO target is 24 hours.
- RTO target is 4 hours.

Safe verification command:

```bash
php artisan operations:verify
```

## 9. Coding Standards

- Make the smallest correct change.
- Follow existing Laravel-native patterns.
- Prefer clear application services/support classes only when they reduce real duplication.
- Do not create generic frameworks for speculative future needs.
- Preserve existing routes and public behavior unless the sprint requires a change.
- Preserve English/Arabic localization and RTL behavior.
- Use `bdi` or LTR direction where needed for dates, IDs, emails, phone numbers, and technical values.
- Keep status colors semantic.
- Preserve the approved warm professional visual identity.
- Do not modify frontend files unless the sprint requires frontend work.
- All text files must be UTF-8 without BOM and LF line endings.

## 10. Testing Rules

Before reporting completion, run focused tests for changed behavior first, then run:

```bash
php artisan test
```

When frontend files change, also run:

```bash
npm.cmd run build
```

Do not run `npm` build when no frontend file changed.

Tests must use isolated databases and `Storage::fake` where appropriate. Tests must cover organization isolation and authorization for sensitive workflows. Do not weaken tests merely to make them pass. Fix production behavior when it is wrong. Update a test only when the assertion is demonstrably stale or brittle. Avoid assertions tied to irrelevant whitespace or broad translated substrings.

## 11. Production Safety

- Codex must not change Laravel Cloud settings.
- Codex must not run production migrations.
- Codex must not delete production records or files.
- Codex must not restore or delete databases.
- Codex must not rotate or expose credentials.
- Production actions require explicit human approval and manual verification.
- A successful local test does not prove production readiness.
- Production verification must follow the runbooks.

## 12. Interruption And Connectivity Resilience

- The project owner may experience electricity and internet interruptions.
- Keep work resumable.
- Inspect current partial state before restarting interrupted work.
- Do not duplicate migrations or implementation after reconnecting.
- Prefer short checkpoints for long workflows.
- Clearly state the safe resume point in final reports.

## 13. User-Facing Command Formatting

- Put every command the user must copy inside a fenced code block.
- Do not mix executable commands with explanatory text inside the same code block.
- Give steps sequentially.
- Avoid unnecessary technical complexity.
- Clearly identify commands that must not be run in production.

## 14. Final Report Requirements

Every implementation report must state:

- Verified repository and branch.
- Exact files changed.
- Migration created or confirmation none was created.
- Business and security behavior changed.
- Focused test results.
- Full test-suite result.
- Frontend build result or why it was not required.
- Line-ending verification when relevant.
- Confirmation no credentials were added.
- Confirmation no production setting changed.
- Confirmation no commit or push occurred.
- Remaining manual production steps.
- Remaining risks or unresolved human decisions.
- Safe resume point.
