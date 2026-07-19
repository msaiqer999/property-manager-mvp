# Indonesia Closed Pilot v1

Indonesia Closed Pilot v1 is a controlled pilot for 5-10 Indonesian landlords or small operators. It uses the Global Readiness Foundation as country configuration on top of the same core Property Manager platform.

## Positioning

This platform is for landlord property management. It is not an advertising marketplace, broker platform, payment gateway, maintenance marketplace, or rent-commission product.

Core pilot message in Bahasa Indonesia:

- Kami tidak mengambil bagian dari uang sewa Anda.
- Kami membantu Anda mengelola properti Anda.

## Country Configuration

Indonesia must come from seeded country configuration, not hardcoded application assumptions:

- Country code: `ID`
- Currency: `IDR`
- Locale: `id` where Bahasa Indonesia text exists, with fallback to English for untranslated MVP screens
- Timezone: `Asia/Jakarta`
- Property types: `kos`, `kamar`, `kontrakan`, `ruko`, `apartment`, `shop`, `warehouse`

Organization country remains the source of truth after registration. IP or browser locale can only ever be a suggestion in future work.

## Sample Pilot Data

The optional Indonesia sample data seeder creates:

- Kos Putri Surabaya with 18 rooms
- Kontrakan Keluarga Malang with 6 units
- Ruko Sidoarjo with 4 units
- Gudang kecil Gresik with 2 units

Use it only for local or staging pilot walkthroughs:

```bash
php artisan db:seed --class=IndonesiaClosedPilotSeeder
```

Do not run sample seeders against a real pilot database with live landlord data.

## Scope Boundaries

This pilot does not add:

- Payment gateway processing
- AI features
- Public listings
- Broker workflows
- Rent commission
- Maintenance marketplace
- Multi-country enterprise dashboard

Keep the MVP simple. Real pilot owners should still be onboarded as separate organizations using the private pilot runbook.
