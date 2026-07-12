# Main Business Logic

## Organization Scoping

Every logged-in user belongs to one organization. Most queries use
`auth()->user()->organization_id` to restrict data.

Important patterns:

- Buildings are filtered by `organization_id`.
- Tenants, contracts, payments, expenses, reports, users, and activity logs are
  filtered by `organization_id`.
- Units are filtered through their building relationship.

## Contract Creation

When a contract is created:

1. Request data is validated.
2. Tenant and unit ownership are checked against the current organization.
3. The contract is created inside a transaction.
4. Payment schedule rows are generated.
5. The related unit is marked as `rented`.
6. Activity is logged.

Main files:

- `app/Http/Controllers/ContractController.php`
- `app/Support/PaymentSchedule.php`

## Payment Schedule Generation

`PaymentSchedule::createFor()` generates payment rows from:

- `start_date`
- `end_date`
- `rent_amount`
- `payment_frequency`

Frequency mapping:

- monthly: every 1 month, amount = monthly rent
- quarterly: every 3 months, amount = rent x 3
- semi_annual: every 6 months, amount = rent x 6
- annual: every 12 months, amount = rent x 12

Duplicate protection:

- Schedule generation returns early if payments already exist.
- Schedule replacement only runs if no payment has been recorded yet.

Important limitation:

- The current schedule assumes full periods. It does not prorate partial months.

## Payment Recording

When recording a payment:

1. User must have `record-payment`.
2. Payment must belong to the user organization.
3. `amount_paid` is validated and capped at `amount_due`.
4. Optional proof image is stored on the configured private document disk.
5. Status is calculated:
   - `paid` when amount paid is equal to amount due
   - `partial` when some amount is paid before due date
   - `overdue` when unpaid and due date is in the past
   - `pending` when unpaid and not overdue
6. Activity is logged.

Payment proof files use app-generated private keys under
`organizations/{organization_id}/payments/{payment_id}/proofs`. Downloads are
served through authorized application routes. Older rows with no stored disk
value are treated as legacy local private files.

## Expenses

Expenses are attached to:

- organization
- building
- optional unit
- creator user

Building and optional unit are checked to ensure they belong to the current
organization.

Expense invoices use the same private document disk strategy as payment proofs,
with keys under `organizations/{organization_id}/expenses/{expense_id}/invoices`.
Invoice downloads authorize the expense before checking file existence.

## Dashboard Numbers

The dashboard displays:

- Current month income from paid payments by `payment_date`
- Current month expenses by `expense_date`
- Net profit as income minus expenses
- Overdue amount based on due date and unpaid balance
- Vacant unit count
- Rented unit count
- Contracts ending within 45 days
- Latest payments
- Latest expenses

## Reports

Reports currently support:

- Building income
- Unit statement
- Expenses
- Overdue payments
- Net profit
- Monthly summary

PDF export uses Laravel DomPDF.

Important note:

Reports are MVP-level summaries. A professional developer should verify the
financial definitions with the product owner before production use.

## Activity Logs

Activity logs are written through `App\Services\ActivityLogger`.

Currently logged examples:

- building created/updated/deleted
- unit created/updated/deleted/status changed
- tenant created/updated
- contract created/updated
- payment recorded
- expense created/updated
