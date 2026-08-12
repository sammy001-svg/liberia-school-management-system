-- Login page carousel — editable school announcements
-- ---------------------------------------------------------------------------
-- The login carousel was three slides hard-coded into the view, with background
-- photos hot-linked from an external image host. This makes them tenant-owned
-- content the school can edit, with images uploaded to the server so the page
-- still renders correctly on a school network with no outside internet access.
--
-- When a tenant has no slides of its own, the login view falls back to the
-- original built-in three, so a fresh install never shows an empty carousel.
--
-- Safe to re-run.

CREATE TABLE IF NOT EXISTS login_slides (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    caption VARCHAR(400) DEFAULT NULL,
    -- Full public URL as returned by Controller::handleImageUpload(); NULL means
    -- render the slide on the plain brand gradient instead of a photo.
    image_url VARCHAR(255) DEFAULT NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    -- Optional scheduling: a term-dated announcement can be set to appear and
    -- disappear on its own. NULL on either side means "no bound".
    starts_on DATE DEFAULT NULL,
    ends_on DATE DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_login_slides_tenant (tenant_id, is_active, sort_order),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
