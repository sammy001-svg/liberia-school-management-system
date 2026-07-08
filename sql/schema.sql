-- ============================================================
-- SCHOOL MANAGEMENT SYSTEM — FULL SCHEMA
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
-- DROP DATABASE IF EXISTS school_mgmt;
-- CREATE DATABASE school_mgmt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE school_mgmt;

-- ============================================================
-- RESELLERS
-- ============================================================
CREATE TABLE resellers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    domain VARCHAR(255) DEFAULT NULL,
    currency VARCHAR(10) DEFAULT 'Ksh',
    logo VARCHAR(255) DEFAULT NULL,
    primary_color VARCHAR(20) DEFAULT '#10B981',
    secondary_color VARCHAR(20) DEFAULT '#059669',
    accent_color VARCHAR(20) DEFAULT '#34D399',
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    status ENUM('active','suspended','pending') DEFAULT 'pending',
    commission_rate DECIMAL(5,2) DEFAULT 0.00,
    max_schools INT DEFAULT 5,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- PLANS / SUBSCRIPTIONS
-- ============================================================
CREATE TABLE plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    max_students INT DEFAULT 500,
    max_teachers INT DEFAULT 50,
    max_staff INT DEFAULT 20,
    price_monthly DECIMAL(10,2) DEFAULT 0.00,
    price_yearly DECIMAL(10,2) DEFAULT 0.00,
    features JSON DEFAULT NULL,
    billing_owner ENUM('platform','reseller') DEFAULT 'platform',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TENANTS (SCHOOLS)
-- ============================================================
CREATE TABLE tenants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reseller_id INT UNSIGNED DEFAULT NULL,
    plan_id INT UNSIGNED DEFAULT NULL,
    institution_type ENUM('high_school','university') NOT NULL DEFAULT 'high_school',
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    domain VARCHAR(255) DEFAULT NULL,
    currency VARCHAR(10) DEFAULT 'Ksh',
    logo VARCHAR(255) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    status ENUM('active','suspended','pending','trial') DEFAULT 'pending',
    trial_ends_at DATE DEFAULT NULL,
    timezone VARCHAR(80) DEFAULT 'UTC',
    academic_year VARCHAR(20) DEFAULT NULL,
    current_semester VARCHAR(20) DEFAULT NULL,
    primary_color VARCHAR(20) DEFAULT '#10B981',
    secondary_color VARCHAR(20) DEFAULT '#059669',
    accent_color VARCHAR(20) DEFAULT '#34D399',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reseller_id) REFERENCES resellers(id) ON DELETE SET NULL,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
);

-- ============================================================
-- TENANT FEATURE FLAGS
-- ============================================================
CREATE TABLE tenant_features (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    feature_key VARCHAR(80) NOT NULL,
    is_enabled TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_feature (tenant_id, feature_key),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- ============================================================
-- ROLES & PERMISSIONS
-- ============================================================
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    scope ENUM('global','reseller','school') DEFAULT 'school',
    tenant_id INT UNSIGNED DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    module VARCHAR(80) NOT NULL,
    action VARCHAR(80) NOT NULL,
    description VARCHAR(255) DEFAULT NULL
);

CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- ============================================================
-- USERS (Unified)
-- ============================================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED DEFAULT NULL,
    reseller_id INT UNSIGNED DEFAULT NULL,
    role_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    gender ENUM('male','female','other') DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    address TEXT DEFAULT NULL,
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    last_login TIMESTAMP NULL DEFAULT NULL,
    remember_token VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_email_tenant (email, tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (reseller_id) REFERENCES resellers(id) ON DELETE SET NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- ============================================================
-- SUBSCRIPTIONS
-- ============================================================
CREATE TABLE subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    reseller_id INT UNSIGNED DEFAULT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0.00,
    billing_cycle ENUM('monthly','yearly') DEFAULT 'monthly',
    status ENUM('active','expired','cancelled','trial') DEFAULT 'active',
    payment_reference VARCHAR(150) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id),
    FOREIGN KEY (reseller_id) REFERENCES resellers(id) ON DELETE SET NULL
);

-- ============================================================
-- ACADEMIC YEARS & TERMS
-- ============================================================
CREATE TABLE academic_years (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current TINYINT(1) DEFAULT 0,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE terms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    academic_year_id INT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current TINYINT(1) DEFAULT 0,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
);

-- ============================================================
-- DEPARTMENTS & PROGRAMS (University)
-- ============================================================
CREATE TABLE departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20) DEFAULT NULL,
    head_user_id INT UNSIGNED DEFAULT NULL,
    description TEXT DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE programs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(30) DEFAULT NULL,
    duration_years TINYINT DEFAULT 4,
    degree_type ENUM('certificate','diploma','bachelor','master','phd') DEFAULT 'bachelor',
    description TEXT DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- ============================================================
