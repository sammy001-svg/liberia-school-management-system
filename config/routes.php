<?php
// ============================================================
// ROUTES CONFIGURATION
// ============================================================

// Auth
$router->get('/login',    ['AuthController', 'loginPage']);
$router->post('/login',   ['AuthController', 'loginPost']);
$router->get('/logout',   ['AuthController', 'logout']);

// ── SCHOOL (High School) ──────────────────────────────────────
$router->get('/school',                     ['SchoolDashboardController', 'index']);
$router->get('/school/dashboard',           ['SchoolDashboardController', 'index']);

$router->get('/school/students',            ['StudentController', 'index']);
$router->get('/school/students/create',     ['StudentController', 'create']);
$router->post('/school/students/store',     ['StudentController', 'store']);
$router->get('/school/students/bulk-template', ['StudentController', 'bulkTemplate']);
$router->post('/school/students/bulk-upload',  ['StudentController', 'bulkUpload']);
$router->get('/school/students/{id}',       ['StudentController', 'show']);
$router->get('/school/students/{id}/id-card', ['StudentController', 'idCard']);
$router->get('/school/students/{id}/edit',  ['StudentController', 'edit']);
$router->post('/school/students/{id}/update', ['StudentController', 'update']);
$router->post('/school/students/{id}/delete', ['StudentController', 'delete']);

$router->get('/school/teachers',            ['TeacherController', 'index']);
$router->get('/school/teachers/create',     ['TeacherController', 'create']);
$router->post('/school/teachers/store',     ['TeacherController', 'store']);
$router->get('/school/teachers/bulk-template', ['TeacherController', 'bulkTemplate']);
$router->post('/school/teachers/bulk-upload',  ['TeacherController', 'bulkUpload']);
$router->get('/school/teachers/{id}',       ['TeacherController', 'show']);
$router->get('/school/teachers/{id}/id-card', ['TeacherController', 'idCard']);
$router->get('/school/teachers/{id}/edit',  ['TeacherController', 'edit']);
$router->post('/school/teachers/{id}/update', ['TeacherController', 'update']);
$router->post('/school/teachers/{id}/courses/assign', ['TeacherController', 'assignCourse']);
$router->post('/school/teachers/{id}/courses/{courseId}/remove', ['TeacherController', 'removeCourse']);

$router->get('/school/classes',             ['ClassController', 'index']);
$router->get('/school/classes/create',      ['ClassController', 'create']);
$router->post('/school/classes/store',      ['ClassController', 'store']);
$router->get('/school/classes/{id}/edit',   ['ClassController', 'edit']);
$router->post('/school/classes/{id}/update', ['ClassController', 'update']);
$router->get('/school/classes/{id}',        ['ClassController', 'show']);

$router->get('/school/attendance',          ['AttendanceController', 'index']);
$router->post('/school/attendance/mark',    ['AttendanceController', 'mark']);
$router->get('/school/attendance/report',   ['AttendanceController', 'report']);

$router->get('/school/timetable',           ['TimetableController', 'index']);
$router->get('/school/timetable/create',    ['TimetableController', 'create']);
$router->post('/school/timetable/store',    ['TimetableController', 'store']);
$router->post('/school/timetable/{id}/delete', ['TimetableController', 'deleteEntry']);

$router->get('/school/grades',              ['GradeController', 'index']);
$router->get('/school/grades/enter',        ['GradeController', 'enter']);
$router->post('/school/grades/store',       ['GradeController', 'store']);
$router->post('/school/exams/store',        ['GradeController', 'storeExam']);
$router->get('/school/grades/report/{studentId}', ['GradeController', 'report']);
$router->get('/school/grades/report-card/{studentId}', ['GradeController', 'reportCard']);
$router->get('/school/grades/rankings',            ['GradeController', 'rankings']);
$router->get('/school/grades/rankings/bulk-template', ['GradeController', 'bulkTemplateRankings']);
$router->post('/school/grades/rankings/bulk-upload',  ['GradeController', 'bulkUploadRankings']);
$router->get('/school/grades/rankings/export',      ['GradeController', 'exportRankingsCsv']);
$router->get('/school/grades/rankings/print',       ['GradeController', 'printRankings']);

