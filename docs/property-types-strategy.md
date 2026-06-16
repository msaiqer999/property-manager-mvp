# Property Types Strategy

Stage 1 focuses on residential rentals, but the architecture should not block future property types.

## Stage 1 Primary Focus

The first launch should focus on:

- Residential buildings.
- Apartments.
- Villas.
- Simple rental units.

This keeps the Abu Dhabi MVP practical and easy to operate.

## Future Property Types

The product direction should allow future support for:

- Commercial properties.
- Mixed-use buildings.
- Shops.
- Offices.
- Warehouses.
- Industrial properties.
- Land.
- Daily or short-term rental properties.

## Current Model Direction

The current Building and Unit model is acceptable for Stage 1.

However, developers should avoid assuming every property is a residential apartment. Future development may need a broader property or asset abstraction.

## Long-Term Type Concepts

Future versions may need:

- `property_type`
- `asset_type`
- `usage_type`
- `rental_mode`
- `long_term_rental`
- `short_term_rental`
- `commercial_use`
- `industrial_use`
- `land_use`

These should be introduced carefully after Stage 1 is stable.

## Daily Rental Separation

Daily rental should not reuse long-term contract logic as-is.

Daily rental needs booking concepts:

- Booking.
- Calendar.
- Guest.
- Check-in.
- Check-out.
- Cleaning.
- Linen.
- Damage inspection.
- Seasonal pricing.
- Housekeeping.

## Verified Rental Market Compatibility

Future verified rental listings should be linked to real internal units already managed in the system.

Recommended future concept:

- A `rental_listings` model linked to `unit_id`.
- Only one active listing per unit.
- No broker-created external listings.
- No sales listings.

## Developer Guidance

When adding Stage 1 code:

- Do not hard-code residential-only assumptions unless unavoidable.
- Keep labels and statuses translatable.
- Keep property type values easy to extend.
- Do not add daily rental or listing behavior during Stage 1 stabilization.
