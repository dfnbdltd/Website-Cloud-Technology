-- =====================================================================
-- Organization Name Consent (ask once): manual SQL (use this INSTEAD of `php artisan migrate`)
-- Database: MySQL / MariaDB. Table: domain_order_items
-- Take a backup first (phpMyAdmin > Export), then run the steps IN ORDER.
-- =====================================================================

-- STEP 0 (check) - both queries must return 0 rows. If one returns a row,
-- that column already exists: skip the matching ADD COLUMN line in step 1.
SHOW COLUMNS FROM domain_order_items LIKE 'org_consent_choice';
SHOW COLUMNS FROM domain_order_items LIKE 'org_consent_responded_at';

-- STEP 1 - the only schema change (two nullable columns; nothing existing is touched)
ALTER TABLE domain_order_items
  ADD COLUMN org_consent_choice VARCHAR(20) NULL DEFAULT NULL,
  ADD COLUMN org_consent_responded_at TIMESTAMP NULL DEFAULT NULL;

-- STEP 2 (verify) - both columns exist and are NULL for every existing domain
-- (so each existing domain shows the consent box once, until its customer answers)
SELECT id, domain, org_consent_choice, org_consent_responded_at FROM domain_order_items LIMIT 20;

-- OPTIONAL - a domain whose customer ALREADY answered at ResellerClub before this update
-- and should not see the box any more (replace the domain name):
-- UPDATE domain_order_items
--    SET org_consent_choice = 'accepted', org_consent_responded_at = NOW()
--  WHERE domain = 'example.com';

-- OPTIONAL - only if you ever run `php artisan migrate` later. The migration is
-- already safe to re-run (it skips existing columns), so this is not required.
INSERT INTO migrations (migration, batch)
SELECT '2026_11_19_000000_add_org_consent_to_domain_order_items', COALESCE(MAX(batch), 0) + 1
FROM migrations;

-- ROLLBACK (only to undo; restore the previous version of the app too)
-- ALTER TABLE domain_order_items DROP COLUMN org_consent_choice, DROP COLUMN org_consent_responded_at;
