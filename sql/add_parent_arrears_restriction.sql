-- ============================================================
-- Adds an opt-in per-school toggle: when enabled, parents with an
-- overdue (unpaid/partial, past due_date) invoice on any linked child
-- lose access to child detail/report-card pages in the parent portal
-- until the balance is cleared. Off by default — existing behavior
-- (unrestricted access) is unchanged unless a School Admin turns it on.
-- (run once against a database created before this migration)
-- ============================================================

ALTER TABLE tenants
    ADD COLUMN restrict_parent_arrears TINYINT(1) NOT NULL DEFAULT 0 AFTER parent_login_mode;
