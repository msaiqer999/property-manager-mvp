# Property Manager MVP

Complete Laravel MVP application for small property owners to manage buildings,
units, tenants, contracts, payment schedules, received payments, expenses,
reports, and activity logs.

## Stack

- Laravel 11
- PHP 8.2+
- PostgreSQL
- Blade
- Tailwind CSS via Vite
- Laravel DomPDF
- Laravel built-in session authentication
- Lightweight role-based access control

## Requirements

- PHP 8.2 or newer
- Composer
- PostgreSQL 14 or newer
- Node.js 18 or newer
- npm

## Setup

This setup path is for local development and demo use. Real pilot deployment
must use the pilot-safe onboarding sequence in
[Deployment Notes](docs/deployment-notes.md).

From the project root:

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
```

Create a PostgreSQL database:

```sql
CREATE DATABASE property_manager;
```

Update `.env` if needed:

```env
APP_NAME="Property Manager"
APP_URL=http://127.0.0.1:8000
APP_LOCALE=en
REGISTRATION_ENABLED=true

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=property_manager
DB_USERNAME=postgres
DB_PASSWORD=secret
```

Run migrations and seed demo data:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

Never run php artisan migrate:fresh, php artisan db:wipe, or demo seeders against the real pilot database.

Run the frontend and backend:

```bash
npm run dev
php artisan serve
```

Open:

```text
http://127.0.0.1:8000
```

## Demo Accounts

All demo accounts use password `password`.

- Owner: `owner@example.com`
- Manager: `manager@example.com`
- Accountant: `accountant@example.com`
- Caretaker: `caretaker@example.com`

## Demo Data

The seeder creates:

- 1 organization
- 4 users: owner, manager, accountant, caretaker
- 2 buildings
- 20 units
- 12 tenants
- 12 active contracts
- Monthly, quarterly, and annual payment schedules
- 5 overdue payments
- 10 expenses

The dashboard should immediately show realistic monthly income, monthly
expenses, net profit, overdue amount, vacant/rented unit counts, contracts ending
soon, latest payments, and latest expenses.

## Main Modules

- Authentication: register, login, logout, password reset.
- Organizations: each user belongs to one organization.
- Buildings: create, edit, view, list.
- Units: create, edit, view, filter by building/status.
- Tenants: create, edit, profile, tenant contracts.
- Contracts: create, link tenant/unit, generate schedule, export PDF.
- Payments: scheduled payments, record received payment, upload proof, receipt PDF.
- Expenses: building/unit expenses, invoice upload, filters.
- Dashboard: income, expenses, net profit, overdue, unit counts, recent activity.
- Reports: building income, unit statement, expenses, overdue, net profit, monthly summary.
- Activity logs: key actions with user and timestamp.

## Roles

- Owner: full access, including users and critical deletes.
- Manager: manages operational records, cannot manage users or delete critical records.
- Accountant: views payments, expenses, reports, and exports; cannot edit contracts or delete data.
- Caretaker: records payments and uploads proof; cannot view profit reports or edit contracts.

## Useful Commands

```bash
php artisan pilot:create-owner
php artisan payments:mark-overdue
php artisan test
php artisan route:list
```

## Pilot Onboarding

For a real pilot, public self-registration should remain disabled. Create the
first owner from a trusted server console instead:

1. Keep the application unavailable publicly or behind restricted access.
2. Set all production environment values, including `REGISTRATION_ENABLED=false`.
3. Run `php artisan config:clear`.
4. Run `php artisan config:cache`.
5. Run migrations only: `php artisan migrate --force`.
6. Run `php artisan pilot:create-owner` from the trusted server console.
7. Verify owner login through restricted access.
8. Verify as a guest that GET and POST `/register` return 404.
9. Only then expose the application to intended pilot users.
10. Keep `REGISTRATION_ENABLED=false`.

Automated tests should explicitly configure registration state and use an
isolated test database.

## Documentation

- [Database Structure](docs/database-structure.md)
- [User Roles And Permissions](docs/user-roles.md)
- [Business Logic](docs/business-logic.md)
- [Known Limitations](docs/known-limitations.md)
- [Future Roadmap](docs/future-roadmap.md)
- [Deployment Notes](docs/deployment-notes.md)

## Known Limitations

- Authorization is MVP-level and should be moved to Laravel Policies or Spatie Permission.
- Reports are basic business summaries, not audited accounting reports.
- Payment schedules do not prorate partial periods.
- Uploaded images are stored on the local private disk; add signed download routes when viewing files is needed.
- Arabic RTL direction is prepared, but translation files are not complete.
- Browser verification must be performed after installing PHP/Composer/Node locally.

## Future Roadmap

- Full Arabic translations and RTL QA.
- Policies and complete role matrix tests.
- Date range filters for reports.
- Automatic overdue scheduler in production cron.
- Contract renewal and termination workflows.
- Payment ledger with multiple receipts per scheduled payment.
- Private file storage and secure document access.
- PWA support for smartphone field usage.
