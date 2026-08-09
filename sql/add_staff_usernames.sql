-- Staff usernames — so an admin can tell a teacher how to sign in
-- ---------------------------------------------------------------------------
-- Staff sign in with their EMAIL, but teachers imported from a CSV or created
-- without one had neither an email nor a username, which left them unable to log
-- in at all while still looking like active accounts on screen.
--
-- This backfills a username for every staff-side account missing one, so there is
-- always a usable sign-in identifier to show on the profile. AuthController now
-- accepts either the email or the username for staff logins.
--
-- Students and parents are skipped: students sign in with their admission number
-- and PIN, and parents already have their own username flow.
--
-- Safe to re-run: only ever fills usernames that are still empty.

-- Pass 1 — give everyone the plain slug of their name where that slug is free.
-- The candidate must not collide with an existing username in the same tenant,
-- and must not be wanted by two people at once (those fall through to pass 2).
UPDATE users u
JOIN (
    SELECT c.id,
           c.tenant_id,
           c.slug
      FROM (
            SELECT id, tenant_id,
                   TRIM(BOTH '.' FROM LOWER(REGEXP_REPLACE(name, '[^a-zA-Z0-9]+', '.'))) AS slug
              FROM users
             WHERE (username IS NULL OR username = '')
               AND role_id IN (SELECT id FROM roles WHERE name NOT IN ('Student', 'Parent'))
           ) c
     WHERE c.slug <> ''
       -- not already taken by an existing account in this tenant
       AND NOT EXISTS (
             SELECT 1 FROM (SELECT username, tenant_id FROM users) t
              WHERE t.username = c.slug AND t.tenant_id <=> c.tenant_id
           )
       -- and not contested by another account being backfilled in the same pass
       AND 1 = (
             SELECT COUNT(*) FROM (
                    SELECT id, tenant_id,
                           TRIM(BOTH '.' FROM LOWER(REGEXP_REPLACE(name, '[^a-zA-Z0-9]+', '.'))) AS slug
                      FROM users
                     WHERE (username IS NULL OR username = '')
                       AND role_id IN (SELECT id FROM roles WHERE name NOT IN ('Student', 'Parent'))
                  ) d
              WHERE d.slug = c.slug AND d.tenant_id <=> c.tenant_id
           )
) pick ON pick.id = u.id
SET u.username = pick.slug;

-- Pass 2 — anyone still without one (duplicate names, or a name that slugged to
-- nothing) gets the slug plus their user id, which is unique by construction.
UPDATE users u
SET u.username = CONCAT(
        NULLIF(TRIM(BOTH '.' FROM LOWER(REGEXP_REPLACE(u.name, '[^a-zA-Z0-9]+', '.'))), ''),
        '.', u.id)
WHERE (u.username IS NULL OR u.username = '')
  AND u.role_id IN (SELECT id FROM roles WHERE name NOT IN ('Student', 'Parent'));

-- Anything whose name slugged to empty ends up as ".<id>"; give those a stable
-- prefix rather than a leading dot.
UPDATE users
   SET username = CONCAT('staff', username)
 WHERE username LIKE '.%';
