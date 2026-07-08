<?php
require_once ROOT_DIR . '/core/Controller.php';

class AnnouncementController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Teacher','Super Admin']);
        $announcements = $this->db->fetchAll("SELECT a.*, u.name AS author FROM announcements a JOIN users u ON a.author_id=u.id WHERE a.tenant_id=? ORDER BY a.published_at DESC", [$this->tid]);
        $this->view('school/highschool/announcements/index', ['pageTitle'=>'Announcements','panelType'=>'school','announcements'=>$announcements,'flash'=>$this->getFlash()]);
    }

    public function create(): void {
        $this->requireAuth(['School Admin','Teacher','Super Admin']);
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        $this->view('school/highschool/announcements/form', ['pageTitle'=>'Post Announcement','panelType'=>'school','classes'=>$classes,'flash'=>$this->getFlash()]);
    }

    public function store(): void {
        $this->requireAuth(['School Admin','Teacher','Super Admin']);
        $this->db->insert("INSERT INTO announcements (tenant_id,author_id,title,body,audience,class_id,is_pinned) VALUES (?,?,?,?,?,?,?)",
            [$this->tid,$_SESSION['user_id'],$_POST['title'],$_POST['body'],$_POST['audience']??'all',$_POST['class_id']??null,(int)($_POST['is_pinned']??0)]);
        $this->flash('success','Announcement posted.'); $this->redirect('/school/announcements');
    }
}

class MessageController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth();
        $msgs = $this->db->fetchAll("SELECT m.*, u.name AS sender_name FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.recipient_id=? AND m.tenant_id=? ORDER BY m.created_at DESC", [$_SESSION['user_id'],$this->tid]);
        $this->view('school/highschool/messages/index', ['pageTitle'=>'Messages','panelType'=>'school','messages'=>$msgs,'flash'=>$this->getFlash()]);
    }

    public function compose(): void {
        $this->requireAuth();
        $users = $this->db->fetchAll("SELECT id,name FROM users WHERE tenant_id=? AND id!=? ORDER BY name", [$this->tid,$_SESSION['user_id']]);
        $this->view('school/highschool/messages/compose', ['pageTitle'=>'Compose','panelType'=>'school','users'=>$users,'flash'=>$this->getFlash()]);
    }

    public function send(): void {
        $this->requireAuth();
        $this->db->insert("INSERT INTO messages (tenant_id,sender_id,recipient_id,subject,body) VALUES (?,?,?,?,?)",
            [$this->tid,$_SESSION['user_id'],$_POST['recipient_id'],$_POST['subject']??'',$_POST['body']]);
        $this->flash('success','Message sent.'); $this->redirect('/school/messages');
    }

    public function show(string $id): void {
        $this->requireAuth();
        $msg = $this->db->fetchOne("SELECT m.*,u.name AS sender_name FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.id=? AND m.tenant_id=?",[$id,$this->tid]);
        if (!$msg) { $this->redirect('/school/messages'); }
        if (!$msg['is_read']) { $this->db->execute("UPDATE messages SET is_read=1,read_at=NOW() WHERE id=?",[$id]); }
        $this->view('school/highschool/messages/show', ['pageTitle'=>'Message','panelType'=>'school','message'=>$msg,'flash'=>$this->getFlash()]);
    }
}

