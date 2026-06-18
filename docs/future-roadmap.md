# Future Roadmap

## Phase 1: Stabilize MVP

- Install into a fresh Laravel app.
- Run migrations, seeders, and tests.
- Replace Tailwind CDN with Vite build.
- Add Form Request classes for all create/update flows.
- Add Laravel Policies for every model.
- Add missing indexes for dashboard/report queries.
- Add role-based feature tests.

## Phase 2: Product Readiness

- Add date range filters to reports.
- Add building/unit/tenant search across all index pages.
- Add contract renewal workflow.
- Add contract termination workflow.
- Add automatic overdue status command and scheduled job.
- Add payment receipts with better template design.
- Add private file storage for proofs and invoices.
- Add export options for CSV/XLSX.

## Phase 3: Localization And Regional Fit

- Add translation files for English and Arabic.
- Complete RTL QA across all screens.
- Add locale switcher.
- Add regional date/number formatting.
- Add VAT/tax fields if required by market.

## Phase 4: Accounting Depth

- Add payment transaction ledger.
- Support partial payments over multiple receipts.
- Add refund/reversal handling.
- Add expense approval flow.
- Add owner statements.
- Add bank reconciliation imports.

## Phase 5: PWA And Field Usage

- Basic installability foundation is present through a manifest, app icons, and a network-only service worker.
- Offline application functionality is intentionally not included.
- Production installation still requires proper HTTPS deployment and browser verification.
- Optimize caretaker payment recording flow.
- Add camera-first proof upload.
- Add push notifications for overdue payments and contracts ending soon.

## Phase 6: SaaS Operations

- Add organization onboarding.
- Add invitation emails.
- Add subscription/billing module if needed.
- Add audit log search and retention policies.
- Add backups, monitoring, and error reporting.
