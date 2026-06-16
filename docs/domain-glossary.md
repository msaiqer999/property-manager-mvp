# Domain Glossary

This glossary defines the core product terms for Property Manager / المدير العقاري.

## Workspace / Account

A Workspace, currently represented in the codebase by `Organization`, is the account that uses the system.

It may represent:

- An individual landlord.
- A family property owner.
- A small owner-managed property team.
- A future property management company.

Important: `Organization` must not always be treated as the real legal property owner. In future stages, one workspace may manage properties for multiple real property owners.

## Organization

The current database and application term for Workspace / Account.

For future development, developers should understand `Organization` as the operating account, not necessarily the legal owner of every property.

## Real Property Owner

The legal or beneficial owner of a property.

In Stage 1, the workspace and real property owner may often be the same person or family. In Stage 2, a property management company workspace may manage properties for many real property owners.

## Property Management Company

A professional company that manages properties on behalf of real property owners.

This is a future Stage 2 concept and should not be forced into the current Stage 1 data model too early.

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

A tenant is a record in Stage 1.

Tenants should not have login accounts yet. A future tenant portal may allow tenants to log in, view balances, upload documents, request maintenance, or receive notifications.

## Contract

A long-term rental agreement between a tenant and a managed unit.

Current contracts are for long-term rental operations. Future daily or short-term rental should use booking concepts instead of reusing long-term contracts.

## Payment

A scheduled or recorded rent payment linked to a contract and workspace.

Payments should remain organization-scoped and should not be confused with online payment gateway transactions unless a future payment gateway module is added.

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