class GradeController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Teacher','Super Admin']);
        $exams = $this->db->fetchAll("SELECT e.*, c.name AS class_name FROM exams e LEFT JOIN classes c ON e.class_id=c.id WHERE e.tenant_id=? ORDER BY e.exam_date DESC", [$this->tid]);
        $this->view('school/highschool/grades/index', ['pageTitle'=>'Grades & Exams','panelType'=>'school','exams'=>$exams,'flash'=>$this->getFlash()]);
    }

    public function enter(): void {
        $this->requireAuth(['School Admin','Teacher','Super Admin']);
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        $exams   = $this->db->fetchAll("SELECT id,name FROM exams WHERE tenant_id=?", [$this->tid]);
        $students = []; $courses = [];
        if (!empty($_GET['class_id'])) {
            $students = $this->db->fetchAll("SELECT s.id,u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.class_id=? AND s.tenant_id=? AND s.status='active' ORDER BY u.name",[$_GET['class_id'],$this->tid]);
            $courses  = $this->db->fetchAll("SELECT id,name FROM courses WHERE class_id=? AND tenant_id=?",[$_GET['class_id'],$this->tid]);
        }
        $this->view('school/highschool/grades/enter', ['pageTitle'=>'Enter Grades','panelType'=>'school','classes'=>$classes,'exams'=>$exams,'students'=>$students,'courses'=>$courses,'selectedClass'=>$_GET['class_id']??'','flash'=>$this->getFlash()]);
    }

    public function store(): void {
        $this->requireAuth(['School Admin','Teacher','Super Admin']);
        foreach ($_POST['grades'] ?? [] as $studentId => $marks) {
            foreach ($marks as $courseId => $score) {
                $pct = (float)$score;
                $letter = $pct>=90?'A+':($pct>=80?'A':($pct>=70?'B':($pct>=60?'C':($pct>=50?'D':'F'))));
                $gpa    = $pct>=90?4.0:($pct>=80?3.5:($pct>=70?3.0:($pct>=60?2.5:($pct>=50?2.0:0.0))));
                $this->db->insert("INSERT INTO grades (tenant_id,student_id,course_id,exam_id,marks_obtained,total_marks,grade_letter,gpa_points,graded_by) VALUES (?,?,?,?,?,100,?,?,?) ON DUPLICATE KEY UPDATE marks_obtained=VALUES(marks_obtained),grade_letter=VALUES(grade_letter)",
                    [$this->tid,$studentId,$courseId,$_POST['exam_id']??null,$pct,$letter,$gpa,$_SESSION['user_id']]);
            }
        }
        $this->flash('success','Grades saved.'); $this->redirect('/school/grades');
    }

    public function report(string $studentId): void {
        $this->requireAuth(['School Admin','Teacher','Super Admin']);
        $student = $this->db->fetchOne("SELECT s.*,u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.id=? AND s.tenant_id=?",[$studentId,$this->tid]);
        $grades  = $this->db->fetchAll("SELECT g.*,c.name AS course_name,e.name AS exam_name FROM grades g LEFT JOIN courses c ON g.course_id=c.id LEFT JOIN exams e ON g.exam_id=e.id WHERE g.student_id=? AND g.tenant_id=? ORDER BY g.created_at DESC",[$studentId,$this->tid]);
        $this->view('school/highschool/grades/report', ['pageTitle'=>'Grade Report','panelType'=>'school','student'=>$student,'grades'=>$grades,'flash'=>$this->getFlash()]);
    }
}

class TimetableController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Teacher','Student','Super Admin']);
        $classId   = $_GET['class_id'] ?? '';
        $classes   = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        $timetable = [];
        if ($classId) {
            $rows = $this->db->fetchAll("SELECT tt.*,c.name AS course_name,u.name AS teacher_name FROM timetable tt LEFT JOIN courses c ON tt.course_id=c.id LEFT JOIN teachers t ON tt.teacher_id=t.id LEFT JOIN users u ON t.user_id=u.id WHERE tt.tenant_id=? AND tt.class_id=? ORDER BY FIELD(tt.day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday'),tt.start_time",[$this->tid,$classId]);
            foreach ($rows as $r) { $timetable[$r['day_of_week']][] = $r; }
        }
        $this->view('school/highschool/timetable/index', ['pageTitle'=>'Timetable','panelType'=>'school','classes'=>$classes,'timetable'=>$timetable,'classId'=>$classId,'flash'=>$this->getFlash()]);
    }

    public function create(): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $classes  = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        $courses  = $this->db->fetchAll("SELECT id,name FROM courses WHERE tenant_id=?", [$this->tid]);
        $teachers = $this->db->fetchAll("SELECT t.id,u.name FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.tenant_id=?", [$this->tid]);
        $this->view('school/highschool/timetable/form', ['pageTitle'=>'Add Timetable Entry','panelType'=>'school','classes'=>$classes,'courses'=>$courses,'teachers'=>$teachers,'flash'=>$this->getFlash()]);
    }

    public function store(): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $this->db->insert("INSERT INTO timetable (tenant_id,class_id,course_id,teacher_id,day_of_week,start_time,end_time,room) VALUES (?,?,?,?,?,?,?,?)",
            [$this->tid,$_POST['class_id'],$_POST['course_id']??null,$_POST['teacher_id']??null,$_POST['day_of_week'],$_POST['start_time'],$_POST['end_time'],$_POST['room']??'']);
        $this->flash('success','Timetable entry added.'); $this->redirect('/school/timetable?class_id='.$_POST['class_id']);
    }
}

