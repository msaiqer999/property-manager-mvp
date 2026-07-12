# Payment Domain Boundaries

## Document Status And Purpose

Status: Approved now.

This document defines the financial boundaries for Property Manager. It prevents unrelated money workflows from being merged into one generic payment model.

## Core Principle

These domains must not share one generic payment workflow merely because they all involve money.

The platform has three separate financial domains:

- Long-term rent payments.
- Future short-stay booking payments.
- Future SaaS platform subscription billing.

Each domain has different users, records, lifecycle rules, reporting needs, and legal meaning.

## A. Long-Term Rent Payments

Status: Approved now.

The current first-stage product records long-term rent collection activity.

Domain concepts:

- Tenant.
- Contract.
- Payment schedule.
- Rent payment.
- Overdue tracking.
- Receipt.

Current rent payment records are operational property-management records. They help the organization track what was due, what was recorded as paid, what remains overdue, and which receipt or proof belongs to that rent item.

The first-stage release records rent collections operationally but does not process, hold, split, or distribute tenant rent money.

## B. Future Short-Stay Booking Payments

Status: Planned later.

Future short-stay payments belong to a separate booking domain.

Future domain concepts:

- Guest.
- Booking.
- Booking charge.
- Booking payment.
- Refund or cancellation handling.

A booking must not be modeled as a short contract. Short-stay payment rules may involve nightly charges, cancellation windows, deposits, refunds, channel fees, taxes, and guest-facing receipts. Those rules are not the same as long-term rent schedules.

## C. SaaS Platform Subscription Billing

Status: Planned later.

Future SaaS billing belongs to the platform business domain, not the property operations domain.

Future domain concepts:

- Organization billing customer.
- Subscription.
- Package.
- Invoice.
- Payment gateway.
- Renewal.
- Failed payment.
- Grace period.

A SaaS subscription payment must never be stored as a rent payment. It is paid by the organization for using the platform and must remain separate from tenant rent and future guest booking payments.

## Payment Gateway Scope

Status: Deliberately postponed.

Payment gateway implementation is postponed.

When a gateway is later introduced, the architecture should specify which domain it serves:

- Tenant rent payment collection.
- Guest booking payment collection.
- Platform SaaS subscription billing.

Adding a gateway for one domain must not imply that all payment domains now share the same models or lifecycle.

## Reporting Boundaries

Status: Approved now.

Reports must preserve financial meaning:

- Rent reports show property income records from tenant contracts.
- Future booking reports should show stay and guest booking economics.
- Future SaaS billing reports should show platform subscription revenue and collections.

Mixing these domains in one table or report without clear type and ownership boundaries would make financial reporting unreliable.

## Development Guardrails

Status: Approved now.

- Do not model a booking as a short contract.
- Do not store SaaS subscription payments as rent payments.
- Do not use the current `payments` table for future platform billing.
- Do not assume future booking charges follow long-term rent schedule semantics.
- Do not add payment gateway code until a specific domain and lifecycle are approved.
- Keep organization isolation mandatory for every financial record.
