-- ============================================================
-- Website module (School panel → Website): lets a School Admin edit the
-- public website's pages, news & events, gallery and leadership team.
--
-- The tables are also created automatically the first time anyone opens the
-- Website module, so running this file is optional for them. What it adds that
-- the app cannot is the website.manage permission, so the module can be
-- granted to a role (e.g. a communications officer) without full settings
-- access. Until then, anyone with settings.manage can use it.
--
-- Safe to re-run.
-- ============================================================

CREATE TABLE IF NOT EXISTS website_content (
    tenant_id INT UNSIGNED NOT NULL,
    -- A field key from app/Services/WebsiteContent.php, e.g. 'home.hero_title'.
    -- Only fields the school has changed are stored; the rest use built-in text.
    content_key VARCHAR(120) NOT NULL,
    content MEDIUMTEXT,
    updated_by INT UNSIGNED DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (tenant_id, content_key),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS website_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    category ENUM('news','event') NOT NULL DEFAULT 'news',
    title VARCHAR(200) NOT NULL,
    excerpt VARCHAR(400) DEFAULT NULL,
    body MEDIUMTEXT,
    image_url VARCHAR(255) DEFAULT NULL,
    event_date DATE DEFAULT NULL,
    event_location VARCHAR(200) DEFAULT NULL,
    -- A future date schedules the post: it stays off the site until then.
    published_on DATE NOT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_website_posts_tenant (tenant_id, is_published, published_on),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS website_gallery (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    caption VARCHAR(200) DEFAULT NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_website_gallery_tenant (tenant_id, is_active, sort_order),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS website_leaders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    position VARCHAR(150) NOT NULL,
    bio VARCHAR(600) DEFAULT NULL,
    photo_url VARCHAR(255) DEFAULT NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_website_leaders_tenant (tenant_id, is_active, sort_order),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- website.manage, granted to every role that can already manage settings.
INSERT INTO permissions (name, module, action, description)
SELECT 'website.manage', 'website', 'manage', 'Edit the public school website' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='website' AND action='manage');

INSERT INTO role_permissions (role_id, permission_id)
SELECT rp.role_id, pw.id
  FROM role_permissions rp
  JOIN permissions ps ON ps.id = rp.permission_id AND ps.module = 'settings' AND ps.action = 'manage'
  JOIN permissions pw ON pw.module = 'website' AND pw.action = 'manage'
 WHERE NOT EXISTS (SELECT 1 FROM role_permissions x WHERE x.role_id = rp.role_id AND x.permission_id = pw.id);