class ParentController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $parents = $this->db->fetchAll("SELECT p.*,u.name,u.email,u.phone FROM parents p JOIN users u ON p.user_id=u.id WHERE p.tenant_id=? ORDER BY u.name", [$this->tid]);
        $this->view('school/highschool/parents/index', ['pageTitle'=>'Parents','panelType'=>'school','parents'=>$parents,'flash'=>$this->getFlash()]);
    }

    public function create(): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $students = $this->db->fetchAll("SELECT s.id,u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.tenant_id=? AND s.status='active' ORDER BY u.name", [$this->tid]);
        $this->view('school/highschool/parents/form', ['pageTitle'=>'Add Parent','panelType'=>'school','students'=>$students,'flash'=>$this->getFlash()]);
    }

    public function store(): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE name='Parent' LIMIT 1")['id'] ?? 8;
        $pw = password_hash($_POST['password'] ?? 'Parent@123', PASSWORD_BCRYPT);
        $userId = $this->db->insert("INSERT INTO users (tenant_id,role_id,name,email,phone,status) VALUES (?,?,?,?,?,?)",
            [$this->tid,$roleId,$_POST['name'],$_POST['email'],$_POST['phone']??'','active']);
        $this->db->execute("UPDATE users SET password_hash=? WHERE id=?",[$pw,$userId]);
        $parentId = $this->db->insert("INSERT INTO parents (tenant_id,user_id,occupation) VALUES (?,?,?)",
            [$this->tid,$userId,$_POST['occupation']??'']);
        if (!empty($_POST['student_id'])) {
            $this->db->insert("INSERT INTO parent_students (parent_id,student_id,relationship) VALUES (?,?,?)",
                [$parentId,$_POST['student_id'],$_POST['relationship']??'parent']);
        }
        $this->flash('success','Parent account created.'); $this->redirect('/school/parents');
    }
}

class SchoolSettingsController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $this->view('school/highschool/settings', ['pageTitle'=>'School Settings','panelType'=>'school','tenant'=>$tenant,'flash'=>$this->getFlash()]);
    }

    public function update(): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $this->db->execute("UPDATE tenants SET name=?,email=?,phone=?,address=?,country=?,timezone=?,academic_year=?,currency=?,domain=?,primary_color=?,secondary_color=?,accent_color=? WHERE id=?",
            [
                $_POST['name'], $_POST['email']??'', $_POST['phone']??'', $_POST['address']??'', 
                $_POST['country']??'', $_POST['timezone']??'UTC', $_POST['academic_year']??'',
                $_POST['currency']??'Ksh',
                $_POST['domain']??null, $_POST['primary_color']??'#4F46E5', 
                $_POST['secondary_color']??'#7C3AED', $_POST['accent_color']??'#06B6D4',
                $this->tid
            ]);
        
        // Update session branding if it's the current school
        if ($_SESSION['tenant_id'] == $this->tid) {
            $_SESSION['branding']['name']            = $_POST['name'];
            $_SESSION['branding']['primary_color']   = $_POST['primary_color'];
            $_SESSION['branding']['secondary_color']  = $_POST['secondary_color'];
        }

        $this->flash('success','Settings and branding updated.'); $this->redirect('/school/settings');
    }
}

class AdminPlanController extends Controller {
    public function index(): void {
        $this->requireSuperAdmin();
        $plans = $this->db->fetchAll("SELECT * FROM plans ORDER BY price_monthly");
        $this->view('super_admin/plans/index', ['pageTitle'=>'School Plans','panelType'=>'admin','plans'=>$plans,'flash'=>$this->getFlash()]);
    }

    public function create(): void {
        $this->requireSuperAdmin();
        $this->view('super_admin/plans/form', ['pageTitle'=>'Create School Plan','panelType'=>'admin','plan'=>null,'flash'=>$this->getFlash()]);
    }

    public function store(): void {
        $this->requireSuperAdmin();
        $features = json_encode($_POST['features'] ?? []);
        $this->db->insert("INSERT INTO plans (name,description,max_students,max_teachers,price_monthly,price_yearly,billing_owner,features) VALUES (?,?,?,?,?,?,?,?)",
            [$_POST['name'],$_POST['description']??'',(int)$_POST['max_students'],(int)$_POST['max_teachers'],(float)$_POST['price_monthly'],(float)$_POST['price_yearly'],$_POST['billing_owner']??'platform',$features]);
        $this->flash('success','School plan created.'); $this->redirect('/admin/plans');
    }

