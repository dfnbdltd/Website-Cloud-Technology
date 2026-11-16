-- =====================================================================
-- Domain transfer sync: manual SQL (use this INSTEAD of `php artisan migrate`)
-- Database: MySQL / MariaDB. Table: domain_order_items
-- Take a backup first (phpMyAdmin > Export), then run the steps IN ORDER.
-- =====================================================================

-- STEP 0 (check) - both queries must return 0 rows. If one returns a row,
-- that column already exists: skip the matching ADD COLUMN line in step 1.
SHOW COLUMNS FROM domain_order_items LIKE 'expires_at';
SHOW COLUMNS FROM domain_order_items LIKE 'transfer_completed_at';

-- STEP 1 - the only schema change (two nullable columns; nothing existing is touched)
ALTER TABLE domain_order_items
  ADD COLUMN expires_at TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN transfer_completed_at TIMESTAMP NULL DEFAULT NULL;

-- STEP 2 - data fix: transfers an admin already marked "Success" by hand
-- (order status = active) become "transferred", which unlocks their Manage page.
-- (No schema change is needed for the new value: registration_status is a plain string column.)
UPDATE domain_order_items i
JOIN domain_orders o ON o.id = i.domain_order_id
SET i.registration_status = 'transferred',
    i.transfer_completed_at = NOW()
WHERE i.order_type = 'transfer'
  AND i.registration_status = 'transfer_submitted'
  AND o.status = 'active'
  AND o.payment_status = 'paid';

-- STEP 3 (verify) - your netsgoods.com row should show registration_status = transferred
SELECT i.id, i.domain, i.order_type, i.registration_status, i.reseller_order_id,
       i.expires_at, i.transfer_completed_at, o.reference, o.status AS order_status
FROM domain_order_items i
JOIN domain_orders o ON o.id = i.domain_order_id
WHERE i.order_type = 'transfer';

-- STEP 4 (optional, netsgoods.com only) - store ResellerClub's real expiry now.
-- If you skip it, the site reads it from ResellerClub by itself the first time the customer opens My Domains.
UPDATE domain_order_items SET expires_at = '2028-02-15 00:00:00' WHERE domain = 'netsgoods.com' AND order_type = 'transfer';

-- OPTIONAL - only if you ever run `php artisan migrate` later. The migration is
-- already safe to re-run (it skips existing columns), so this is not required.
INSERT INTO migrations (migration, batch)
SELECT '2026_11_16_000000_add_transfer_sync_fields_to_domain_order_items', COALESCE(MAX(batch), 0) + 1
FROM migrations;

-- ROLLBACK (only to undo; the code keeps working without these columns only if the
-- previous version of the app is restored)
-- ALTER TABLE domain_order_items DROP COLUMN expires_at, DROP COLUMN transfer_completed_at;
