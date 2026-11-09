# Deploying to faith.ledasystems.site

Everything below has been tested against a copy of this codebase. Pick **one**
of the three routes in step 2 — whichever matches how you normally reach the
server — then do steps 3 and 4 regardless of which you chose.

Two files on the server must **never** be overwritten:

| Path | Why |
|---|---|
| `config/database.php` | Holds the live database name, user and password. It is excluded from git and from the zip, so neither route will touch it. |
| `public/uploads/` | The school's logo, leadership photos, award photos, news images. Only `.gitkeep` placeholders are in the repo, so nothing you upload will replace them. |

---

## 1. Take a backup first

In cPanel → **phpMyAdmin**, select the school's database, choose **Export**
→ **Quick** → **Go**. Keep the `.sql` file somewhere safe.

Then in cPanel → **File Manager**, select the site folder, click **Compress**,
and keep that archive too. If anything goes wrong, these two put the site back
exactly as it was.

---

## 2. Put the new files on the server

### Route A — `git pull` on the server (best, if you have SSH or cPanel Terminal)

```bash
cd ~/public_html          # or wherever the site lives
git pull origin master
```

`config/database.php` is listed in `.gitignore`, so your live database
settings are left alone. If git reports local changes blocking the pull, stop
and tell me what it says rather than forcing it.

### Route B — cPanel File Manager (no SSH needed)

1. Download the release archive (see the end of this file for its location).
2. cPanel → **File Manager** → open the site folder → **Upload** the zip.
3. Select the uploaded zip → **Extract** → extract into the site folder.
4. Choose **Overwrite** when asked. The zip contains no `config/database.php`
   and no uploaded images, so neither is at risk.
5. Delete the zip afterwards so it is not publicly reachable.

### Route C — FTP (FileZilla or similar)

Upload these folders, overwriting what is there:

```
app/      core/      public/      sql/
config/app.php        config/routes.php
```

Do **not** upload `config/database.php`. Do **not** delete `public/uploads/`.

---

## 3. Run one database file

Only one migration needs running by hand — the rest of the schema updates
itself on the first page load.

cPanel → **phpMyAdmin** → select the school's database → **Import** →
choose `sql/add_finance_system.sql` → **Go**.

This adds the missing `finance.approve` permission and grants it to any role
that already has `settings.manage`. Every statement in it is guarded with
`WHERE NOT EXISTS`, so running it twice changes nothing — verified.

To confirm it worked, run this in phpMyAdmin's **SQL** tab:

```sql
SELECT COUNT(*) FROM permissions WHERE module = 'finance' AND action = 'approve';
```

`1` means it is in.

---

## 4. Refresh your browser once, properly

Press **Ctrl + F5** (or **Ctrl + Shift + R**) on the school site after
deploying. Until this release the stylesheet was served without a version tag,
so your browser is still holding the old one. From this release onward every
CSS change carries its own version and this step is never needed again.

---

## What is in this release, and how to check each piece

Work through these after deploying. Each one has a specific thing to look at,
so you can tell what landed rather than taking it on trust.

### 1. A staff edit can no longer wipe someone's login email

Previously, saving the **Staff** form with the email box empty cleared the only
thing that account could sign in with, said "Staff details updated", and locked
that person out — any password, permanently.

**Check:** Staff → edit any member → clear the email box → Save. It should
refuse with a message. The email should still be there afterwards.

> If an admin is locked out right now, run this in phpMyAdmin first, then sign
> in with that address and set a password from **Login Accounts**:
> ```sql
> SELECT id, name, email, username, status FROM users
>  WHERE role_id = (SELECT id FROM roles WHERE name = 'School Admin');
> ```
> Note the `id` of the account you want, then:
> ```sql
> UPDATE users SET email = 'admin@celdiacademy.com', status = 'active' WHERE id = <that id>;
> ```

### 2. Saved grades are visible again

Marks were always being saved, but the screen sent you to the exam list
afterwards and reopening Enter Grades gave a blank sheet, so the work looked
lost.

**Check:** Grades & Exams → Enter Grades → pick a class and exam → type a few
marks → Save. You should land back on the same sheet with your marks in the
boxes and a green line saying how many are recorded. Leave the page, come back
via the menu, and it should still be there.

### 3. Pass and fail marks are coloured everywhere

69 and below prints **red**, 70 and above prints **blue** — on the report card,
the Grade Report, the mark entry sheet, Rankings, the student profile, and both
the student and parent portals.

**Check:** open any student's report card. If marks are still black, step 4
above was skipped.

> Still open: your grading scale reads `60 - 71 = Concept Not Understood (C)`,
> so 70 and 71 now print blue as passes while still lettered **C** on the same
> card. Tell me the bands you want and I will align them.

### 4. The dashboard says where the money is

The finance cards were locked to the academic year marked current and showed a
blank card when that year held nothing, which read like lost data.

**Check:** the dashboard's **Finances** card now has a year picker. If a year
holds no entries but the books are not empty, it names the amount and the year
it is in, and links straight there. Entries dated outside every academic year
are called out separately.

### 5. Make-up Exams

A new section under **Online Learning**. A student who missed a test sits it
online, and only the students you name on its sitting list can see it.

**Check:** Online Learning → Make-up Exams → set one up, add a student with the
reason they missed it, add a question, publish. It appears on that student's
**Make-up Exams** page in their portal and nowhere else.

---

## If you get "HTTP ERROR 500" after deploying

A 500 with no message means PHP stopped before it could render anything, and
`config/app.php` has `debug => false` so the reason is not shown. Do not guess:

1. Upload `public/health.php` from the release to the site's public folder.
2. Open `https://your-site/health.php`.
3. It names the exact problem — a file that did not upload fully, a missing
   `config/database.php`, a wrong PHP version, a missing extension, a database
   it cannot reach.
4. Fix what it names, reload the site, then **delete `health.php`**.

The commonest cause is a large file that did not finish uploading:
`core/Controller.php` and `app/Controllers/AllControllers.php` are around 80 KB
each and load on every request, so a half-written copy takes the whole site
down. The check catches exactly that and names the file.

If `health.php` reports everything fine, the failure is inside a page rather
than at load time: open **cPanel → Metrics → Errors**, or the `error_log` file
in the site folder, and read the newest lines.

## If something looks wrong afterwards

Tell me which of the five checks above failed and what you saw. Do not roll
back on your own unless the site is down — most of what can go wrong here is a
stale browser cache (step 4) or the one SQL file not having run (step 3).

---

## Where the release archive is

A zip of the current code is ready at:

```
C:\PROGRA~1\KMSpico	emp\claude\c--Liberia-School-system-liberia-school-management-system-cd3f81-eb63-4e48-a4ee-e8832f039bf6\scratchpad\celdi-deploy.zip
```

391 files, 5.4 MB. Checked: it contains no `config/database.php` (only
`config/database.php.example`) and no uploaded images — just the empty
`public/uploads/logos/` placeholder, which will not disturb what is on the
server. It is built from the latest commit, so ask for a fresh one if more
changes land before you deploy.
