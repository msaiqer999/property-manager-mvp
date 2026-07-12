# Package Entitlements Strategy

## Document Status And Purpose

Status: Approved now.

This document defines how future packages should control access without hard-coding product behavior against commercial package names.

No entitlement tables or entitlement enforcement code are required in Development #14.

## Business Model

Status: Approved now.

The approved business model is:

- Fixed subscriptions.
- Clear package upgrades.
- Fixed module subscriptions.
- Transparent usage packages for external costs when necessary.

Transaction commissions are not part of the approved model. The platform should not take commissions from landlord rental income, booking income, maintenance job value, or cleaning job value.

## Commercial Packages Versus Runtime Entitlements

Status: Approved now.

Commercial package names are presentation and pricing concepts.

Application code must not hard-code behavior against names such as:

- `basic_owner`.
- `professional_owner`.
- `enterprise`.

Package names may change for marketing, localization, pricing experiments, or regional positioning. Runtime access should instead be based on capabilities and limits.

## User Roles Versus Package Entitlements

Status: Approved now.

User-role permissions and package entitlements are separate concerns.

User roles answer: What may this user do inside the organization?

Package entitlements answer: What has this organization subscribed to or been allowed to use?

Examples:

- A manager may have permission to view reports, but the organization may not have the `advanced_reports` capability.
- An owner may have permission to add users, but the organization may be limited by `max_users`.
- A caretaker may record payments based on role permissions, regardless of whether the organization is Basic Owner or Professional Owner.

Usage limits must be enforceable without changing user roles.

## Future Capabilities

Status: Planned later.

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

Capabilities should be stable internal keys. They should not be final commercial package names.

## Future Limits

Status: Planned later.

Possible future limits include:

- `max_units`.
- `max_users`.
- `max_properties`.
- `storage_limit`.
- `message_allowance`.
- `active_short_stay_units`.

Limits should be checked where usage is created or expanded. They should be reported clearly to account administrators.

## When To Add Entitlement Code

Status: Deliberately postponed.

Entitlement code should be introduced only with the first real package-gated feature.

Do not add empty entitlement tables, unused services, or speculative package logic during Development #14.

When entitlement code is introduced, it should provide:

- A clear source of organization capabilities.
- A clear source of organization limits.
- Tests showing that roles and entitlements are independent.
- Tests showing that package labels can change without changing behavior.

## Development Guardrails

Status: Approved now.

- Do not hard-code runtime behavior against commercial package names.
- Do not use user roles as package tiers.
- Do not use package tiers as user permissions.
- Do not add entitlement schema before a real package-gated feature requires it.
- Keep package decisions separate from rent, booking, and SaaS billing records.
