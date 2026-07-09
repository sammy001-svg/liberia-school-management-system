<?php
require_once ROOT_DIR . '/core/Controller.php';

class BusController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    // --- BUSES ---
    public function buses(): void {
        $this->requireAuth(['School Admin']);
        $buses = $this->db->fetchAll(
            "SELECT b.*, (SELECT COUNT(*) FROM bus_routes r WHERE r.bus_id=b.id) AS route_count
             FROM buses b WHERE b.tenant_id=? ORDER BY b.bus_number", [$this->tid]
        );
        $stats = [
            'total'  => count($buses),
            'active' => count(array_filter($buses, fn($b) => $b['status']==='active')),
            'maintenance' => count(array_filter($buses, fn($b) => $b['status']==='maintenance')),
        ];
        $this->view('school/highschool/transport/buses', ['pageTitle'=>'Buses','panelType'=>'school','buses'=>$buses,'stats'=>$stats,'flash'=>$this->getFlash()]);
    }

    public function storeBus(): void {
        $this->requireAuth(['School Admin']);
        $errors = $this->validate($_POST, ['bus_number' => 'required|max:50', 'capacity' => 'numeric']);
        if ($errors) { $this->failValidation($errors, '/school/transport/buses'); }
        $this->db->insert(
            "INSERT INTO buses (tenant_id,bus_number,plate_number,capacity,model,status) VALUES (?,?,?,?,?,?)",
            [$this->tid, $_POST['bus_number'], $_POST['plate_number'] ?: null, $_POST['capacity'] ?: 40, $_POST['model'] ?: null, $_POST['status'] ?: 'active']
        );
        $this->flash('success', 'Bus added.');
        $this->redirect('/school/transport/buses');
    }

    public function updateBus(string $id): void {
        $this->requireAuth(['School Admin']);
        $errors = $this->validate($_POST, ['bus_number' => 'required|max:50', 'capacity' => 'numeric']);
        if ($errors) { $this->failValidation($errors, '/school/transport/buses'); }
        $this->db->execute(
            "UPDATE buses SET bus_number=?,plate_number=?,capacity=?,model=?,status=? WHERE id=? AND tenant_id=?",
            [$_POST['bus_number'], $_POST['plate_number'] ?: null, $_POST['capacity'] ?: 40, $_POST['model'] ?: null, $_POST['status'] ?: 'active', $id, $this->tid]
        );
        $this->flash('success', 'Bus updated.');
        $this->redirect('/school/transport/buses');
    }

    public function deleteBus(string $id): void {
        $this->requireAuth(['School Admin']);
        $this->db->execute("DELETE FROM buses WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Bus removed.');
        $this->redirect('/school/transport/buses');
    }

    // --- DRIVERS ---
    public function drivers(): void {
        $this->requireAuth(['School Admin']);
        $drivers = $this->db->fetchAll(
            "SELECT d.*, (SELECT COUNT(*) FROM bus_routes r WHERE r.driver_id=d.id) AS route_count
             FROM bus_drivers d WHERE d.tenant_id=? ORDER BY d.name", [$this->tid]
        );
        $stats = ['total' => count($drivers), 'active' => count(array_filter($drivers, fn($d) => $d['status']==='active'))];
        $this->view('school/highschool/transport/drivers', ['pageTitle'=>'Drivers','panelType'=>'school','drivers'=>$drivers,'stats'=>$stats,'flash'=>$this->getFlash()]);
    }

    public function storeDriver(): void {
        $this->requireAuth(['School Admin']);
        $errors = $this->validate($_POST, ['name' => 'required|max:150']);
        if ($errors) { $this->failValidation($errors, '/school/transport/drivers'); }
        $this->db->insert(
            "INSERT INTO bus_drivers (tenant_id,name,phone,license_no,address,status) VALUES (?,?,?,?,?,?)",
            [$this->tid, $_POST['name'], $_POST['phone'] ?: null, $_POST['license_no'] ?: null, $_POST['address'] ?: null, $_POST['status'] ?: 'active']
        );
        $this->flash('success', 'Driver added.');
        $this->redirect('/school/transport/drivers');
    }

    public function updateDriver(string $id): void {
        $this->requireAuth(['School Admin']);
        $errors = $this->validate($_POST, ['name' => 'required|max:150']);
        if ($errors) { $this->failValidation($errors, '/school/transport/drivers'); }
        $this->db->execute(
            "UPDATE bus_drivers SET name=?,phone=?,license_no=?,address=?,status=? WHERE id=? AND tenant_id=?",
            [$_POST['name'], $_POST['phone'] ?: null, $_POST['license_no'] ?: null, $_POST['address'] ?: null, $_POST['status'] ?: 'active', $id, $this->tid]
        );
        $this->flash('success', 'Driver updated.');
        $this->redirect('/school/transport/drivers');
    }

    public function deleteDriver(string $id): void {
        $this->requireAuth(['School Admin']);
        $this->db->execute("DELETE FROM bus_drivers WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Driver removed.');
        $this->redirect('/school/transport/drivers');
    }

    // --- ROUTES ---
    public function routes(): void {
        $this->requireAuth(['School Admin','Accountant']);
        $routes = $this->db->fetchAll(
            "SELECT r.*, b.bus_number, d.name AS driver_name,
                    (SELECT COUNT(*) FROM bus_students bs WHERE bs.route_id=r.id AND bs.status='active') AS student_count
             FROM bus_routes r
             LEFT JOIN buses b ON r.bus_id=b.id
             LEFT JOIN bus_drivers d ON r.driver_id=d.id
             WHERE r.tenant_id=? ORDER BY r.name", [$this->tid]
        );
        $buses = $this->db->fetchAll("SELECT id,bus_number FROM buses WHERE tenant_id=? AND status='active' ORDER BY bus_number", [$this->tid]);
        $drivers = $this->db->fetchAll("SELECT id,name FROM bus_drivers WHERE tenant_id=? AND status='active' ORDER BY name", [$this->tid]);
        $stats = [
            'total'    => count($routes),
            'students' => array_sum(array_column($routes, 'student_count')),
            'revenue'  => array_sum(array_map(fn($r) => $r['monthly_fee'] * $r['student_count'], $routes)),
        ];
        $this->view('school/highschool/transport/routes', [
            'pageTitle'=>'Bus Routes','panelType'=>'school','routes'=>$routes,'buses'=>$buses,'drivers'=>$drivers,'stats'=>$stats,'flash'=>$this->getFlash(),
        ]);
    }

    public function storeRoute(): void {
        $this->requireAuth(['School Admin']);
        $errors = $this->validate($_POST, ['name' => 'required|max:150', 'monthly_fee' => 'numeric']);
        if ($errors) { $this->failValidation($errors, '/school/transport/routes'); }
        $this->db->insert(
            "INSERT INTO bus_routes (tenant_id,name,bus_id,driver_id,stops,monthly_fee,departure_time,return_time,status) VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $this->tid, $_POST['name'], $_POST['bus_id'] ?: null, $_POST['driver_id'] ?: null, $_POST['stops'] ?? '',
                $_POST['monthly_fee'] ?: 0, $_POST['departure_time'] ?: null, $_POST['return_time'] ?: null, $_POST['status'] ?: 'active',
            ]
        );
        $this->flash('success', 'Route created.');
        $this->redirect('/school/transport/routes');
    }

    public function updateRoute(string $id): void {
        $this->requireAuth(['School Admin']);
        $errors = $this->validate($_POST, ['name' => 'required|max:150', 'monthly_fee' => 'numeric']);
        if ($errors) { $this->failValidation($errors, '/school/transport/routes'); }
        $this->db->execute(
            "UPDATE bus_routes SET name=?,bus_id=?,driver_id=?,stops=?,monthly_fee=?,departure_time=?,return_time=?,status=? WHERE id=? AND tenant_id=?",
            [
                $_POST['name'], $_POST['bus_id'] ?: null, $_POST['driver_id'] ?: null, $_POST['stops'] ?? '',
                $_POST['monthly_fee'] ?: 0, $_POST['departure_time'] ?: null, $_POST['return_time'] ?: null, $_POST['status'] ?: 'active',
                $id, $this->tid,
            ]
        );
        $this->flash('success', 'Route updated.');
        $this->redirect('/school/transport/routes');
    }

    public function deleteRoute(string $id): void {
        $this->requireAuth(['School Admin']);
        $this->db->execute("DELETE FROM bus_routes WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Route removed.');
        $this->redirect('/school/transport/routes');
    }

    // --- ROUTE STUDENT ASSIGNMENT ---
    public function routeStudents(string $id): void {
        $this->requireAuth(['School Admin']);
        $route = $this->db->fetchOne(
            "SELECT r.*, b.bus_number, d.name AS driver_name FROM bus_routes r
             LEFT JOIN buses b ON r.bus_id=b.id LEFT JOIN bus_drivers d ON r.driver_id=d.id
             WHERE r.id=? AND r.tenant_id=?", [$id, $this->tid]
        );
        if (!$route) { $this->redirect('/school/transport/routes'); }

        $assigned = $this->db->fetchAll(
            "SELECT bs.*, u.name AS student_name, s.admission_no, c.name AS class_name
             FROM bus_students bs JOIN students s ON bs.student_id=s.id JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
             WHERE bs.route_id=? ORDER BY u.name", [$id]
        );
        $availableStudents = $this->db->fetchAll(
            "SELECT s.id, u.name FROM students s JOIN users u ON s.user_id=u.id
             WHERE s.tenant_id=? AND s.status='active' AND s.id NOT IN (SELECT student_id FROM bus_students WHERE tenant_id=?)
             ORDER BY u.name", [$this->tid, $this->tid]
        );
        $this->view('school/highschool/transport/route_students', [
            'pageTitle'=>'Students — '.$route['name'],'panelType'=>'school',
            'route'=>$route,'assigned'=>$assigned,'availableStudents'=>$availableStudents,'flash'=>$this->getFlash(),
        ]);
    }

    public function assignStudent(string $id): void {
        $this->requireAuth(['School Admin']);
        $errors = $this->validate($_POST, ['student_id' => 'required']);
        if ($errors) { $this->failValidation($errors, '/school/transport/routes/'.$id.'/students'); }
        try {
            $this->db->insert(
                "INSERT INTO bus_students (tenant_id,route_id,student_id,pickup_stop) VALUES (?,?,?,?)",
                [$this->tid, $id, $_POST['student_id'], $_POST['pickup_stop'] ?? '']
            );
            $this->flash('success', 'Student assigned to route.');
        } catch (\Throwable $e) {
            $this->flash('danger', 'That student is already assigned to a bus route.');
        }
        $this->redirect('/school/transport/routes/'.$id.'/students');
    }

    public function unassignStudent(string $id, string $studentId): void {
        $this->requireAuth(['School Admin']);
        $this->db->execute("DELETE FROM bus_students WHERE route_id=? AND student_id=? AND tenant_id=?", [$id, $studentId, $this->tid]);
        $this->flash('success', 'Student removed from route.');
        $this->redirect('/school/transport/routes/'.$id.'/students');
    }
}
