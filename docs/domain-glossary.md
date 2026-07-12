# Domain Glossary

This glossary defines the core product terms for Property Manager.

## Organization / Account

An Organization / Account, currently represented in the codebase by `Organization`, is the workspace that uses the system.

It is the current boundary for:

- Authentication membership.
- Security and organization isolation.
- Role-based access.
- Team administration.
- Future platform subscription billing.

It may currently represent:

- An individual landlord.
- A family property owner.
- A small owner-managed property team.
- A future property management company.

Important: `Organization` must not always be treated as the real legal property owner. In future stages, one workspace may manage properties for multiple legal property owner clients.

## Workspace / Account

The product-facing name for the organization boundary.

For future development, developers should understand Workspace / Account as the operating account, not necessarily the legal owner of every property.

## Account Owner

The administrative user role inside an organization.

The current `owner` role controls access, roles, permissions, and team membership. It should not be treated as proof that the user is the legal owner of every managed property.

## Legal Property Owner / Client

The future real-world person or entity that legally owns managed properties.

In Stage 1, the workspace and legal property owner may often be the same person or family. In a future property-management company stage, one workspace may manage properties for many legal property owner clients.

This is not the current `owner` user role.

## Organization Type

A future classification of what kind of account the organization is.

Possible future organization types include:

- `owner_operator`.
- `property_management_company`.
- `maintenance_provider`.

Organization type is separate from user role.

## Property Management Company

A professional company that manages properties on behalf of legal property owner clients.

This is a future concept and should not be forced into the current Stage 1 data model too early.

## Property Manager

Depending on context, a Property Manager may mean an operational user inside an organization or a future property-management organization.

Documentation and UI copy should avoid using this term when the intended meaning is account owner, legal property owner, manager user role, or property-management company.

## Portfolio

A future grouping of properties belonging to a legal property owner/client or a management relationship.

Portfolios are not implemented in Stage 1.

## Service Provider

A future internal or external maintenance, cleaning, or operational provider.

Service providers may later include maintenance companies, cleaning providers, internal teams, or individual technicians. This concept is separate from the current caretaker user role.

## Building

A managed physical property or structure that contains units.

Stage 1 focuses on residential rental buildings, but the architecture should not block commercial, mixed-use, shop, office, warehouse, industrial, land, or daily rental concepts later.

## Unit

A rentable or manageable space inside a building or property.

Examples:

- Apartment.
- Villa.
- Shop.
- Office.
- Warehouse.
- Chalet.
- Other rentable space.

Future versions may need broader property and asset abstractions for land, industrial assets, and short-term rental inventory.

## Tenant

A tenant is a long-term rental party and a record in Stage 1.

Tenants should not have login accounts yet. A future tenant portal may allow tenants to log in, view balances, upload documents, request maintenance, or receive notifications.

## Guest

A future short-stay customer.

A guest is not a tenant contract substitute. Guest stays should use booking concepts, not long-term contract records.

## Contract

A long-term rental agreement between a tenant and a managed unit.

Current contracts are for long-term rental operations. Future daily or short-term rental should use booking concepts instead of reusing long-term contracts.

## Payment

A scheduled or recorded rent payment linked to a contract and workspace.

Payments should remain organization-scoped and should not be confused with future booking payments, SaaS subscription payments, or online payment gateway transactions.

## Platform Subscription

A future payment by the organization/account for using the SaaS platform.

Platform subscription billing must remain separate from tenant rent payments and future guest booking payments.

## Expense

A cost recorded against a workspace, building, or unit.

Expenses may include maintenance, service, utilities, management costs, repairs, or other property operation costs.

## Activity Log

An audit record of important user actions inside the workspace.

Activity logs are sensitive administrative data. They should remain scoped to the workspace and should remain available even if a user is deactivated or removed.

## Verified Rental Listing

A future listing that may be published only from a real unit already managed inside the system.

This is not an open classifieds or broker marketplace concept.

## Daily Rental Booking

A future short-term rental concept based on booking dates, guests, check-in, check-out, calendar availability, cleaning, linen, and damage inspection.

Daily rental should remain separate from long-term contract logic.
