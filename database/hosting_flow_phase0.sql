-- =====================================================================
-- Hosting flow, PHASE 0: database changes. Run in phpMyAdmin.
-- Use this INSTEAD of `php artisan migrate` (the migrations skip anything
-- that already exists, so running both is harmless).
-- Database: MySQL / MariaDB. Every new column is NULL-able: old rows keep working.
--
-- 1. Back up first (phpMyAdmin > Export).
-- 2. Run STEP 1. For each column it lists, REMOVE that line from STEP 2
--    (MySQL has no "ADD COLUMN IF NOT EXISTS" on older versions).
-- 3. Run STEP 2, then STEP 3, then the optional STEP 4 statements you want.
-- =====================================================================

-- STEP 1 (check) - 0 rows on each means nothing exists yet.
SHOW COLUMNS FROM `hosting_orders` WHERE Field IN ('plan_specs','admin_seen_at','hosted_by_admin_id','hosted_by_admin_email');
SHOW COLUMNS FROM `invoice_items` LIKE 'period_label';
SHOW TABLES LIKE 'admins';

-- STEP 2 - columns
ALTER TABLE `hosting_orders`
  ADD COLUMN `plan_specs` VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN `admin_seen_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `hosted_by_admin_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  ADD COLUMN `hosted_by_admin_email` VARCHAR(190) NULL DEFAULT NULL,
  ADD KEY `hosting_orders_admin_seen_at_index` (`admin_seen_at`);

ALTER TABLE `invoice_items`
  ADD COLUMN `period_label` VARCHAR(40) NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STEP 3 - tell Laravel these migrations are done (skip a line if its name is already in `migrations`).
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_25_000000_add_plan_specs_and_admin_fields_to_hosting_orders', b.`n`
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS `n` FROM `migrations`) b
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `migration` FROM `migrations`) m WHERE m.`migration` = '2026_11_25_000000_add_plan_specs_and_admin_fields_to_hosting_orders');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_25_000100_add_period_label_to_invoice_items', b.`n`
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS `n` FROM `migrations`) b
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `migration` FROM `migrations`) m WHERE m.`migration` = '2026_11_25_000100_add_period_label_to_invoice_items');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_25_000200_create_admins_table', b.`n`
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS `n` FROM `migrations`) b
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `migration` FROM `migrations`) m WHERE m.`migration` = '2026_11_25_000200_create_admins_table');

-- ---------------------------------------------------------------------
-- STEP 4 - OPTIONAL one-time statements
-- ---------------------------------------------------------------------

-- (a) Existing paid hosting orders count as already seen, so the admin badge starts at 0.
UPDATE `hosting_orders` SET `admin_seen_at` = NOW()
WHERE `payment_status` = 'paid' AND `admin_seen_at` IS NULL;

-- (b) Existing hosting invoices get their term label from the hosting order.
--     Four short statements (one per term) so phpMyAdmin's checker accepts them.
UPDATE `invoice_items` ii
JOIN `invoices` i ON i.`id` = ii.`invoice_id`
JOIN `hosting_orders` h ON h.`id` = i.`source_id`
SET ii.`period_label` = 'Monthly'
WHERE i.`source_type` LIKE '%HostingOrder' AND ii.`item_type` = 'hosting' AND ii.`period_label` IS NULL AND h.`months` = 1;

UPDATE `invoice_items` ii
JOIN `invoices` i ON i.`id` = ii.`invoice_id`
JOIN `hosting_orders` h ON h.`id` = i.`source_id`
SET ii.`period_label` = '1 Year'
WHERE i.`source_type` LIKE '%HostingOrder' AND ii.`item_type` = 'hosting' AND ii.`period_label` IS NULL AND h.`months` = 12;

UPDATE `invoice_items` ii
JOIN `invoices` i ON i.`id` = ii.`invoice_id`
JOIN `hosting_orders` h ON h.`id` = i.`source_id`
SET ii.`period_label` = '2 Years'
WHERE i.`source_type` LIKE '%HostingOrder' AND ii.`item_type` = 'hosting' AND ii.`period_label` IS NULL AND h.`months` = 24;

UPDATE `invoice_items` ii
JOIN `invoices` i ON i.`id` = ii.`invoice_id`
JOIN `hosting_orders` h ON h.`id` = i.`source_id`
SET ii.`period_label` = '3 Years'
WHERE i.`source_type` LIKE '%HostingOrder' AND ii.`item_type` = 'hosting' AND ii.`period_label` IS NULL AND h.`months` = 36;

-- (c) Rebuild plan_specs for existing orders from the plan's CURRENT storage feature
--     ("10 GB NVMe storage" -> "10 GB | NVMe Storage"). One statement per term:
--     the term's own feature wins, the Monthly feature is the fallback. Orders whose
--     plan is gone, or whose feature has no readable size, stay NULL (plan name only).
--     Works on MySQL 5.7+ and MariaDB (no window functions).
UPDATE `hosting_orders` h
SET h.`plan_specs` = (
    SELECT CONCAT(UPPER(SUBSTRING_INDEX(TRIM(f.`value_monthly`), ' ', 2)), ' | ',
                  IF(f.`value_monthly` LIKE '%nvme%', 'NVMe Storage', IF(f.`value_monthly` LIKE '%ssd%', 'SSD Storage', 'Storage')))
    FROM `hosting_plan_features` f
    WHERE f.`hosting_plan_id` = CAST(h.`plan_id` AS UNSIGNED)
      AND f.`feature_group` = 'primary' AND f.`is_available` = 1
      AND f.`value_monthly` REGEXP '(storage|nvme|ssd|disk)'
      AND f.`value_monthly` REGEXP '^[0-9]+(\\.[0-9]+)? ?(TB|GB|MB)'
      AND f.`billing_term` = 'monthly'
    ORDER BY f.`id` LIMIT 1)
