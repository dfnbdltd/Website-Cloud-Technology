# After Merge — Deployment Checklist

This update covers the registration page, the admin invoice editor, and the invoice PDF.

- **No database migration is required.**
- **No new environment variables are required.**
- Use **PHP 8.0** (this project runs Laravel 6; newer PHP versions may fail).

## 1. Update the code

```bash
git checkout main
git pull origin main
```

If `vendor/` is not already installed:

```bash
composer install --no-dev --optimize-autoloader
```

## 2. Clear Laravel caches

Run from the project root:

```bash
php artisan optimize:clear
```

At minimum, clear the changed Blade views and routes:

```bash
php artisan view:clear
php artisan route:clear
```

This update adds no migrations, so `php artisan migrate` is not required.

## 3. Smoke test

1. Open `/register` — one wide card with a **Full Name** field, no marketing pane, and no Address 2 / State fields.
2. Log in as admin → open an invoice → click **Download customer information PDF**.
   Confirm it is A4 landscape and includes purchase, sales, and profit columns.
3. Log in as the customer → open the same invoice → click **Download invoice PDF**.
   Confirm purchase price and profit are **not** present.
4. In the admin invoice editor, change the status **paid ↔ unpaid**, save, and confirm the invoice page updates.
5. On the admin invoice page, test **Mark as paid** / **Mark as unpaid**.

## 4. Tests (optional)

Run this in staging/CI where dev dependencies are installed (the production `--no-dev` install does not include PHPUnit):

```bash
php vendor/bin/phpunit --filter InvoiceWorkflowTest
```

Expected: **15 tests pass**.

Known unrelated failure in the full suite: `LandingAdminLoginTest` still expects “Admin login” on the landing page. It is not caused by this update.