    public function edit(string $id): void {
        $this->requireSuperAdmin();
        $plan = $this->db->fetchOne("SELECT * FROM plans WHERE id=?", [$id]);
        $plan['features'] = json_decode($plan['features'] ?? '[]', true);
        $this->view('super_admin/plans/form', ['pageTitle'=>'Edit School Plan','panelType'=>'admin','plan'=>$plan,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requireSuperAdmin();
        $features = json_encode($_POST['features'] ?? []);
        $this->db->execute("UPDATE plans SET name=?,description=?,max_students=?,max_teachers=?,price_monthly=?,price_yearly=?,billing_owner=?,features=? WHERE id=?",
            [$_POST['name'],$_POST['description']??'',(int)$_POST['max_students'],(int)$_POST['max_teachers'],(float)$_POST['price_monthly'],(float)$_POST['price_yearly'],$_POST['billing_owner']??'platform',$features,$id]);
        $this->flash('success','School plan updated.'); $this->redirect('/admin/plans');
    }
}

class AdminResellerPlanController extends Controller {
    public function index(): void {
        $this->requireSuperAdmin();
        $plans = $this->db->fetchAll("SELECT * FROM reseller_plans ORDER BY price");
        $this->view('super_admin/reseller_plans/index', ['pageTitle'=>'Reseller Packages','panelType'=>'admin','plans'=>$plans,'flash'=>$this->getFlash()]);
    }

    public function create(): void {
        $this->requireSuperAdmin();
        $this->view('super_admin/reseller_plans/form', ['pageTitle'=>'Create Reseller Package','panelType'=>'admin','plan'=>null,'flash'=>$this->getFlash()]);
    }

    public function store(): void {
        $this->requireSuperAdmin();
        $this->db->insert("INSERT INTO reseller_plans (name,description,price,max_schools) VALUES (?,?,?,?)",
            [$_POST['name'],$_POST['description']??'',(float)$_POST['price'],(int)$_POST['max_schools']]);
        $this->flash('success','Reseller package created.'); $this->redirect('/admin/reseller-plans');
    }

    public function edit(string $id): void {
        $this->requireSuperAdmin();
        $plan = $this->db->fetchOne("SELECT * FROM reseller_plans WHERE id=?", [$id]);
        $this->view('super_admin/reseller_plans/form', ['pageTitle'=>'Edit Reseller Package','panelType'=>'admin','plan'=>$plan,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requireSuperAdmin();
        $this->db->execute("UPDATE reseller_plans SET name=?,description=?,price=?,max_schools=? WHERE id=?",
            [$_POST['name'],$_POST['description']??'',(float)$_POST['price'],(int)$_POST['max_schools'],$id]);
        $this->flash('success','Reseller package updated.'); $this->redirect('/admin/reseller-plans');
    }
}

class AdminUserController extends Controller {
    public function index(): void {
        $this->requireSuperAdmin();
        $users = $this->db->fetchAll("SELECT u.*, r.name AS role_name, t.name AS tenant_name FROM users u JOIN roles r ON u.role_id=r.id LEFT JOIN tenants t ON u.tenant_id=t.id ORDER BY u.created_at DESC LIMIT 100");
        $this->view('super_admin/users/index', ['pageTitle'=>'Platform Users','panelType'=>'admin','users'=>$users,'flash'=>$this->getFlash()]);
    }
}

class ResellerDashboardController extends Controller {
    public function index(): void {
        $this->requireReseller();
        $rid = $_SESSION['reseller_id'] ?? 0;
        $reseller = $this->db->fetchOne("SELECT * FROM resellers WHERE id=?", [$rid]);
        $stats = [
            'schools'  => $this->db->fetchOne("SELECT COUNT(*) AS c FROM tenants WHERE reseller_id=?",[$rid])['c']??0,
            'students' => $this->db->fetchOne("SELECT COUNT(*) AS c FROM students s JOIN tenants t ON s.tenant_id=t.id WHERE t.reseller_id=?",[$rid])['c']??0,
            'revenue'  => $this->db->fetchOne("SELECT COALESCE(SUM(amount_paid),0) AS c FROM subscriptions WHERE reseller_id=?",[$rid])['c']??0,
            'max_schools' => $reseller['max_schools'] ?? 0
        ];
        $schools = $this->db->fetchAll("SELECT t.*, p.name AS plan_name FROM tenants t LEFT JOIN plans p ON t.plan_id=p.id WHERE t.reseller_id=? ORDER BY t.created_at DESC LIMIT 10",[$rid]);
        $this->view('reseller/dashboard', ['pageTitle'=>'Reseller Dashboard','panelType'=>'reseller','stats'=>$stats,'schools'=>$schools,'flash'=>$this->getFlash()]);
    }
}

class ResellerSchoolController extends Controller {
    public function index(): void {
        $this->requireReseller();
        $rid = $_SESSION['reseller_id'] ?? 0;
        $schools = $this->db->fetchAll("SELECT t.*, p.name AS plan_name FROM tenants t LEFT JOIN plans p ON t.plan_id=p.id WHERE t.reseller_id=? ORDER BY t.name",[$rid]);
        $this->view('reseller/schools/index', ['pageTitle'=>'My Schools','panelType'=>'reseller','schools'=>$schools,'flash'=>$this->getFlash()]);
    }

    public function create(): void {
        $this->requireReseller();
        $plans = $this->db->fetchAll("SELECT id,name FROM plans WHERE is_active=1");
        $this->view('reseller/schools/form', ['pageTitle'=>'Add School','panelType'=>'reseller','school'=>null,'plans'=>$plans,'flash'=>$this->getFlash()]);
    }

    public function store(): void {
        $this->requireReseller();
        $rid  = $_SESSION['reseller_id'] ?? 0;
        
        // Check reseller limit
        $reseller = $this->db->fetchOne("SELECT max_schools FROM resellers WHERE id=?", [$rid]);
        $current  = $this->db->fetchOne("SELECT COUNT(*) AS c FROM tenants WHERE reseller_id=?", [$rid])['c'];
        
        if ($current >= ($reseller['max_schools'] ?? 0)) {
            $this->flash('error', 'You have reached your limit of ' . $reseller['max_schools'] . ' schools. Please contact admin to upgrade.');
            $this->redirect('/reseller/schools');
        }

        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i','-',$name)).'-'.time();
        $this->db->insert("INSERT INTO tenants (reseller_id,plan_id,institution_type,name,slug,email,phone,country,status) VALUES (?,?,?,?,?,?,?,?,?)",
            [$rid,$_POST['plan_id']??null,$_POST['institution_type'],$name,$slug,$_POST['email']??'',$_POST['phone']??'',$_POST['country']??'','pending']);
        $this->flash('success','School created.'); $this->redirect('/reseller/schools');
    }

    public function show(string $id): void {
        $this->requireReseller();
        $rid = $_SESSION['reseller_id'] ?? 0;
        $school = $this->db->fetchOne("SELECT * FROM tenants WHERE id=? AND reseller_id=?",[$id,$rid]);
        if (!$school) { $this->redirect('/reseller/schools'); }
        $this->view('reseller/schools/show', ['pageTitle'=>$school['name'],'panelType'=>'reseller','school'=>$school,'flash'=>$this->getFlash()]);
    }
}

class ResellerBrandingController extends Controller {
    public function index(): void {
        $this->requireReseller();
        $rid = $_SESSION['reseller_id'] ?? 0;
        $reseller = $this->db->fetchOne("SELECT * FROM resellers WHERE id=?",[$rid]);
        $this->view('reseller/branding', ['pageTitle'=>'White-Label Branding','panelType'=>'reseller','reseller'=>$reseller,'flash'=>$this->getFlash()]);
    }

    public function update(): void {
        $this->requireReseller();
        $rid = $_SESSION['reseller_id'] ?? 0;
        $this->db->execute("UPDATE resellers SET name=?,currency=?,primary_color=?,secondary_color=?,domain=? WHERE id=?",
            [$_POST['name'],$_POST['currency']??'Ksh',$_POST['primary_color']??'#4F46E5',$_POST['secondary_color']??'#7C3AED',$_POST['domain']??null,$rid]);
        $_SESSION['branding']['name']            = $_POST['name'];
        $_SESSION['branding']['primary_color']   = $_POST['primary_color'];
        $_SESSION['branding']['secondary_color']  = $_POST['secondary_color'];
        $this->flash('success','Branding updated.'); $this->redirect('/reseller/branding');
    }
}

class ResellerBillingController extends Controller {
    public function index(): void {
        $this->requireReseller();
        $rid  = $_SESSION['reseller_id'] ?? 0;
        $subs = $this->db->fetchAll("SELECT s.*,t.name AS school_name,p.name AS plan_name FROM subscriptions s JOIN tenants t ON s.tenant_id=t.id JOIN plans p ON s.plan_id=p.id WHERE s.reseller_id=? ORDER BY s.created_at DESC",[$rid]);
        $this->view('reseller/billing', ['pageTitle'=>'Billing','panelType'=>'reseller','subscriptions'=>$subs,'flash'=>$this->getFlash()]);
    }
}