// ── CERTIFICATES ─────────────────────────────────────────────────
$router->get('/school/certificates',              ['CertificateController', 'index']);
$router->post('/school/certificates/generate',     ['CertificateController', 'generate']);
$router->post('/school/certificates/bulk-generate', ['CertificateController', 'bulkGenerate']);
$router->get('/school/certificates/{id}/print',    ['CertificateController', 'printCertificate']);
$router->post('/school/certificates/{id}/delete',  ['CertificateController', 'delete']);

$router->get('/school/finance',             ['FinanceController', 'index']);
$router->get('/school/finance/invoices',    ['FinanceController', 'invoices']);
$router->get('/school/finance/invoices/create', ['FinanceController', 'createInvoice']);
$router->post('/school/finance/invoices/store', ['FinanceController', 'storeInvoice']);
$router->get('/school/finance/invoices/{id}/print', ['FinanceController', 'printInvoice']);
$router->get('/school/finance/payments',    ['FinanceController', 'payments']);
$router->post('/school/finance/payments/store', ['FinanceController', 'storePayment']);
$router->get('/school/finance/fees',        ['FinanceController', 'feeStructures']);
$router->post('/school/finance/fees/store', ['FinanceController', 'storeFeeStructure']);
$router->post('/school/finance/fees/{id}/generate', ['FinanceController', 'generateFeeInvoices']);
$router->get('/school/finance/expenses',    ['FinanceController', 'expenses']);
$router->post('/school/finance/expenses/store', ['FinanceController', 'storeExpense']);
$router->post('/school/finance/expenses/{id}/delete', ['FinanceController', 'deleteExpense']);
$router->get('/school/finance/collection',  ['FinanceController', 'collection']);
$router->get('/school/finance/bus-billing',  ['FinanceController', 'busBilling']);
$router->post('/school/finance/bus-billing/generate', ['FinanceController', 'generateBusInvoices']);
$router->get('/school/finance/reports',      ['FinanceController', 'reports']);
$router->get('/school/finance/reports/print', ['FinanceController', 'printReport']);

// ── SCHOOL BUS / TRANSPORT ───────────────────────────────────────
$router->get('/school/transport/buses',            ['BusController', 'buses']);
$router->post('/school/transport/buses/store',      ['BusController', 'storeBus']);
$router->post('/school/transport/buses/{id}/update', ['BusController', 'updateBus']);
$router->post('/school/transport/buses/{id}/delete', ['BusController', 'deleteBus']);

$router->get('/school/transport/drivers',            ['BusController', 'drivers']);
$router->post('/school/transport/drivers/store',      ['BusController', 'storeDriver']);
$router->post('/school/transport/drivers/{id}/update', ['BusController', 'updateDriver']);
$router->post('/school/transport/drivers/{id}/delete', ['BusController', 'deleteDriver']);

$router->get('/school/transport/routes',            ['BusController', 'routes']);
$router->post('/school/transport/routes/store',      ['BusController', 'storeRoute']);
$router->post('/school/transport/routes/{id}/update', ['BusController', 'updateRoute']);
$router->post('/school/transport/routes/{id}/delete', ['BusController', 'deleteRoute']);
$router->get('/school/transport/routes/{id}/students', ['BusController', 'routeStudents']);
$router->post('/school/transport/routes/{id}/students/assign', ['BusController', 'assignStudent']);
$router->post('/school/transport/routes/{id}/students/{studentId}/unassign', ['BusController', 'unassignStudent']);