WHERE h.`plan_specs` IS NULL AND h.`period` = 'monthly';

UPDATE `hosting_orders` h
SET h.`plan_specs` = (
    SELECT CONCAT(UPPER(SUBSTRING_INDEX(TRIM(f.`value_monthly`), ' ', 2)), ' | ',
                  IF(f.`value_monthly` LIKE '%nvme%', 'NVMe Storage', IF(f.`value_monthly` LIKE '%ssd%', 'SSD Storage', 'Storage')))
    FROM `hosting_plan_features` f
    WHERE f.`hosting_plan_id` = CAST(h.`plan_id` AS UNSIGNED)
      AND f.`feature_group` = 'primary' AND f.`is_available` = 1
      AND f.`value_monthly` REGEXP '(storage|nvme|ssd|disk)'
      AND f.`value_monthly` REGEXP '^[0-9]+(\\.[0-9]+)? ?(TB|GB|MB)'
      AND f.`billing_term` IN ('monthly', 'yearly')
    ORDER BY (f.`billing_term` = 'yearly') DESC, f.`id` LIMIT 1)
WHERE h.`plan_specs` IS NULL AND h.`period` = 'yearly';

UPDATE `hosting_orders` h
SET h.`plan_specs` = (
    SELECT CONCAT(UPPER(SUBSTRING_INDEX(TRIM(f.`value_monthly`), ' ', 2)), ' | ',
                  IF(f.`value_monthly` LIKE '%nvme%', 'NVMe Storage', IF(f.`value_monthly` LIKE '%ssd%', 'SSD Storage', 'Storage')))
    FROM `hosting_plan_features` f
    WHERE f.`hosting_plan_id` = CAST(h.`plan_id` AS UNSIGNED)
      AND f.`feature_group` = 'primary' AND f.`is_available` = 1
      AND f.`value_monthly` REGEXP '(storage|nvme|ssd|disk)'
      AND f.`value_monthly` REGEXP '^[0-9]+(\\.[0-9]+)? ?(TB|GB|MB)'
      AND f.`billing_term` IN ('monthly', 'two_year')
    ORDER BY (f.`billing_term` = 'two_year') DESC, f.`id` LIMIT 1)
WHERE h.`plan_specs` IS NULL AND h.`period` = 'twoYear';

UPDATE `hosting_orders` h
SET h.`plan_specs` = (
    SELECT CONCAT(UPPER(SUBSTRING_INDEX(TRIM(f.`value_monthly`), ' ', 2)), ' | ',
                  IF(f.`value_monthly` LIKE '%nvme%', 'NVMe Storage', IF(f.`value_monthly` LIKE '%ssd%', 'SSD Storage', 'Storage')))
    FROM `hosting_plan_features` f
    WHERE f.`hosting_plan_id` = CAST(h.`plan_id` AS UNSIGNED)
      AND f.`feature_group` = 'primary' AND f.`is_available` = 1
      AND f.`value_monthly` REGEXP '(storage|nvme|ssd|disk)'
      AND f.`value_monthly` REGEXP '^[0-9]+(\\.[0-9]+)? ?(TB|GB|MB)'
      AND f.`billing_term` IN ('monthly', 'three_year')
    ORDER BY (f.`billing_term` = 'three_year') DESC, f.`id` LIMIT 1)
WHERE h.`plan_specs` IS NULL AND h.`period` = 'threeYear';

-- ---------------------------------------------------------------------
-- STEP 5 - admins: INSERT TEMPLATE (used by phase 5; until then the .env admin still logs in).
-- Make a bcrypt hash on your computer / server:
--     php artisan tinker
--     >>> bcrypt('YourStrongPassword')
-- copy the $2y$10$... text it prints and paste it as the password below.
-- Never store a plain password here.
-- ---------------------------------------------------------------------
-- INSERT INTO `admins` (`name`, `email`, `password`, `is_active`, `created_at`, `updated_at`) VALUES
--   ('Admin One',   'admin1@yourdomain.com', '$2y$10$PASTE_HASH_1_HERE', 1, NOW(), NOW()),
--   ('Admin Two',   'admin2@yourdomain.com', '$2y$10$PASTE_HASH_2_HERE', 1, NOW(), NOW()),
--   ('Admin Three', 'admin3@yourdomain.com', '$2y$10$PASTE_HASH_3_HERE', 1, NOW(), NOW());

-- STEP 6 (verify)
SHOW COLUMNS FROM `hosting_orders` WHERE Field IN ('plan_specs','admin_seen_at','hosted_by_admin_id','hosted_by_admin_email');  -- 4 rows
SHOW COLUMNS FROM `invoice_items` LIKE 'period_label';                                                                       -- 1 row
SHOW COLUMNS FROM `admins`;                                                                                                  -- 7 rows

-- =====================================================================
-- ROLLBACK (backup first)
-- ALTER TABLE `hosting_orders` DROP INDEX `hosting_orders_admin_seen_at_index`, DROP COLUMN `plan_specs`, DROP COLUMN `admin_seen_at`, DROP COLUMN `hosted_by_admin_id`, DROP COLUMN `hosted_by_admin_email`;
-- ALTER TABLE `invoice_items` DROP COLUMN `period_label`;
-- DROP TABLE `admins`;
-- =====================================================================
