-- Parent usernames — the parent portal signs in by username, not email
-- ---------------------------------------------------------------------------
-- Companion to add_staff_usernames.sql, which deliberately skipped parents.
-- That assumption was wrong for this install: parents imported before
-- ParentController started generating usernames have a valid password but no
-- username at all, and because tenants.parent_login_mode is 'username_password'
-- they had no way to sign in.
--
-- Accounts created through the Parents screen already get a username, so this
-- only ever fills the historical gap.
--
-- Safe to re-run: only touches usernames that are still empty.

-- Pass 1 — plain slug of the name, where it is free and uncontested.
UPDATE users u
JOIN (
    SELECT c.id, c.tenant_id, c.slug
      FROM (
            SELECT id, tenant_id,
                   TRIM(BOTH '.' FROM LOWER(REGEXP_REPLACE(name, '[^a-zA-Z0-9]+', '.'))) AS slug
              FROM users
             WHERE (username IS NULL OR username = '')
               AND role_id IN (SELECT id FROM roles WHERE name = 'Parent')
           ) c
     WHERE c.slug <> ''
       AND NOT EXISTS (
             SELECT 1 FROM (SELECT username, tenant_id FROM users) t
              WHERE t.username = c.slug AND t.tenant_id <=> c.tenant_id
           )
       AND 1 = (
             SELECT COUNT(*) FROM (
                    SELECT id, tenant_id,
                           TRIM(BOTH '.' FROM LOWER(REGEXP_REPLACE(name, '[^a-zA-Z0-9]+', '.'))) AS slug
                      FROM users
                     WHERE (username IS NULL OR username = '')
                       AND role_id IN (SELECT id FROM roles WHERE name = 'Parent')
                  ) d
              WHERE d.slug = c.slug AND d.tenant_id <=> c.tenant_id
           )
) pick ON pick.id = u.id
SET u.username = pick.slug;

-- Pass 2 — duplicates and unslugifiable names get the id appended.
UPDATE users u
SET u.username = CONCAT(
        NULLIF(TRIM(BOTH '.' FROM LOWER(REGEXP_REPLACE(u.name, '[^a-zA-Z0-9]+', '.'))), ''),
        '.', u.id)
WHERE (u.username IS NULL OR u.username = '')
  AND u.role_id IN (SELECT id FROM roles WHERE name = 'Parent');

UPDATE users
   SET username = CONCAT('parent', username)
 WHERE username LIKE '.%'
   AND role_id IN (SELECT id FROM roles WHERE name = 'Parent');
