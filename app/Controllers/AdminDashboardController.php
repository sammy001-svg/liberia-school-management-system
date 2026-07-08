<?php
require_once ROOT_DIR . '/core/Controller.php';

class AdminDashboardController extends Controller {
    public function index(): void {
        $this->requireSuperAdmin();

        $stats = [
            'resellers' => $this->db->fetchOne("SELECT COUNT(*) AS c FROM resellers WHERE slug != 'platform'")['c'] ?? 0,
            'schools'   => $this->db->fetchOne("SELECT COUNT(*) AS c FROM tenants WHERE slug != 'platform'")['c'] ?? 0,
            'students'  => $this->db->fetchOne("SELECT COUNT(*) AS c FROM students")['c'] ?? 0,
            'teachers'  => $this->db->fetchOne("SELECT COUNT(*) AS c FROM teachers")['c'] ?? 0,
            'revenue'   => $this->db->fetchOne("SELECT COALESCE(SUM(amount_paid),0) AS c FROM subscriptions")['c'] ?? 0,
            'active_subs' => $this->db->fetchOne("SELECT COUNT(*) AS c FROM subscriptions WHERE status='active'")['c'] ?? 0,
        ];

        $recentSchools = $this->db->fetchAll(
            "SELECT t.*, r.name AS reseller_name
             FROM tenants t LEFT JOIN resellers r ON t.reseller_id = r.id
             WHERE t.slug != 'platform'
             ORDER BY t.created_at DESC LIMIT 8"
        );

        $recentResellers = $this->db->fetchAll(
            "SELECT r.*, COUNT(t.id) AS school_count
             FROM resellers r
             LEFT JOIN tenants t ON t.reseller_id = r.id
             WHERE r.slug != 'platform'
             GROUP BY r.id
             ORDER BY r.created_at DESC LIMIT 6"
        );

        $this->view('super_admin/dashboard', [
            'pageTitle'       => 'Super Admin Dashboard',
            'panelType'       => 'admin',
            'stats'           => $stats,
            'recentSchools'   => $recentSchools,
            'recentResellers' => $recentResellers,
            'flash'           => $this->getFlash(),
        ]);
    }
}
