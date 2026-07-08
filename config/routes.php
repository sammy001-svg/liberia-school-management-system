<?php
// ============================================================
// ROUTES CONFIGURATION
// ============================================================

// Auth
$router->get('/login',    ['AuthController', 'loginPage']);
$router->post('/login',   ['AuthController', 'loginPost']);
$router->get('/logout',   ['AuthController', 'logout']);

// Registration
$router->get('/register/school',    ['RegistrationController', 'schoolPage']);
$router->post('/register/school',   ['RegistrationController', 'registerSchool']);
$router->get('/register/reseller',  ['RegistrationController', 'resellerPage']);
$router->post('/register/reseller', ['RegistrationController', 'registerReseller']);

// ── SUPER ADMIN ──────────────────────────────────────────────
$router->get('/admin',                      ['AdminDashboardController', 'index']);
$router->get('/admin/dashboard',            ['AdminDashboardController', 'index']);

// Reseller Plans (Packages for resellers)
$router->get('/admin/reseller-plans',                ['AdminResellerPlanController', 'index']);
$router->get('/admin/reseller-plans/create',         ['AdminResellerPlanController', 'create']);
$router->post('/admin/reseller-plans/store',         ['AdminResellerPlanController', 'store']);
$router->get('/admin/reseller-plans/{id}/edit',      ['AdminResellerPlanController', 'edit']);
$router->post('/admin/reseller-plans/{id}/update',   ['AdminResellerPlanController', 'update']);

// Resellers
$router->get('/admin/resellers',                ['AdminResellerController', 'index']);
$router->get('/admin/resellers/create',         ['AdminResellerController', 'create']);
$router->post('/admin/resellers/store',         ['AdminResellerController', 'store']);
$router->get('/admin/resellers/{id}',           ['AdminResellerController', 'show']);
$router->get('/admin/resellers/{id}/edit',      ['AdminResellerController', 'edit']);
$router->post('/admin/resellers/{id}/update',   ['AdminResellerController', 'update']);
$router->get('/admin/resellers/{id}/delete',    ['AdminResellerController', 'delete']);

$router->get('/admin/schools',              ['AdminSchoolController', 'index']);
$router->get('/admin/schools/create',       ['AdminSchoolController', 'create']);
$router->post('/admin/schools/store',       ['AdminSchoolController', 'store']);
$router->get('/admin/schools/{id}',         ['AdminSchoolController', 'show']);
$router->get('/admin/schools/{id}/edit',    ['AdminSchoolController', 'edit']);
$router->post('/admin/schools/{id}/update', ['AdminSchoolController', 'update']);
$router->post('/admin/schools/{id}/delete', ['AdminSchoolController', 'delete']);

$router->get('/admin/plans',                ['AdminPlanController', 'index']);
$router->get('/admin/plans/create',         ['AdminPlanController', 'create']);
$router->post('/admin/plans/store',         ['AdminPlanController', 'store']);
$router->get('/admin/plans/{id}/edit',      ['AdminPlanController', 'edit']);
$router->post('/admin/plans/{id}/update',   ['AdminPlanController', 'update']);

$router->get('/admin/users',                ['AdminUserController', 'index']);

// ── RESELLER ─────────────────────────────────────────────────
$router->get('/reseller',                   ['ResellerDashboardController', 'index']);
$router->get('/reseller/dashboard',         ['ResellerDashboardController', 'index']);

$router->get('/reseller/schools',           ['ResellerSchoolController', 'index']);
$router->get('/reseller/schools/create',    ['ResellerSchoolController', 'create']);
$router->post('/reseller/schools/store',    ['ResellerSchoolController', 'store']);
$router->get('/reseller/schools/{id}',      ['ResellerSchoolController', 'show']);

$router->get('/reseller/branding',          ['ResellerBrandingController', 'index']);
$router->post('/reseller/branding/update',  ['ResellerBrandingController', 'update']);

$router->get('/reseller/billing',           ['ResellerBillingController', 'index']);

// ── SCHOOL (High School) ──────────────────────────────────────
$router->get('/school',                     ['SchoolDashboardController', 'index']);
$router->get('/school/dashboard',           ['SchoolDashboardController', 'index']);

$router->get('/school/students',            ['StudentController', 'index']);
$router->get('/school/students/create',     ['StudentController', 'create']);
$router->post('/school/students/store',     ['StudentController', 'store']);
$router->get('/school/students/{id}',       ['StudentController', 'show']);
$router->get('/school/students/{id}/edit',  ['StudentController', 'edit']);
$router->post('/school/students/{id}/update', ['StudentController', 'update']);
$router->post('/school/students/{id}/delete', ['StudentController', 'delete']);

$router->get('/school/teachers',            ['TeacherController', 'index']);
$router->get('/school/teachers/create',     ['TeacherController', 'create']);
$router->post('/school/teachers/store',     ['TeacherController', 'store']);
$router->get('/school/teachers/{id}',       ['TeacherController', 'show']);
$router->get('/school/teachers/{id}/edit',  ['TeacherController', 'edit']);
$router->post('/school/teachers/{id}/update', ['TeacherController', 'update']);

