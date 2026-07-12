-- ============================================================
-- Adds configurable login credentials: a `username` identifier for parents,
-- and per-school toggles for how students/parents log in (email+password vs
-- admission no.+PIN vs username+password). PINs reuse the existing
-- users.password_hash column (hashed/verified exactly like a password).
-- (run once against a database created before this migration)
-- ============================================================

ALTER TABLE users
    ADD COLUMN username VARCHAR(60) DEFAULT NULL AFTER email,
    ADD UNIQUE KEY unique_username_tenant (username, tenant_id);

ALTER TABLE tenants
    ADD COLUMN student_login_mode ENUM('email_password','admission_pin') NOT NULL DEFAULT 'admission_pin',
    ADD COLUMN parent_login_mode  ENUM('email_password','username_password') NOT NULL DEFAULT 'username_password' AFTER student_login_mode;
