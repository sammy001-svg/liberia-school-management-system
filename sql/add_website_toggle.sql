-- ============================================================
-- Public website on/off switch (School Settings → Public Website).
-- When on, "/" and the website pages (/about-us, /divisions, /admissions …)
-- show the school's public site; when off, they all redirect to /login,
-- which is how "/" behaved before the website existed.
-- On by default. Safe to run more than once.
-- ============================================================

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tenants' AND COLUMN_NAME='website_enabled')=0,
    'ALTER TABLE tenants ADD COLUMN website_enabled TINYINT(1) NOT NULL DEFAULT 1', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
