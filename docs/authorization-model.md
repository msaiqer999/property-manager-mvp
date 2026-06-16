# Authorization Model

Property Manager / المدير العقاري uses layered authorization to protect workspace data, role permissions, financial information, PDF exports, uploads, and activity logs.

## Authorization Layers

The current authorization approach uses:

- Route middleware.
- Role enum permissions.
- Laravel Policies.
- Support authorization classes for non-model screens.
- Manual organization checks.
- Security coverage tests.
- GitHub Actions CI.

These layers intentionally overlap. The duplication is acceptable during the MVP pilot because it provides defense in depth while the authorization model continues to mature.

## Workspace Isolation

The current `organization_id` is the workspace isolation key.

Users should only access data that belongs to their own workspace. For models without a direct `organization_id`, isolation must be enforced through related models.

Example:

- Units are isolated through `Unit -> Building -> organization_id`.

## Current Roles

Current roles:

- Owner.
- Manager.
- Accountant.
- Caretaker.

The owner is the head of the workspace and should control team access.

## Current Policy Coverage

Implemented model policies:

- `PaymentPolicy`
- `ExpensePolicy`
- `BuildingPolicy`
- `UnitPolicy`
- `TenantPolicy`
- `ContractPolicy`
- `UserPolicy`
- `ActivityLogPolicy`

## Current Support Authorization Classes

Implemented support authorization classes:

- `ReportAuthorization`
- `DashboardAuthorization`

These are used because reports and dashboard screens are not normal Eloquent model resources.

## Protected Areas

The authorization model protects:

- Buildings.
- Units.
- Tenants.
- Contracts.
- Payments.
- Expenses.
- Reports.
- Users.
- Activity logs.
- Dashboard.
- PDF exports.
- File uploads.
- Cross-organization access.
- Role restrictions.

## Stage 1 Team Management Direction

Stage 1 should support owner-managed teams:

- Owner.
- Family member.
- Business manager.
- Accountant.
- Caretaker or guard.
- Trusted third party.

Recommended future hardening:

- Add user activation/deactivation.
- Prevent disabling or removing the last owner.
- Preserve activity logs after user deactivation or removal.
- Consider invitations instead of direct manual account creation.
- Consider more granular permissions only after the MVP pilot proves role needs.

## What Should Not Be Added Yet

Do not add:

- Tenant accounts.
- Public broker access.
- Open marketplace permissions.
- Sales permissions.
- Payment gateway permissions.
- Daily rental roles.

Those belong to later stages.
