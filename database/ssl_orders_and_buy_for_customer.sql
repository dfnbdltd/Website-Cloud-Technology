-- =====================================================================
-- SSL orders + "buy for a customer" (domains): manual SQL.
-- Use this INSTEAD of `php artisan migrate`.
-- Database: MySQL / MariaDB (the same schema your Laravel app uses).
--
-- 1. Take a backup first (phpMyAdmin > Export).
-- 2. Run the steps IN ORDER. Each step says what to expect.
-- 3. Safe to re-run: step 1 uses IF NOT EXISTS, step 2 is guarded by a check.
-- =====================================================================

-- ---------------------------------------------------------------------
-- STEP 0 (check) - things the SSL flow already needs. Every query must
-- return 1 row. If one returns nothing, run the migration/SQL that
-- creates it first (they come from earlier releases, not this one).
-- ---------------------------------------------------------------------
SHOW TABLES LIKE 'ssl_plans';
SHOW TABLES LIKE 'ssl_plan_features';
SHOW COLUMNS FROM ssl_plans LIKE 'resellerclub_plan_id';
SHOW COLUMNS FROM ssl_plan_features LIKE 'shown_to_customers';
SHOW COLUMNS FROM invoice_items LIKE 'purchase_price';
SHOW TABLES LIKE 'invoices';

-- ---------------------------------------------------------------------
-- STEP 1 - create the SSL orders table (new table; nothing existing is touched)
-- One row = one certificate for one domain.
--   user_id            who OWNS the certificate (the customer)
--   placed_by_user_id  who PLACED/PAID the order (differs when buying for a customer)
--   total              BDT charged, frozen when the order is placed
--   purchase_price     your ResellerClub cost for the same term in BDT (NULL if unreadable)
--   exchange_rate      USD->BDT rate used when the order was placed
--   status             payment_pending -> paid -> processing -> active | failed
--   payment_status     pending -> initiated -> paid | cancelled
--   reseller_order_id / registration_status  filled by your "order on ResellerClub" step
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ssl_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` VARCHAR(40) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `placed_by_user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `ssl_plan_id` INT UNSIGNED NULL DEFAULT NULL,
  `plan_key` VARCHAR(80) NOT NULL,
  `plan_name` VARCHAR(120) NOT NULL,
  `resellerclub_plan_id` INT UNSIGNED NULL DEFAULT NULL,
  `domain` VARCHAR(255) NOT NULL,
  `years` INT UNSIGNED NOT NULL DEFAULT 1,
  `total` DECIMAL(12,2) NOT NULL,
  `purchase_price` DECIMAL(12,2) NULL DEFAULT NULL,
  `exchange_rate` DECIMAL(12,4) NULL DEFAULT NULL,
  `price_source` VARCHAR(20) NULL DEFAULT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'payment_pending',
  `payment_method` VARCHAR(30) NULL DEFAULT NULL,
  `payment_status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `provider` VARCHAR(30) NULL DEFAULT NULL,
  `provider_payment_id` VARCHAR(255) NULL DEFAULT NULL,
  `customer_name` VARCHAR(255) NOT NULL,
  `customer_email` VARCHAR(255) NOT NULL,
  `customer_phone` VARCHAR(30) NULL DEFAULT NULL,
  `reseller_order_id` VARCHAR(40) NULL DEFAULT NULL,
  `registration_status` VARCHAR(40) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ssl_orders_reference_unique` (`reference`),
  KEY `ssl_orders_user_id_index` (`user_id`),
  KEY `ssl_orders_placed_by_user_id_index` (`placed_by_user_id`),
  KEY `ssl_orders_status_index` (`status`),
  KEY `ssl_orders_payment_status_index` (`payment_status`),
  KEY `ssl_orders_provider_payment_id_index` (`provider_payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- STEP 2a (check) - must return 0 rows. If it returns a row, the column
-- already exists: SKIP step 2b.
-- ---------------------------------------------------------------------
SHOW COLUMNS FROM domain_orders LIKE 'placed_by_user_id';

-- STEP 2b - remember who placed a domain order for another customer.
-- user_id stays the OWNER (the domain appears in their My Store); this new
-- column lets the buyer still open their own order confirmation.
ALTER TABLE `domain_orders`
  ADD COLUMN `placed_by_user_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `user_id`,
  ADD KEY `domain_orders_placed_by_user_id_index` (`placed_by_user_id`);

-- STEP 2c - old orders were always placed by their owner.
UPDATE `domain_orders` SET `placed_by_user_id` = `user_id`
WHERE `placed_by_user_id` IS NULL AND `user_id` IS NOT NULL;

-- ---------------------------------------------------------------------
-- STEP 3 (optional) - tell Laravel these two migrations are already done,
-- so a future `php artisan migrate` does not try them again. (Both migration
-- files also skip themselves when the table/column exists, so this is only
-- tidiness.) Skip any line whose name already appears in `migrations`.
-- ---------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_17_000000_create_ssl_orders_table', b.`next_batch`
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS `next_batch` FROM `migrations`) b
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `migration` FROM `migrations`) m WHERE m.`migration` = '2026_11_17_000000_create_ssl_orders_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_17_000100_add_placed_by_to_domain_orders', b.`next_batch`
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS `next_batch` FROM `migrations`) b
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `migration` FROM `migrations`) m WHERE m.`migration` = '2026_11_17_000100_add_placed_by_to_domain_orders');

-- ---------------------------------------------------------------------
-- STEP 4 (verify)
-- ---------------------------------------------------------------------
SHOW COLUMNS FROM ssl_orders;                                  -- 26 columns
SHOW COLUMNS FROM domain_orders LIKE 'placed_by_user_id';      -- 1 row
SELECT COUNT(*) AS orders_missing_placed_by FROM domain_orders
WHERE placed_by_user_id IS NULL AND user_id IS NOT NULL;       -- 0

-- =====================================================================
-- ROLLBACK (only if you must undo; take a backup first)
-- ALTER TABLE domain_orders DROP INDEX domain_orders_placed_by_user_id_index, DROP COLUMN placed_by_user_id;
-- DROP TABLE ssl_orders;   -- destroys every SSL order
-- =====================================================================