-- CLASSES (High School)
-- ============================================================
CREATE TABLE classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    academic_year_id INT UNSIGNED DEFAULT NULL,
    name VARCHAR(80) NOT NULL,
    grade_level VARCHAR(30) NOT NULL,
    section VARCHAR(20) DEFAULT NULL,
    capacity INT DEFAULT 40,
    class_teacher_id INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE SET NULL
);

-- ============================================================
-- COURSES (University + High School subjects)
-- ============================================================
CREATE TABLE courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    program_id INT UNSIGNED DEFAULT NULL,
    class_id INT UNSIGNED DEFAULT NULL,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(30) DEFAULT NULL,
    credit_hours TINYINT DEFAULT 3,
    semester_no TINYINT DEFAULT 1,
    description TEXT DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
);

-- ============================================================
-- TEACHERS / LECTURERS
-- ============================================================
CREATE TABLE teachers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    employee_no VARCHAR(50) DEFAULT NULL,
    department_id INT UNSIGNED DEFAULT NULL,
    class_id INT UNSIGNED DEFAULT NULL,
    qualification VARCHAR(150) DEFAULT NULL,
    specialization VARCHAR(150) DEFAULT NULL,
    joined_at DATE DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

CREATE TABLE teacher_courses (
    teacher_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (teacher_id, course_id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- ============================================================
-- STUDENTS
-- ============================================================
CREATE TABLE students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    admission_no VARCHAR(50) NOT NULL,
    class_id INT UNSIGNED DEFAULT NULL,
    department_id INT UNSIGNED DEFAULT NULL,
    program_id INT UNSIGNED DEFAULT NULL,
    academic_year_id INT UNSIGNED DEFAULT NULL,
    current_semester TINYINT DEFAULT 1,
    cgpa DECIMAL(4,2) DEFAULT 0.00,
    admission_date DATE DEFAULT NULL,
    graduation_date DATE DEFAULT NULL,
    status ENUM('active','graduated','withdrawn','suspended') DEFAULT 'active',
    UNIQUE KEY unique_admission (tenant_id, admission_no),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE SET NULL
);

-- ============================================================
-- PARENTS (High School)
-- ============================================================
CREATE TABLE parents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    occupation VARCHAR(150) DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE parent_students (
    parent_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    relationship VARCHAR(50) DEFAULT 'parent',
    PRIMARY KEY (parent_id, student_id),
    FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- ENROLLMENTS
-- ============================================================
CREATE TABLE enrollments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED DEFAULT NULL,
    class_id INT UNSIGNED DEFAULT NULL,
    academic_year_id INT UNSIGNED DEFAULT NULL,
    semester TINYINT DEFAULT 1,
    status ENUM('enrolled','dropped','completed','failed') DEFAULT 'enrolled',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
);

-- ============================================================
-- ATTENDANCE
-- ============================================================
CREATE TABLE attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED DEFAULT NULL,
    course_id INT UNSIGNED DEFAULT NULL,
    date DATE NOT NULL,
    status ENUM('present','absent','late','excused') DEFAULT 'present',
    remarks VARCHAR(255) DEFAULT NULL,
    marked_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- TIMETABLE
-- ============================================================
CREATE TABLE timetable (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED DEFAULT NULL,
    course_id INT UNSIGNED DEFAULT NULL,
    teacher_id INT UNSIGNED DEFAULT NULL,
    academic_year_id INT UNSIGNED DEFAULT NULL,
    term_id INT UNSIGNED DEFAULT NULL,
    day_of_week ENUM('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(80) DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

-- ============================================================
-- GRADES / RESULTS
-- ============================================================
CREATE TABLE exams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    class_id INT UNSIGNED DEFAULT NULL,
    term_id INT UNSIGNED DEFAULT NULL,
    academic_year_id INT UNSIGNED DEFAULT NULL,
    exam_date DATE DEFAULT NULL,
    total_marks DECIMAL(6,2) DEFAULT 100.00,
    pass_marks DECIMAL(6,2) DEFAULT 40.00,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE grades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED DEFAULT NULL,
    exam_id INT UNSIGNED DEFAULT NULL,
    marks_obtained DECIMAL(6,2) DEFAULT 0.00,
    total_marks DECIMAL(6,2) DEFAULT 100.00,
    grade_letter VARCHAR(5) DEFAULT NULL,
    gpa_points DECIMAL(4,2) DEFAULT 0.00,
    remarks VARCHAR(255) DEFAULT NULL,
    graded_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE SET NULL
);

-- ============================================================
-- FINANCE
-- ============================================================
CREATE TABLE fee_structures (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    frequency ENUM('once','monthly','termly','yearly') DEFAULT 'termly',
    class_id INT UNSIGNED DEFAULT NULL,
    program_id INT UNSIGNED DEFAULT NULL,
    academic_year_id INT UNSIGNED DEFAULT NULL,
    description TEXT DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    fee_structure_id INT UNSIGNED DEFAULT NULL,
    invoice_no VARCHAR(50) NOT NULL,
    amount_due DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0.00,
    discount DECIMAL(10,2) DEFAULT 0.00,
    due_date DATE DEFAULT NULL,
    status ENUM('unpaid','partial','paid','overdue','waived') DEFAULT 'unpaid',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_invoice (tenant_id, invoice_no),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('cash','mpesa','bank','cheque','online') DEFAULT 'cash',
    reference VARCHAR(150) DEFAULT NULL,
    received_by INT UNSIGNED DEFAULT NULL,
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

-- ============================================================
-- MESSAGING
-- ============================================================
CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    recipient_id INT UNSIGNED NOT NULL,
    subject VARCHAR(255) DEFAULT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE announcements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    author_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    audience ENUM('all','students','teachers','parents','staff') DEFAULT 'all',
    class_id INT UNSIGNED DEFAULT NULL,
    is_pinned TINYINT(1) DEFAULT 0,
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- ACTIVITY LOG
-- ============================================================
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    tenant_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(80) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SEED DEFAULT DATA
-- ============================================================

-- Default plans
INSERT INTO plans (name, description, max_students, max_teachers, price_monthly, price_yearly, features, billing_owner) VALUES
('Starter', 'Perfect for small schools', 200, 20, 29.99, 299.99, '{"attendance":true,"grades":true,"finance":false,"messaging":false}', 'platform'),
('Professional', 'Mid-size institution', 1000, 100, 79.99, 799.99, '{"attendance":true,"grades":true,"finance":true,"messaging":true,"reports":true}', 'platform'),
('Enterprise', 'Large university or multi-campus', 9999, 999, 199.99, 1999.99, '{"attendance":true,"grades":true,"finance":true,"messaging":true,"reports":true,"api":true,"custom_domain":true}', 'platform');

-- Default roles
INSERT INTO roles (name, scope) VALUES
('Super Admin', 'global'),
('Reseller Owner', 'reseller'),
('Reseller Staff', 'reseller'),
('School Admin', 'school'),
('Teacher', 'school'),
('Lecturer', 'school'),
('Student', 'school'),
('Parent', 'school'),
('Accountant', 'school'),
('Staff', 'school');

-- Default super admin user (password: Admin@1234)
INSERT INTO resellers (name, slug, email, status) VALUES ('Platform', 'platform', 'platform@internal', 'active');
INSERT INTO tenants (name, slug, institution_type, status) VALUES ('Platform Admin', 'platform', 'high_school', 'active');
INSERT INTO users (tenant_id, reseller_id, role_id, name, email, password_hash, status) VALUES
(1, 1, 1, 'Super Admin', 'admin@schoolms.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- LEARNING MATERIALS
-- ============================================================
CREATE TABLE learning_materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED DEFAULT NULL,
    course_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

-- ============================================================
-- HR & PAYROLL
-- ============================================================
CREATE TABLE staff_salaries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    basic_salary DECIMAL(10,2) NOT NULL,
    allowances DECIMAL(10,2) DEFAULT 0.00,
    deductions DECIMAL(10,2) DEFAULT 0.00,
    effective_from DATE NOT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE payroll (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    month TINYINT NOT NULL,
    year SMALLINT NOT NULL,
    basic_salary DECIMAL(10,2) NOT NULL,
    allowances DECIMAL(10,2) DEFAULT 0.00,
    deductions DECIMAL(10,2) DEFAULT 0.00,
    net_salary DECIMAL(10,2) NOT NULL,
    status ENUM('draft','paid','cancelled') DEFAULT 'draft',
    paid_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE leave_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    leave_type ENUM('sick','annual','maternity','paternity','unpaid','other') DEFAULT 'annual',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    approved_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- INVENTORY & LIBRARY
-- ============================================================
CREATE TABLE inventory (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    category VARCHAR(80) DEFAULT NULL,
    quantity INT DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'pcs',
    location VARCHAR(100) DEFAULT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE library_books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(150) DEFAULT NULL,
    isbn VARCHAR(30) DEFAULT NULL,
    category VARCHAR(80) DEFAULT NULL,
    status ENUM('available','issued','lost','damaged') DEFAULT 'available',
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE library_loans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date DATE NOT NULL,
    returned_at TIMESTAMP NULL DEFAULT NULL,
    fine_amount DECIMAL(8,2) DEFAULT 0.00,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES library_books(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

