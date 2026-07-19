# Global Readiness Foundation

Property Manager should remain a core property-management platform with country configuration, not a country-specific fork and not a franchise system.

The current MVP keeps the user experience simple while preparing the data model for future countries such as the United Arab Emirates, Indonesia, Saudi Arabia, Kenya, Tanzania, Morocco, and others.

## Architecture Principle

The product architecture is:

```text
Core Property Management Platform + Country Configuration
```

The core platform owns common workflows:

- organizations and users
- buildings, units, tenants, contracts, payments, expenses, and reports
- authorization and organization isolation
- private document handling
- localization and RTL support
- closed-beta onboarding and feedback

Country configuration owns market-specific defaults:

- default country
- default currency
- default locale
- default timezone
- property types
- payment methods
- tax/report settings
- contract templates

Country configuration must not change core rent, payment, expense, contract, authorization, or storage behavior unless a future sprint explicitly approves that change.

## Source Of Truth

Before registration, country detection from IP address, browser locale, device locale, or marketing page context may only be used as a suggestion.

Do not treat IP-based detection as the official source of truth. IP address can be inaccurate because of VPNs, travel, shared offices, hosting networks, and browser privacy tools.

During registration, the user may answer:

```text
Where are the properties you want to manage located?
```

When selected, that country becomes the organization's default country and supplies the initial currency, locale, and timezone defaults.

After registration, organization settings are the official source of truth for country-sensitive defaults. A user preferred locale may override display language only. It must not change the organization's legal, tax, reporting, contract, currency, or timezone defaults.

## Data Model

The foundation adds reference tables for:

- `countries`
- `currencies`
- `property_types`
- `payment_methods`
- `contract_templates`
- `tax_settings`

The foundation also adds nullable default fields to:

- `organizations`
- `users`
- `buildings`

Nullable fields are intentional. Existing pilot data and tests can continue to run while future onboarding and settings screens gradually fill these defaults.

## Organization Defaults

An organization can store:

- `country_id`
- `currency_code`
- `locale`
- `timezone`

If an organization has a country but no currency override, the country default currency can be used. This supports simple single-country portfolios now and prepares for settings screens later.

## Building Overrides

Buildings are the current property-level entity in the MVP.

Buildings can store nullable overrides for:

- `country_id`
- `currency_code`
- `timezone`

For now, the UI should stay simple and use organization defaults. Building-level overrides are a future hook for multi-country portfolios where one organization manages properties across more than one country.

## Property Types And Payment Methods

Property types and payment methods can be global or country-specific.

Global records have `country_id = null`. Country-specific records have a `country_id`.

Future selection logic should combine:

```text
global options + selected country options
```

This avoids hardcoding one country's property vocabulary into the core app.

## Contract Templates

Contract templates are stored by optional country and language.

Seeded templates are basic placeholders for future operator-reviewed templates. They are not legal advice and should not be treated as final market-ready lease documents.

## Tax Settings

Tax settings are country-scoped.

Seeded tax settings are inactive manual-review placeholders. This avoids encoding unstable or legally sensitive tax rules into the MVP before an operator verifies them for the target country and use case.

## Current MVP Boundaries

This foundation does not add:

- franchise management
- multi-country enterprise dashboards
- payment gateways
- AI features
- maintenance marketplaces
- public listings
- tax calculation logic
- multi-currency accounting
- country-specific financial calculations

The existing UAE-oriented demo remains compatible through seeded organization defaults using AED and `Asia/Dubai`.

## Future Expansion

Future sprints can build on this foundation by adding:

- organization settings for country, currency, locale, and timezone
- building-level country overrides for multi-country portfolios
- localized property-type and payment-method labels
- operator-reviewed contract templates per country and language
- verified tax/report configuration per country
- explicit country detection suggestions during onboarding

Those future additions should continue to keep the first-run MVP interface simple.
