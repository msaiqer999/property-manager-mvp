# Platform Architecture

## Document Status And Purpose

Status: Approved now.

This is the canonical architecture reference for the Property Manager platform. It documents the approved product boundaries for Development #14 and separates the current Basic Owner closed-beta implementation from planned future modules.

This document is architecture guidance. It does not mean every future concept is implemented today.

## Approved Platform Vision

The Property Manager platform is a subscription-based property operating platform for owners, property managers, property-management companies, enterprises, and service providers.

It begins with long-term property administration, contracts, rent records, expenses, documents, and reporting.

It may later expand through separate modules for:

- Professional owner operations.
- Property-management companies.
- Maintenance.
- Cleaning.
- Property marketing and leasing.
- Short-stay and daily rental.
- Mobile applications.
- SaaS subscription billing.

The platform must not earn commissions from:

- Landlord rental income.
- Short-stay booking income.
- Maintenance job value.
- Cleaning job value.

Revenue should come from:

- Fixed subscriptions.
- Clear package upgrades.
- Fixed module subscriptions.
- Transparent usage packages for external costs when necessary.

## Current First-Stage Product Scope

Status: Approved now.

The first-stage product is the Basic Owner closed-beta product for long-term rental property management.

Current scope includes:

- Organizations and users.
- Buildings and units.
- Tenants.
- Long-term contracts.
- Payment schedules, rent payments, receipts, and overdue tracking.
- Expenses.
- Reports.
- Documents.
- Roles and permissions.
- Activity logs.
- Localization.
- Mobile-first and PWA-compatible web experience.
- Quick Start, Pilot Guide, and beta feedback.

The first-stage release records rent collection activity for operational tracking. It does not process, hold, split, or distribute tenant rent money.

## Long-Term Customer Segments

Status: Planned later.

The platform should be able to grow toward these customer segments without duplicating the property core:

- Basic Owner.
- Professional Owner.
- Property Management Companies.
- Large Property Enterprises.
- Specialized Maintenance Companies.
- Full-Service Maintenance Companies.
- Cleaning and operational service providers.
- Property marketing and leasing teams.
- Short-stay and daily-rental operators.

These segments should be added incrementally only when there is a real product need.

## Shared Property Platform Core

Status: Approved now.

The platform should keep one shared property operating core. Buildings, properties, and units should not be duplicated separately for each future module.

Future modules may attach their own domain records to the shared core:

- Long-term rental contracts attach to units.
- Future bookings attach to units through availability and stay records.
- Future maintenance work orders attach to buildings, units, or common areas.
- Future cleaning tasks attach to units, stays, or operational schedules.
- Future marketing workflows attach to managed properties or units.

## Organization / Account Boundary

Status: Approved now.

`Organization` currently represents the workspace/account boundary.

It is the boundary for:

- Authentication membership.
- Authorization and user roles.
- Data ownership and isolation.
- Activity logging.
- Future subscription billing.

`organization_id` must remain a non-negotiable security boundary. No user should be able to view or mutate records from another organization unless a future explicitly designed cross-organization workflow allows it.

## User Roles Versus Organization Types

Status: Approved now.

User roles and organization types are separate concepts.

Current user roles are operational permissions inside one organization:

- Owner.
- Manager.
- Accountant.
- Caretaker.

Future organization types may include:

- `owner_operator`.
- `property_management_company`.
- `maintenance_provider`.

Do not use user roles to represent organization type. Do not use organization type to grant individual user permissions.

## Account Owner Versus Legal Property Owner / Client

Status: Approved now.

The current `owner` user role means the account administrator inside an organization. It does not always mean the real-world legal owner of every property.

A future property-management company organization may have:

- One organization/account.
- Many employee users.
- Many legal property owner clients.
- Many portfolios grouped by client or management agreement.

Do not assume the account owner is always the legal property owner.

## Future Legal Owner / Client And Portfolio Concepts

Status: Planned later.

Future property-management company support may require:

- Legal property owner/client records.
- Client contacts.
- Managed portfolios.
- Management agreements.
- Owner statements.
- Owner-level reports and balances.

These concepts should belong to an organization but represent real-world ownership or client relationships. They are deliberately not implemented during the Basic Owner stage.

## Property / Building And Unit As The Shared Operational Core

Status: Approved now.

Buildings and units are the current operational core.

Future property types may need broader naming, but the architectural principle is stable: properties and units are managed assets shared by rental, maintenance, cleaning, reporting, and marketing modules.

Unit status should remain a current operational state, not the only source of truth for every future rental mode. Short-stay availability should use a future booking and calendar model rather than forcing daily occupancy into long-term contract status.

## Long-Term Rental Domain

Status: Approved now.

The current long-term rental domain uses:

- Tenant.
- Contract.
- Payment schedule.
- Rent payment.
- Receipt.
- Renewal.
- Termination.
- Overdue management.

This domain should remain focused on long-term rental administration.

## Future Maintenance And Cleaning Domain

Status: Planned later.

Maintenance and cleaning should become shared operational modules, not separate property systems.

Future work may include:

- Work orders.
- Internal staff assignment.
- Internal maintenance teams.
- External maintenance providers.
- Cleaning providers.
- Individual technicians.
- Status history.
- Attachments and completion evidence.

The platform should not take commissions from maintenance or cleaning job value under the approved business model.

## Future Property Marketing And Leasing Domain

Status: Planned later.

Future marketing and leasing workflows should publish or manage supply from real properties or units already managed in the system.

This should not become an uncontrolled open classifieds marketplace. Any listing or leasing module should preserve organization isolation, property data quality, and clear ownership of published records.