$router->get('/school/classes',             ['ClassController', 'index']);
$router->get('/school/classes/create',      ['ClassController', 'create']);
$router->post('/school/classes/store',      ['ClassController', 'store']);
$router->get('/school/classes/{id}/edit',   ['ClassController', 'edit']);
$router->post('/school/classes/{id}/update', ['ClassController', 'update']);

$router->get('/school/attendance',          ['AttendanceController', 'index']);
$router->post('/school/attendance/mark',    ['AttendanceController', 'mark']);
$router->get('/school/attendance/report',   ['AttendanceController', 'report']);

$router->get('/school/timetable',           ['TimetableController', 'index']);
$router->get('/school/timetable/create',    ['TimetableController', 'create']);
$router->post('/school/timetable/store',    ['TimetableController', 'store']);

$router->get('/school/grades',              ['GradeController', 'index']);
$router->get('/school/grades/enter',        ['GradeController', 'enter']);
$router->post('/school/grades/store',       ['GradeController', 'store']);
$router->get('/school/grades/report/{studentId}', ['GradeController', 'report']);

$router->get('/school/finance',             ['FinanceController', 'index']);
$router->get('/school/finance/invoices',    ['FinanceController', 'invoices']);
$router->get('/school/finance/invoices/create', ['FinanceController', 'createInvoice']);
$router->post('/school/finance/invoices/store', ['FinanceController', 'storeInvoice']);
$router->get('/school/finance/payments',    ['FinanceController', 'payments']);
$router->post('/school/finance/payments/store', ['FinanceController', 'storePayment']);

$router->get('/school/parents',             ['ParentController', 'index']);
$router->get('/school/parents/create',      ['ParentController', 'create']);
$router->post('/school/parents/store',      ['ParentController', 'store']);

$router->get('/school/announcements',       ['AnnouncementController', 'index']);
$router->get('/school/announcements/create', ['AnnouncementController', 'create']);
$router->post('/school/announcements/store', ['AnnouncementController', 'store']);

$router->get('/school/messages',            ['MessageController', 'index']);
$router->get('/school/messages/compose',    ['MessageController', 'compose']);
$router->post('/school/messages/send',      ['MessageController', 'send']);
$router->get('/school/messages/{id}',       ['MessageController', 'show']);

$router->get('/school/settings',            ['SchoolSettingsController', 'index']);
$router->post('/school/settings/update',    ['SchoolSettingsController', 'update']);

// ── UNIVERSITY SPECIFIC ─────────────────────────────────────────
$router->get('/school/departments',         ['UniversityController', 'departments']);
$router->get('/school/departments/create',  ['UniversityController', 'createDepartment']);
$router->post('/school/departments/store',  ['UniversityController', 'storeDepartment']);

$router->get('/school/programs',            ['UniversityController', 'programs']);
$router->get('/school/programs/create',     ['UniversityController', 'createProgram']);
$router->post('/school/programs/store',     ['UniversityController', 'storeProgram']);

$router->get('/school/courses',             ['UniversityController', 'courses']);
$router->get('/school/courses/create',      ['UniversityController', 'createCourse']);
$router->post('/school/courses/store',      ['UniversityController', 'storeCourse']);

$router->get('/school/enrollments',         ['UniversityController', 'enrollments']);
$router->get('/school/enrollments/create',  ['UniversityController', 'createEnrollment']);
$router->post('/school/enrollments/store',  ['UniversityController', 'storeEnrollment']);

// ── STUDENT PORTAL ──────────────────────────────────────────────
$router->get('/student/dashboard',          ['StudentPortalController', 'dashboard']);
$router->get('/student/timetable',          ['StudentPortalController', 'timetable']);
$router->get('/student/grades',             ['StudentPortalController', 'grades']);
$router->get('/student/materials',          ['StudentPortalController', 'materials']);

// ── PARENT PORTAL ───────────────────────────────────────────────
$router->get('/parent/dashboard',           ['ParentPortalController', 'dashboard']);
$router->get('/parent/student/{id}',        ['ParentPortalController', 'viewChild']);
$router->get('/parent/finance',             ['ParentPortalController', 'finance']);

// ── HR & PAYROLL ────────────────────────────────────────────────
$router->get('/school/hr/payroll',          ['HRController', 'payroll']);
$router->post('/school/hr/payroll/generate', ['HRController', 'generatePayroll']);
$router->get('/school/hr/leaves',           ['HRController', 'leaves']);
$router->post('/school/hr/leaves/approve',  ['HRController', 'approveLeave']);

// ── INVENTORY & LIBRARY ─────────────────────────────────────────
$router->get('/school/inventory',           ['InventoryController', 'index']);
$router->post('/school/inventory/store',    ['InventoryController', 'store']);
$router->get('/school/library',             ['InventoryController', 'library']);
$router->get('/school/library/loans',       ['InventoryController', 'loans']);
$router->post('/school/library/issue',      ['InventoryController', 'issueBook']);

// ── ANALYTICS ───────────────────────────────────────────────────
$router->get('/school/analytics',           ['AnalyticsController', 'index']);
$router->get('/school/analytics/student/{id}', ['AnalyticsController', 'studentGrowth']);
$router->get('/school/analytics/attendance', ['AnalyticsController', 'attendanceHeatmap']);

// Catch-all redirect
$router->get('/', ['AuthController', 'loginPage']);
