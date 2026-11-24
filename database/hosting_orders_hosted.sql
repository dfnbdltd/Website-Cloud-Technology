-- =====================================================================
-- Hosting orders: "Mark as hosted" fields. Manual SQL.
-- Use this INSTEAD of `php artisan migrate` for this change.
-- Database: MySQL / MariaDB. Run AFTER database/hosting_orders.sql.
--
-- 1. Take a backup first (phpMyAdmin > Export).
-- 2. Run STEP 1 (check). If it returns 0 rows, run STEP 2.
--    If it returns 2 rows, the columns already exist: skip STEP 2.
--
--   hosted_at   when the admin pressed "Mark as hosted" (status becomes `hosted`)
--   admin_note  optional note from the admin (e.g. cPanel username), emailed to the customer
-- =====================================================================

-- STEP 1 (check)
SHOW COLUMNS FROM `hosting_orders` WHERE Field IN ('hosted_at', 'admin_note');

-- STEP 2
ALTER TABLE `hosting_orders`
  ADD COLUMN `hosted_at` TIMESTAMP NULL DEFAULT NULL AFTER `paid_at`,
  ADD COLUMN `admin_note` TEXT NULL AFTER `hosted_at`;