$router->get('/school/parents',             ['ParentController', 'index']);
$router->get('/school/parents/create',      ['ParentController', 'create']);
$router->post('/school/parents/store',      ['ParentController', 'store']);
$router->get('/school/parents/bulk-template', ['ParentController', 'bulkTemplate']);
$router->post('/school/parents/bulk-upload',  ['ParentController', 'bulkUpload']);
$router->get('/school/parents/{id}',        ['ParentController', 'show']);
$router->get('/school/parents/{id}/edit',   ['ParentController', 'edit']);
$router->post('/school/parents/{id}/update', ['ParentController', 'update']);
$router->post('/school/parents/{id}/delete', ['ParentController', 'delete']);
$router->post('/school/parents/{id}/children/link', ['ParentController', 'linkChild']);
$router->post('/school/parents/{id}/children/{studentId}/unlink', ['ParentController', 'unlinkChild']);

$router->get('/school/announcements',       ['AnnouncementController', 'index']);
$router->get('/school/announcements/create', ['AnnouncementController', 'create']);
$router->post('/school/announcements/store', ['AnnouncementController', 'store']);
$router->post('/school/announcements/{id}/delete', ['AnnouncementController', 'delete']);

$router->get('/school/messages',            ['MessageController', 'index']);
$router->get('/school/messages/compose',    ['MessageController', 'compose']);
$router->post('/school/messages/send',      ['MessageController', 'send']);
$router->get('/school/messages/{id}',       ['MessageController', 'show']);
$router->post('/school/messages/{id}/delete', ['MessageController', 'delete']);

$router->get('/school/settings',            ['SchoolSettingsController', 'index']);
$router->post('/school/settings/update',    ['SchoolSettingsController', 'update']);

// ── DEPARTMENTS & COURSES (used by Teachers, Grades, Timetable) ──
$router->get('/school/departments',         ['UniversityController', 'departments']);
$router->get('/school/departments/create',  ['UniversityController', 'createDepartment']);
$router->post('/school/departments/store',  ['UniversityController', 'storeDepartment']);

$router->get('/school/courses',             ['UniversityController', 'courses']);
$router->get('/school/courses/create',      ['UniversityController', 'createCourse']);
$router->post('/school/courses/store',      ['UniversityController', 'storeCourse']);

// ── ACADEMIC YEARS & TERMS ──────────────────────────────────────
$router->get('/school/academic-years',        ['AcademicController', 'index']);
$router->post('/school/academic-years/store', ['AcademicController', 'storeYear']);
$router->post('/school/terms/store',          ['AcademicController', 'storeTerm']);

// ── HOMEWORK ─────────────────────────────────────────────────────
$router->get('/school/homework',                       ['HomeworkController', 'index']);
$router->post('/school/homework/store',                ['HomeworkController', 'store']);
$router->post('/school/homework/{id}/delete',           ['HomeworkController', 'delete']);
$router->get('/school/homework/{id}/submissions',       ['HomeworkController', 'submissions']);
$router->post('/school/homework/submissions/{id}/grade', ['HomeworkController', 'grade']);

// ── ONLINE CLASSES ───────────────────────────────────────────────
$router->get('/school/online-classes',                     ['OnlineClassController', 'index']);
$router->post('/school/online-classes/store',              ['OnlineClassController', 'store']);
$router->post('/school/online-classes/{id}/delete',        ['OnlineClassController', 'delete']);
$router->post('/school/online-classes/{id}/cancel',        ['OnlineClassController', 'cancel']);
$router->get('/school/online-classes/{id}/attendance',     ['OnlineClassController', 'attendance']);
$router->post('/school/online-classes/{id}/attendance/mark', ['OnlineClassController', 'markAttendance']);

