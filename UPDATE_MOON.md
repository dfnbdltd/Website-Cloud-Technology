# Deployment Steps — after merging this PR



## Step 1 — `composer install`
- **What:** installs dependencies.
- **Why:** this PR adds one new package, `mpdf/mpdf` (used for the invoice PDF download). `composer.lock` is included, so you get the exact tested version.

## Step 2 — `php artisan migrate`
- **What:** runs the new migration.
- **Why:** creates 2 new tables, `invoices` and `invoice_items`. It only adds tables — nothing existing is changed or dropped, so it's safe to run.

## Step 3 — `php artisan invoices:backfill`
- **What:** one-time command.
- **Why:** creates invoices for all old orders and approved top-ups that existed before this feature, so customers see their full billing history. Safe to run again (won't create duplicates).

## What changed in this PR
- **New tables:** `invoices`, `invoice_items` (1 migration file).
- **New files:** `InvoiceService` + `InvoicePdf` (invoices & PDF), `InvoiceController` (customer view/PDF), backfill command, invoice views (admin list/edit, customer page/PDF), tests.
- **Changed files:** small one-line hooks in existing controllers (order place, bKash paid, wallet credit, admin order/top-up), routes, admin sidebar, customer invoice list. Existing business logic untouched.
- **New dependency:** `mpdf/mpdf ^8.2.6` (DomPDF is blocked by composer security advisories).
- Requires PHP `ext-gd` for the PDF.

## Quick check after deploy
- Admin: `/admin/invoices` shows the invoice list; edit works.
- Customer: Billing → Invoices → View + Download PDF works.
