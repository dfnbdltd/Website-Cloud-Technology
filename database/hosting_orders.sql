-- =====================================================================
-- Hosting orders (bKash payment flow): manual SQL.
-- Use this INSTEAD of `php artisan migrate` (do not run both; the
-- migration skips itself if the table already exists, so it is safe).
-- Database: MySQL / MariaDB.
--
-- 1. Take a backup first (phpMyAdmin > Export).
-- 2. Run the statement below once. It is safe to re-run (IF NOT EXISTS).
--
--   user_id            OWNER of the hosting (the customer when buying for one)
--   placed_by_user_id  who placed / pays the order
--   total              BDT charged, frozen when the order is placed
--   status             payment_pending -> awaiting_setup (paid) -> (later) processing -> active | cancelled
--   payment_status     pending -> initiated -> paid | cancelled
--   customer_email     always the OWNER's account email
-- =====================================================================
CREATE TABLE IF NOT EXISTS `hosting_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` VARCHAR(40) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `placed_by_user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `region` VARCHAR(20) NOT NULL,
  `type` VARCHAR(20) NOT NULL,
  `plan_id` VARCHAR(80) NOT NULL,
  `plan_name` VARCHAR(160) NOT NULL,
  `product` VARCHAR(120) NOT NULL,
  `period` VARCHAR(20) NOT NULL,
  `period_label` VARCHAR(40) NOT NULL,
  `months` INT UNSIGNED NOT NULL DEFAULT 1,
  `domain` VARCHAR(255) NOT NULL,
  `total` DECIMAL(12,2) NOT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'payment_pending',
  `payment_method` VARCHAR(30) NULL DEFAULT NULL,
  `payment_status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `provider` VARCHAR(30) NULL DEFAULT NULL,
  `provider_payment_id` VARCHAR(255) NULL DEFAULT NULL,
  `payment_response` TEXT NULL,
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  `customer_name` VARCHAR(255) NOT NULL,
  `customer_email` VARCHAR(255) NOT NULL,
  `customer_phone` VARCHAR(30) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hosting_orders_reference_unique` (`reference`),
  KEY `hosting_orders_user_id_index` (`user_id`),
  KEY `hosting_orders_placed_by_user_id_index` (`placed_by_user_id`),
  KEY `hosting_orders_status_index` (`status`),
  KEY `hosting_orders_payment_status_index` (`payment_status`),
  KEY `hosting_orders_provider_payment_id_index` (`provider_payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Check: must return 1 row.
SHOW TABLES LIKE 'hosting_orders';
