# Workspace vs Property Owner

The most important architectural rule for future development is:

`Organization` currently means Workspace / Account, not always the real property owner.

## Why This Matters

In Stage 1, an individual landlord or family property owner may create one workspace and manage their own properties. In that case, the workspace and the real property owner are often the same.

In Stage 2, a property management company may use one workspace to manage properties for several real property owners. In that case, the workspace is the operating account, while each real property owner is a separate business entity that needs separate statements, reports, and balances.

## Current Stage 1 Interpretation

Current code uses `organizations` to isolate data.

This is correct for Stage 1 if developers interpret it as:

- The account using the system.
- The workspace boundary.
- The security and data isolation boundary.
- The team container.

It should not be assumed to be the legal owner of every property forever.

## Future Stage 2 Need

To support property management companies, the platform will eventually need a separate real property owner model.

Possible future concepts:

- `property_owners`
- `property_owner_contacts`
- `managed_properties`
- `owner_statements`
- `property_owner_reports`
- `management_agreements`

These should belong to a workspace, but represent the real owner of each property.

## Recommended Future Relationship

Future direction:

- Workspace owns users, permissions, and operational access.
- Real property owners own the economic interest in properties.
- Buildings, units, contracts, expenses, and reports may need links to real property owners.
- Property management company workspaces can manage multiple real property owners.

## What Not To Do Now

Do not rename `Organization` in code until there is a planned migration.

Do not add property management company features during Stage 1 stabilization.

Do not overload user roles to represent real property owners. A real property owner is a business domain entity, not just a login role.

## Immediate Developer Guidance

When adding new Stage 1 code, treat `organization_id` as the workspace isolation key.

When writing documentation, labels, or product copy, prefer the terms:

- Workspace.
- Account.
- Team.

Avoid implying that `Organization` always means the legal property owner.
