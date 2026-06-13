# Database Structure

This MVP uses a simple organization-scoped schema. Most business records include
`organization_id` directly. `units` inherit organization ownership through their
parent `building`.

## Tables

### organizations

Represents one property owner/company account.

- `id`
- `name`
- timestamps

Relationships:

- has many users
- has many buildings
- has many tenants
- has many contracts
- has many payments
- has many expenses
- has many activity logs

### users

Application users who belong to an organization.

- `id`
- `organization_id`
- `name`
- `email`
- `password`
- `role`: owner, manager, accountant, caretaker
- authentication/session fields
- timestamps

Notes:

- Email is globally unique.
- Passwords are hashed by Laravel model casting and explicit hashing in seeders.

### buildings

Top-level property asset.

- `id`
- `organization_id`
- `name`
- `location`
- `description`
- soft deletes
- timestamps

Relationships:

- belongs to organization
- has many units
- has many expenses

### units

Rentable units inside a building.

- `id`
- `building_id`
- `unit_number`
- `type`: apartment, shop, office, warehouse, villa, chalet, other
- `size`
- `rooms`
- `status`: vacant, rented, maintenance
- `rent_amount`
- `notes`
- soft deletes
- timestamps

Constraints:

- Unique `building_id + unit_number`.

Relationships:

- belongs to building
- has many contracts
- has many expenses

### tenants

Tenant profile data.

- `id`
- `organization_id`
- `full_name`
- `phone`
- `email`
- `id_number`
- `nationality`
- `notes`
- timestamps

Relationships:

- belongs to organization
- has many contracts

### contracts

Rental agreement between a tenant and a unit.

- `id`
- `organization_id`
- `unit_id`
- `tenant_id`
- `contract_number`
- `start_date`
- `end_date`
- `rent_amount`
- `payment_frequency`: monthly, quarterly, semi_annual, annual
- `deposit_amount`
- `status`: active, expired, terminated
- `notes`
- timestamps

Constraints:

- Unique `organization_id + contract_number`.

Relationships:

- belongs to organization
- belongs to unit
- belongs to tenant
- has many payments

### payments

Scheduled or recorded payment rows for contracts.

- `id`
- `organization_id`
- `contract_id`
- `due_date`
- `amount_due`
- `amount_paid`
- `payment_date`
- `status`: pending, paid, partial, overdue
- `payment_method`: cash, bank_transfer, cheque, other
- `proof_image`
- `notes`
- `created_by`
- timestamps

Relationships:

- belongs to organization
- belongs to contract
- belongs to creator user

### expenses

Operating expenses against a building and optionally a unit.

- `id`
- `organization_id`
- `building_id`
- `unit_id`, nullable
- `category`: maintenance, electricity, water, cleaning, security, management, other
- `amount`
- `expense_date`
- `invoice_image`
- `notes`
- `created_by`
- timestamps

Relationships:

- belongs to organization
- belongs to building
- belongs to unit, optional
- belongs to creator user

### activity_logs

Audit trail for important actions.

- `id`
- `organization_id`
- `user_id`
- `action`
- `subject_type`
- `subject_id`
- `description`
- timestamps

## Recommended Indexes Before Production

Add indexes for report and dashboard performance:

- `payments(organization_id, status, due_date)`
- `payments(contract_id, due_date)`
- `expenses(organization_id, expense_date)`
- `contracts(organization_id, status, end_date)`
- `tenants(organization_id, full_name)`
- `buildings(organization_id, name)`

## Organization Isolation Notes

The current implementation enforces isolation in controllers using
`organization_id` filters and relationship checks. For production, add Laravel
Policies or global organization scopes to reduce the risk of missed checks.