## Future Short-Stay And Daily-Rental Domain

Status: Planned later.

Short-stay and daily-rental workflows must use separate concepts from long-term rental contracts:

- Guest.
- Booking.
- Stay.
- Availability calendar.
- Nightly pricing.
- Check-in and check-out.
- Housekeeping.
- Booking charges.
- Booking payments.
- Refund or cancellation handling.

A booking must not be modeled as a short contract.

## Future SaaS Subscription Billing Domain

Status: Planned later.

SaaS platform billing is separate from rent payments and future booking payments.

Future SaaS billing may include:

- Organization billing customer.
- Subscription.
- Package.
- Invoice.
- Payment gateway.
- Renewal.
- Failed payment.
- Grace period.

A SaaS subscription payment must never be stored as a rent payment.

Payment gateway implementation is postponed.

## Future Android And iOS Application Readiness

Status: Planned later.

The current web application should remain mobile-first and PWA-compatible.

Future mobile readiness should preserve:

- Responsive workflows.
- Clear mobile navigation.
- Browser-compatible file uploads.
- Avoidance of unnecessary browser-only assumptions.
- A path toward gradual API extraction or API endpoints.
- Compatibility with a future Capacitor or native app strategy.

Native Android and iOS applications are not part of the Basic Owner stage.

## Capability And Usage-Limit Architecture

Status: Planned later.

Future package access should be based on capabilities and usage limits, not hard-coded commercial package names.

Possible future capabilities include:

- `advanced_reports`.
- `maintenance_requests`.
- `cleaning_operations`.
- `property_marketing`.
- `short_stay_management`.
- `multiple_property_owners`.
- `approval_workflows`.
- `tenant_portal`.
- `team_inbox`.
- `api_access`.

Possible future limits include:

- `max_units`.
- `max_users`.
- `max_properties`.
- `storage_limit`.
- `message_allowance`.
- `active_short_stay_units`.

User-role permissions and package entitlements are separate concerns.

## Security And Organization Isolation Principles

Status: Approved now.

Security principles:

- Organization isolation is mandatory.
- Policies and route middleware must preserve role restrictions.
- Cross-organization access must be denied before file existence or record details are disclosed.
- Activity logs should capture important administrative changes.
- Sensitive files should remain private and be served through authorized routes.
- Future API endpoints must preserve the same organization isolation rules as Blade routes.

## Data Ownership And Export Principles

Status: Approved now.

Each organization owns its operational data.

Future export work should respect:

- Organization isolation.
- Role-based access.
- Legal owner/client boundaries when those entities are added.
- Privacy of tenant, contract, payment, and document data.
- Aggregated and anonymized handling for future analytics.

## Architecture Decisions That Are Approved Now

Status: Approved now.

- The current first-stage release remains Basic Owner closed beta.
- `Organization` is the workspace/account and security boundary.
- User role `owner` means account administrator, not always legal property owner.
- Organization type is separate from user role.
- Legal property owners/clients are future domain entities.
- Packages should use capabilities and limits.
- Long-term rental and short-stay rental are separate domains.
- Rent payments, booking payments, and SaaS subscription billing are separate financial domains.
- The approved revenue model is fixed subscriptions, package upgrades, module subscriptions, and transparent external usage costs when necessary.
- Transaction commissions from rent, bookings, maintenance, or cleaning are not part of the approved model.

## Features Deliberately Postponed

Status: Deliberately postponed.

- `organization_type` database implementation.
- Legal property owner/client entities.
- Portfolio entities.
- Package entitlement tables.
- Entitlement enforcement code.
- Payment gateway integration.
- SaaS subscription billing implementation.
- Short-stay booking module.
- Maintenance provider marketplace.
- Cleaning operations module.
- Native Android and iOS applications.
- Speculative future feature tests.

## Development Guardrails

Status: Approved now.

When adding new features:

- Preserve Basic Owner closed-beta focus unless a later stage is explicitly approved.
- Keep `organization_id` scoping explicit and tested.
- Do not confuse account owner with legal property owner.
- Do not confuse user roles with organization types.
- Do not model bookings as contracts.
- Do not store SaaS subscription payments as rent payments.
- Do not hard-code behavior against commercial package names.
- Do not add empty tables or abstractions before a real feature needs them.
- Prefer documenting future boundaries before implementing future modules.

## Phased Product Roadmap

Status: Planned later.

The practical roadmap is:

1. Complete the Basic Owner closed beta for long-term rental operations.
2. Harden deployment, file storage, backups, localization, mobile workflows, and support processes.
3. Add Professional Owner capabilities only when pilot feedback proves the need.
4. Add property-management company support with legal owner/client and portfolio concepts.
5. Add maintenance and cleaning operational modules.
6. Add property marketing and leasing workflows.
7. Add short-stay and daily-rental workflows using booking-specific models.
8. Add SaaS subscription billing and package upgrades.
9. Add native mobile app strategy after the web/PWA workflows are stable.

No timeline is promised by this roadmap.

## Definition Of The Completed First Stage

Status: Approved now.

The first stage is complete when Basic Owner closed-beta users can reliably manage:

- Buildings and units.
- Tenant records.
- Long-term contracts.
- Rent schedules and recorded collections.
- Overdue tracking.
- Receipts and documents.
- Expenses.
- Reports.
- Team roles.
- Activity logs.
- English and Arabic workflows.
- Mobile-first usage.
- Pilot onboarding and feedback.

Completion of the first stage does not require property-management company support, short-stay rental, payment gateway processing, SaaS billing, maintenance provider workflows, cleaning operations, or native mobile applications.
