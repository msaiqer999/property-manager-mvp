# Authorization-hardened MVP checkpoint

## Summary

This checkpoint documents the authorization hardening completed for the Laravel
Property Manager MVP.

Implemented authorization layers:

- Route middleware protects broad application areas.
- The `Role` enum defines legacy coarse abilities.
- Laravel Policies protect Eloquent model resources.
- Support authorization classes protect non-model areas.
- Manual organization checks remain in place as defense-in-depth.
- Security tests cover role restrictions and organization isolation.
- GitHub Actions CI is green for the current checkpoint.

## Implemented Policies

- `PaymentPolicy`
- `ExpensePolicy`
- `BuildingPolicy`
- `UnitPolicy`
- `TenantPolicy`
- `ContractPolicy`
- `UserPolicy`
- `ActivityLogPolicy`

## Support Authorization Classes

- `ReportAuthorization`
- `DashboardAuthorization`

## Protected Areas

- Buildings
- Units
- Tenants
- Contracts
- Payments
- Expenses
- Reports
- Users
- Activity logs
- Dashboard
- PDFs
- File uploads
- Cross-organization protection
- Role restrictions

## Remaining Gaps

- Auth/register flow needs deeper security review.
- Login throttling and password reset tests are not yet covered.
- PostgreSQL CI is not yet configured.
- Deployment checklist still needs final production/pilot validation.
- Manual browser and mobile QA still need to be completed.
- File upload hardening is still basic.
- No global organization scope is implemented yet.

## Pilot Readiness

This checkpoint is suitable for controlled MVP pilot testing with known users
and no real online payment processing.

## Recommended Next Phase

- Form Requests
- PostgreSQL CI
- Deployment checklist
- Manual browser/mobile QA
- File upload hardening
