# Known Limitations

This is now a complete Laravel application repository structure, including
`artisan`, `bootstrap`, `config`, `public`, `resources`, `routes`, `storage`,
`tests`, Composer metadata, and Vite/Tailwind frontend metadata.

It is still an MVP and should not be treated as production-ready until it has
been installed, tested, reviewed, and hardened in the target environment.

## Local Runtime Verification

- The project is prepared to run with standard Laravel commands.
- This Codex environment does not currently have `php`, `composer`, or `npm`
  installed in PATH, so runtime commands cannot be executed here.
- See `TEST_REPORT.md` for the exact command attempts and results.

## Authorization

- Permissions are enum-based and enforced through route middleware plus
  Laravel Policies and controller checks.
- Policies exist for the current core models, but future modules must keep
  policy coverage, route middleware, and controller gates aligned.
- Organization isolation is implemented in controller queries, policies, and
  guards, but a production version should continue evaluating whether shared
  query helpers or global organization scopes are useful for future modules.

## Payments

- No proration for partial contract periods.
- A command exists to mark overdue payments, but production scheduling still
  needs server cron configuration.
- No support for payment reversals or refunds.
- No separate payment transaction ledger; each scheduled payment stores its
  current paid amount.

## Contracts

- Contract editing avoids schedule replacement once payments have been recorded,
  but advanced amendment workflows are not implemented.
- Unit availability checks cover current long-term active contract overlap, but
  future short-stay availability needs a separate booking calendar model.
- Renewal preparation exists for the current long-term rental workflow, but
  advanced renewal negotiation and approval workflows are not implemented.
- Termination lifecycle support exists, but full settlement accounting is not
  implemented.

## Reports

- Reports are basic business summaries, not audited accounting statements.
- Date range filters exist for current report views, but reporting is still not
  a complete accounting, owner-statement, or reconciliation system.
- Tax/VAT handling is not implemented.
- Multi-currency is not implemented.

## Files

- Payment proofs and invoices use basic image validation.
- Sensitive documents are stored privately by default and served through
  authorized application routes, but expiring signed links are not implemented.
- No virus scanning or advanced file validation.

## UI

- UI is mobile-first and simple.
- Mobile usability has been addressed in Blade/CSS structure, but visual browser
  QA requires running the app in a real browser.
- Arabic RTL direction readiness is started, but full translation files are not
  built.

## Testing

- Feature tests exist for demo logins, main pages, role restrictions,
  organization isolation, and PDF routes.
- Those tests still need to be executed in an environment with PHP, Composer,
  Node.js, and PostgreSQL installed.
- No full browser automation test suite is included.
