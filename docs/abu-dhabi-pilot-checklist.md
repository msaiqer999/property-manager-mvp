# Abu Dhabi Pilot Checklist

This checklist defines what should be true before using Property Manager / المدير العقاري in a controlled Abu Dhabi MVP pilot.

## Pilot Positioning

The pilot should target:

- Individual landlords.
- Family property owners.
- Small owner-managed teams.

The pilot should not target:

- Open brokers.
- Public property listing businesses.
- Real estate sales teams.
- Large property management companies with complex owner accounting.
- Daily rental operators.

## Product Scope for Pilot

Pilot scope:

- Buildings.
- Units.
- Tenant records.
- Long-term contracts.
- Rent payment schedules.
- Payment recording.
- Overdue tracking.
- Expenses.
- Dashboard.
- Reports.
- PDF exports.
- Team roles.
- Activity logs.

Tenants should remain records only. They should not receive accounts during the pilot.

## Security Readiness

Before pilot:

- GitHub Actions should be green.
- Role restriction tests should pass.
- Cross-organization isolation tests should pass.
- PDF export tests should pass.
- File upload paths should be reviewed.
- Owner-only user management should be verified.
- Activity logs should be reviewed for sensitive data exposure.

## Operational Readiness

Before pilot:

- Confirm Arabic and English usability.
- Confirm mobile browser usability.
- Confirm dashboard numbers match seeded and manual test data.
- Confirm reports match expected calculations.
- Confirm contract payment schedules are generated correctly.
- Confirm overdue payment logic.
- Confirm expense reporting.

## Manual Browser QA

Test these screens on desktop and mobile:

- Login.
- Dashboard.
- Buildings.
- Units.
- Tenants.
- Contracts.
- Payments.
- Expenses.
- Reports.
- Users.
- Activity logs.
- PDF downloads.
- File uploads.

## Known Pilot Boundaries

The pilot should not include:

- Real online payment processing.
- Public listings.
- Broker marketplace.
- Maintenance marketplace.
- Tenant portal.
- Daily rental booking.
- AI analytics.
- Sales workflows.

## Feedback To Collect

During the pilot, collect feedback on:

- Ease of adding properties and units.
- Ease of recording tenants and contracts.
- Payment tracking accuracy.
- Overdue payment visibility.
- Expense tracking usefulness.
- Report clarity.
- Mobile usability.
- Arabic wording.
- Team role fit.
- Missing daily operational workflows.

## Pilot Readiness Statement

This checkpoint is suitable for controlled MVP pilot testing in Abu Dhabi with known users and no real online payment processing, provided manual browser QA and deployment checks are completed before live use.
