# Data Privacy and Analytics

Property Manager / المدير العقاري may eventually support data intelligence, but privacy and trust must come first.

## Current Principle

Workspace data is private.

Users should only access data that belongs to their own workspace. Organization-scoped security is a core product requirement, not only a technical detail.

## Sensitive Data

Sensitive data includes:

- Tenant names.
- Tenant contact information.
- Contract amounts.
- Payment history.
- Overdue payments.
- Expenses.
- Profit and income reports.
- Uploaded payment proof.
- Uploaded invoices.
- Activity logs.
- User accounts and roles.

## Future Data Intelligence

Future analytics should use aggregated and anonymized data.

Examples of acceptable future intelligence:

- Average rent trends by area and property type.
- Maintenance cost benchmarks.
- Vacancy trends.
- Payment delay benchmarks.
- Operating expense ratios.

These should not expose private workspace, tenant, contract, owner, or user-level data.

## Data Rules for Future Analytics

Future analytics should follow these rules:

- Use aggregated data.
- Use anonymized data.
- Avoid exposing single-owner or single-tenant records.
- Avoid showing identifiable property details without permission.
- Separate operational reporting from market intelligence.
- Document consent and data usage clearly.

## Abu Dhabi and UAE Trust Requirement

The first market is Abu Dhabi. The product must earn trust from landlords and property owners before using data for broader intelligence.

Operational reliability and privacy should come before monetizing analytics.

## What Not To Build Now

Do not build AI analytics in Stage 1.

Do not build market intelligence dashboards in Stage 1.

Do not sell or expose workspace-level data.

Do not use tenant records for external scoring or public ratings.

## Developer Guidance

When adding new reporting or analytics code:

- Keep workspace isolation strict.
- Do not mix private reports with future aggregate analytics.
- Keep sensitive financial data behind role checks.
- Add tests for cross-organization data leakage.
- Prefer explicit aggregation boundaries.
