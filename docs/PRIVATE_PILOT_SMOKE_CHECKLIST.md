# Private Pilot Smoke Checklist

Use this checklist on staging before live pilot use and after each deployment.

- `/up` health route returns healthy.
- Login succeeds for an active owner.
- Dashboard loads and totals match known test records.
- Building, unit, tenant, and contract flows work end to end.
- Payment recording works and an uploaded proof downloads through the authorized
  payment proof route.
- Expense recording works and an uploaded invoice downloads through the
  authorized expense invoice route.
- Reports and PDF exports open for authorized roles.
- Arabic and English switching works, including RTL layout.
- Role restrictions match owner, manager, accountant, and caretaker permissions.
- Cross-organization record and document access is denied.
- `php artisan payments:mark-overdue` runs safely on staging.
- `php artisan contracts:expire` runs safely on staging.
- `php artisan pilot:reset-owner-password {email}` is rehearsed only against a
  non-production owner account, with the password entered through hidden prompts.
- Database backup evidence is recorded.
- Private upload backup evidence is recorded.
- Restore rehearsal evidence is recorded, including duration and any manual
  fixes.
