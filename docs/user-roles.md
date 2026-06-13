# User Roles And Permissions

Roles are implemented in `App\Enums\Role` and enforced through
`App\Http\Middleware\EnsureAbility` plus additional controller checks.

## Roles

### Owner

Full access.

Can:

- Manage buildings
- Manage units
- Manage tenants
- Manage contracts
- Record payments
- Manage expenses
- View and export reports
- Invite and edit users
- View activity logs
- Delete critical records

### Manager

Operational access.

Can:

- Manage buildings and units
- Manage tenants
- Manage contracts
- View and record payments
- Manage expenses
- View reports

Should not:

- Manage ownership or billing
- Manage users
- Delete critical records

### Accountant

Financial access.

Can:

- View payments
- Record payments
- View expenses
- View and export reports

Cannot:

- Edit contracts
- Delete data
- Manage users

### Caretaker

Limited field access.

Can:

- View payments
- Record received payments
- Upload payment proof
- Record payment notes

Cannot:

- View profit reports
- Edit contracts
- Manage buildings, units, tenants, or expenses
- Delete data

## Current Permission Design

Abilities are strings such as:

- `manage-properties`
- `manage-tenants`
- `manage-contracts`
- `view-payments`
- `record-payment`
- `view-expenses`
- `manage-expenses`
- `view-reports`
- `manage-users`

Routes are grouped by ability in `routes/web.php`.

## Handover Recommendation

For a production-grade version, replace the enum-only approach with one of:

- Laravel Policies and Gates per model
- Spatie Laravel Permission for database-managed roles and permissions

Add feature tests for every role against every sensitive route.
