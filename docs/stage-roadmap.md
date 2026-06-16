# Stage Roadmap

This roadmap keeps Property Manager / المدير العقاري focused and prevents future modules from distorting the Stage 1 MVP.

## Stage 1: Individual Landlord Operations

Target users:

- Individual landlords.
- Family property owners.
- Small owner-managed teams in Abu Dhabi.

Core scope:

- Buildings.
- Units.
- Tenant records.
- Long-term rental contracts.
- Rent payments.
- Overdue payments.
- Expenses.
- Vacant units.
- Reports.
- Documents and PDF exports.
- Activity logs.
- Owner-managed team roles.

Tenants are records only in this stage. They should not have accounts.

## Stage 1 Stabilization

Priorities:

- Keep GitHub Actions green.
- Strengthen form validation.
- Harden file uploads.
- Improve manual browser QA.
- Confirm mobile usability.
- Add PostgreSQL CI if production will use PostgreSQL.
- Improve deployment documentation.
- Keep role and organization isolation tests strong.

## Stage 1 Architecture Cleanup

Priorities:

- Document `Organization` as Workspace / Account.
- Add user activation/deactivation planning.
- Protect against leaving a workspace without an owner.
- Preserve activity logs when users are deactivated or removed.
- Clarify property type taxonomy.
- Prepare localization foundations for Arabic and English.

## Stage 2: Property Management Companies

Future target users:

- Property management companies.
- Operators managing properties for multiple real property owners.

Needed concepts:

- Real property owners separate from workspaces.
- Owner statements.
- Owner-level financial reports.
- Property management company profile.
- Management agreements.
- Owner-specific balances and expenses.

Do not build this until Stage 1 is stable with real pilot users.

## Stage 3: Maintenance Tender Marketplace

Future concept:

- Maintenance requests can be sent to service providers.
- Providers submit quotes.
- The owner or manager chooses a quote.

This should be tender or quote based, not an uncontrolled open services marketplace.

## Stage 4: Tenant Portal

Future concept:

- Tenants may log in.
- Tenants may view balances.
- Tenants may upload payment proof.
- Tenants may submit maintenance requests.
- Tenants may receive notifications.

Tenant accounts should not be added in Stage 1.

## Stage 5: Verified Rental Market

Future concept:

- Only real units already managed inside the system can be published.
- No open broker posting.
- No duplicate uncontrolled listings.
- No sales module.
- No general classifieds behavior.

This protects trust and data quality.

## Stage 6: Daily and Short-Term Rental

Future concept:

- Bookings.
- Availability calendar.
- Check-in and check-out.
- Guests.
- Cleaning.
- Linen.
- Damage inspection.
- Seasonal pricing.
- Housekeeping.

Daily rental should remain separate from long-term rental contracts.

## Long-Term Data Intelligence

Future intelligence should use aggregated and anonymized operational data.

Private workspace data, tenant data, owner data, and contract data must not be exposed.
