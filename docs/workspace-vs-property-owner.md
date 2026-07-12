# Workspace vs Property Owner

The most important architectural rule for future development is:

`Organization` currently means Workspace / Account, not always the real property owner.

## Why This Matters

In Stage 1, an individual landlord or family property owner may create one workspace and manage their own properties. In that case, the workspace and the real property owner are often the same.

In a later property-management company stage, one workspace may manage properties for several legal property owner clients. In that case, the workspace is the operating account, while each legal property owner/client is a separate business entity that needs separate statements, reports, and balances.

## Current Stage 1 Interpretation

Current code uses `organizations` to isolate data.

This is correct for Stage 1 if developers interpret it as:

- The account using the system.
- The workspace boundary.
- The security and data isolation boundary.
- The team container.
- The future platform billing boundary.

It should not be assumed to be the legal owner of every property forever.

The current `owner` user role means the account administrator inside the workspace. It does not always mean the legal property owner.

## Future Stage 2 Need

To support property management companies, the platform will eventually need a separate legal property owner/client model.

Possible future concepts:

- `property_owners`.
- `property_owner_contacts`.
- `portfolios`.
- `managed_properties`.
- `owner_statements`.
- `property_owner_reports`.
- `management_agreements`.

These should belong to a workspace, but represent the real owner or client relationship for each managed property.

## Future Example

Future extension:

- Organization: a property-management company.
- Users: company employees who work inside that organization.
- Legal owner clients: several different landlords or property-owning entities.
- Portfolios: properties grouped by each legal owner/client or management relationship.

This is a future extension. It does not require database implementation during the Basic Owner stage.

## Recommended Future Relationship

Future direction:

- Workspace owns users, permissions, and operational access.
- Account owner user role controls administration inside the workspace.
- Legal property owners/clients own the economic interest in properties.
- Buildings, units, contracts, expenses, and reports may need links to legal property owners/clients.
- Property management company workspaces can manage multiple legal property owner clients.
- Portfolios can group managed properties under each legal owner/client or management agreement.

## What Not To Do Now

Do not rename `Organization` in code until there is a planned migration.

Do not add property management company features during Stage 1 stabilization.

Do not overload user roles to represent real property owners. A real property owner is a business domain entity, not just a login role.

Do not treat organization type as a user role. Future types such as `owner_operator`, `property_management_company`, or `maintenance_provider` describe the account, not the individual user's permissions.

## Immediate Developer Guidance

When adding new Stage 1 code, treat `organization_id` as the workspace isolation key.

When writing documentation, labels, or product copy, prefer the terms:

- Workspace.
- Account.
- Team.
- Account owner, when referring to the administrative user role.
- Legal property owner/client, when referring to the real-world property owner.

Avoid implying that `Organization` always means the legal property owner.
