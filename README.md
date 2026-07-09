# Liberia School Management System

Liberia School Management System (LiberiaSMS) is a comprehensive educational management platform designed to streamline administrative tasks, enhance academic tracking, and improve communication between schools, teachers, students, and parents.

## 🚀 Key Features

### 🏛️ Administration

- **School Management**: Full control over students, teachers, classes, and departments.

### 🎓 Academic Excellence

- **Attendance Tracking**: Easy-to-use interface for marking attendance and generating reports.
- **Grade Management**: Simplified grade entry and automated student progress reports.
- **Timetable Management**: Interactive scheduling for classes and teachers.
- **Analytics Suite**: Advanced academic analytics, student growth tracking, and attendance heatmaps.

### 🏢 University-Specific Modules

- **Program & Course Management**: Structure departments, academic programs, and specific courses.
- **Enrollment System**: Manage student enrollments into specific programs and units.

### 💰 Finance & HR

- **Financial Module**: Invoice generation, payment tracking, and financial reporting.
- **HR & Payroll**: Automated payroll generation and employee leave management.
- **Inventory & Library**: Track school assets and manage library book loans.

### 📱 Portals

- **Student Portal**: Access to personal timetables, grades, and learning materials.
- **Parent Portal**: Real-time updates on children's academic performance, attendance, and financial status.
- **Communication**: Internal messaging system, announcements, and bulk SMS capabilities.

## 🛠️ Technology Stack

- **Backend**: PHP (Custom MVC Framework)
- **Database**: MySQL
- **Frontend**: HTML5, Vanilla CSS, JavaScript
- **Deployment**: Optimized for standard cPanel/Shared Hosting environments.

## ⚙️ Installation & Deployment

### Local Setup
1. **Clone the repository**:
   ```bash
   git clone https://github.com/sammy001-svg/school-management-system.git
   ```

2. **Database Setup**:
   - Create a new MySQL database (e.g., `school_mgmt`).
   - Import the schema from `sql/schema.sql`.

3. **Configuration**:
   - Copy `config/database.php.example` to `config/database.php`.
   - Update the credentials in `config/database.php` to match your local setup.

4. **Web Server**:
   - Point your web server's document root to the `public/` directory.
   - Ensure `mod_rewrite` is enabled if using Apache.

### 🌐 cPanel Deployment (Shared Hosting)

1. **PHP Version**: In cPanel → **MultiPHP Manager** (or **Select PHP Version**), set the domain to **PHP 8.1 or newer** (the code uses `never` return types and other 8.1+ syntax). Make sure the **`pdo_mysql`** extension is enabled — it's on by default on most cPanel hosts, but double-check under **Select PHP Version → Extensions**.
2. **Upload Files**: Upload all project files to your `public_html` directory (or a subdirectory / addon domain folder).
3. **Database**:
   - Use the **MySQL® Database Wizard** in cPanel to create a database, a database user, and a password, and attach the user to the database with **All Privileges**. cPanel usually prefixes both the database and username with your account name (e.g. `cpaneluser_school_mgmt`).
   - Open **phpMyAdmin**, select the new database, and import `sql/schema.sql`.
   - Then import `sql/admin_seed.sql` to create the first School Admin login (`admin@liberiaschool.com` / `Admin@123`).
   - You do **not** need to run `sql/add_admission_fields.sql` — it's an old migration whose columns are already included in `schema.sql`; running it against a fresh database will error with "duplicate column".
4. **Configuration**:
   - On the server, copy `config/database.php.example` to `config/database.php` (or edit the one already on disk if you uploaded it) and fill in the real cPanel database host/name/user/password. This file is git-ignored, so it's never overwritten by a `git pull`.
   - Leave `config/app.php` as committed — `debug` is already `false` and the timezone is set for Liberia. If you ever flip `debug` back to `true` while troubleshooting locally, remember to set it back to `false` before going live: with it on, uncaught errors are shown to visitors with full stack traces instead of a friendly message.
5. **Permissions**: Ensure the `uploads/` directory has write permissions (usually `755`, occasionally `775` depending on the host's PHP execution mode).
6. **Routing** — pick **one** of the following:
   - **Simplest**: leave the whole project (including the root `.htaccess`) in `public_html`. It automatically rewrites every request into the `public/` folder — no extra setup needed. Note this does leave the `app/`, `core/`, `config/`, and `sql/` folders reachable at the same document root as everything else (though not web-servable, since Apache still resolves them as real files/folders and there's no index — `Options -Indexes` in `public/.htaccess` only blocks directory listing inside `public/`); this is fine for most shared-hosting setups but is not the most locked-down option.
   - **More secure (recommended when available)**: if the domain/subdomain lets you set a custom **Document Root** (common for addon domains and subdomains in cPanel's **Domains** page), point it directly at the project's `public/` folder. That keeps `app/`, `core/`, `config/`, and `sql/` completely outside the web-served tree. The root `.htaccess` is simply unused in this case — you can leave it in place, it's harmless.
7. **Security checklist before going live**:
   - [ ] `config/app.php` → `'debug' => false` (already set)
   - [ ] `config/database.php` has the real cPanel credentials, not the local dev ones
   - [ ] Logged in as `admin@liberiaschool.com` and changed the seeded password immediately (`Admin@123` is public — it's in this repo's SQL and documentation)
   - [ ] Force HTTPS (cPanel's free AutoSSL, then "Force HTTPS Redirect" in the SSL/TLS Status page) — the app already detects HTTPS correctly behind cPanel/Cloudflare and marks session cookies `Secure` once it's on
   - [ ] `uploads/` is writable but nothing else needs to be

## 🤝 Contributing
Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License
This project is licensed under the MIT License.