// ── ONLINE EXAMS ─────────────────────────────────────────────────
$router->get('/school/online-exams',                          ['OnlineExamController', 'index']);
$router->post('/school/online-exams/store',                   ['OnlineExamController', 'store']);
$router->post('/school/online-exams/{id}/delete',              ['OnlineExamController', 'delete']);
$router->post('/school/online-exams/{id}/publish',             ['OnlineExamController', 'publish']);
$router->get('/school/online-exams/{id}/questions',            ['OnlineExamController', 'questions']);
$router->post('/school/online-exams/{id}/questions/store',     ['OnlineExamController', 'storeQuestion']);
$router->post('/school/online-exams/{id}/questions/{qid}/delete', ['OnlineExamController', 'deleteQuestion']);
$router->get('/school/online-exams/{id}/results',              ['OnlineExamController', 'results']);

// ── STUDENT PORTAL ──────────────────────────────────────────────
$router->get('/student/dashboard',          ['StudentPortalController', 'dashboard']);
$router->get('/student/timetable',          ['StudentPortalController', 'timetable']);
$router->get('/student/grades',             ['StudentPortalController', 'grades']);
$router->get('/student/materials',          ['StudentPortalController', 'materials']);
$router->get('/student/homework',           ['StudentPortalController', 'homework']);
$router->post('/student/homework/{id}/submit', ['StudentPortalController', 'submitHomework']);
$router->get('/student/online-classes',     ['StudentPortalController', 'onlineClasses']);
$router->get('/student/exams',              ['StudentPortalController', 'exams']);
$router->get('/student/exams/{id}/take',    ['StudentPortalController', 'takeExam']);
$router->post('/student/exams/{id}/submit', ['StudentPortalController', 'submitExam']);
$router->get('/student/exams/{id}/result',  ['StudentPortalController', 'examResult']);

// ── PARENT PORTAL ───────────────────────────────────────────────
$router->get('/parent/dashboard',           ['ParentPortalController', 'dashboard']);
$router->get('/parent/student/{id}',        ['ParentPortalController', 'viewChild']);
$router->get('/parent/finance',             ['ParentPortalController', 'finance']);

// ── HR & PAYROLL ────────────────────────────────────────────────
$router->get('/school/staff',               ['StaffController', 'index']);
$router->post('/school/staff/store',        ['StaffController', 'store']);
$router->get('/school/staff/{id}/edit',     ['StaffController', 'edit']);
$router->post('/school/staff/{id}/update',  ['StaffController', 'update']);
$router->post('/school/staff/{id}/delete',  ['StaffController', 'delete']);
$router->get('/school/hr/payroll',          ['HRController', 'payroll']);
$router->post('/school/hr/payroll/generate', ['HRController', 'generatePayroll']);
$router->post('/school/hr/payroll/{id}/pay', ['HRController', 'markPayrollPaid']);
$router->get('/school/hr/payroll/{id}/payslip', ['HRController', 'payslip']);
$router->get('/school/hr/leaves',           ['HRController', 'leaves']);
$router->post('/school/hr/leaves/approve',  ['HRController', 'approveLeave']);

// ── INVENTORY & LIBRARY ─────────────────────────────────────────
$router->get('/school/inventory',           ['InventoryController', 'index']);
$router->post('/school/inventory/store',    ['InventoryController', 'store']);
$router->get('/school/library',             ['InventoryController', 'library']);
$router->post('/school/library/store',      ['InventoryController', 'storeBook']);
$router->get('/school/library/bulk-template', ['InventoryController', 'bulkTemplateBooks']);
$router->post('/school/library/bulk-upload',  ['InventoryController', 'bulkUploadBooks']);
$router->get('/school/library/loans',       ['InventoryController', 'loans']);
$router->post('/school/library/issue',      ['InventoryController', 'issueBook']);
$router->post('/school/library/loans/{id}/return', ['InventoryController', 'returnBook']);

// ── ANALYTICS ───────────────────────────────────────────────────
$router->get('/school/analytics',           ['AnalyticsController', 'index']);
$router->get('/school/analytics/student/{id}', ['AnalyticsController', 'studentGrowth']);
$router->get('/school/analytics/attendance', ['AnalyticsController', 'attendanceHeatmap']);

// Catch-all redirect
$router->get('/', ['AuthController', 'loginPage']);
