-- =====================================================================
-- Hosting regions (Local / USA / WordPress): manual SQL.
-- Use this INSTEAD of `php artisan migrate` for this one change.
-- Database: MySQL / MariaDB. Table: hosting_plans
--
-- 1. Take a backup first (phpMyAdmin > Export).
-- 2. Run the steps IN ORDER. Safe to re-run: step 1 is guarded by step 0.
--
-- What it does: adds ONE column, `region`. Every existing plan becomes
-- region 'local', so today's Shared / VPS / Dedicated pages are unchanged.
-- USA and WordPress start empty; fill them from Admin > Hosting plans
-- (use the "Copy from Local" button there).
-- =====================================================================

-- ---------------------------------------------------------------------
-- STEP 0 (check) - must return 0 rows. If it returns a row, the column
-- already exists: SKIP step 1 and go to step 2.
-- ---------------------------------------------------------------------
SHOW COLUMNS FROM hosting_plans LIKE 'region';

-- ---------------------------------------------------------------------
-- STEP 1 - the only schema change (nothing existing is touched)
-- ---------------------------------------------------------------------
ALTER TABLE `hosting_plans`
  ADD COLUMN `region` VARCHAR(20) NOT NULL DEFAULT 'local' AFTER `type`,
  ADD KEY `hosting_plans_region_index` (`region`);

-- ---------------------------------------------------------------------
-- STEP 2 - make sure every existing plan is 'local'
-- ---------------------------------------------------------------------
UPDATE `hosting_plans` SET `region` = 'local' WHERE `region` IS NULL OR `region` = '';

-- ---------------------------------------------------------------------
-- STEP 3 (optional) - tell Laravel this migration is already done, so a
-- future `php artisan migrate` does not try it again. (The migration also
-- skips itself when the column exists, so this is only tidiness.)
-- ---------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_23_000000_add_region_to_hosting_plans', b.`next_batch`
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS `next_batch` FROM `migrations`) b
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `migration` FROM `migrations`) m WHERE m.`migration` = '2026_11_23_000000_add_region_to_hosting_plans');

-- ---------------------------------------------------------------------
-- STEP 4 (verify)
-- ---------------------------------------------------------------------
SHOW COLUMNS FROM hosting_plans LIKE 'region';                      -- 1 row
SELECT `region`, `type`, COUNT(*) AS plans FROM `hosting_plans`
GROUP BY `region`, `type` ORDER BY `region`, `type`;                -- local: shared 5, vps 4, dedicated 5

-- =====================================================================
-- ROLLBACK (only if you must undo; take a backup first)
-- Deletes USA / WordPress plans and their features, then drops the column.
-- DELETE f FROM hosting_plan_features f JOIN hosting_plans p ON p.id = f.hosting_plan_id WHERE p.region <> 'local';
-- DELETE FROM hosting_plans WHERE region <> 'local';
-- ALTER TABLE hosting_plans DROP INDEX hosting_plans_region_index, DROP COLUMN region;
-- DELETE FROM migrations WHERE migration = '2026_11_23_000000_add_region_to_hosting_plans';
-- =====================================================================
