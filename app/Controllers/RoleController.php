<?php
require_once ROOT_DIR . '/core/Controller.php';

class RoleController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    // Roles a School Admin is allowed to hand out via the Users tab: built-in roles that
    // aren't tied to their own linked table (Teacher/Student/Parent have dedicated onboarding
    // flows elsewhere and their own teachers/students/parents rows), plus this tenant's custom roles.
    private const ASSIGNABLE_BUILTIN_ROLES = ['School Admin', 'Accountant', 'Staff'];

    // Builds the "(r.tenant_id IS NULL AND r.name IN (...)) OR r.tenant_id=?" fragment shared
    // by the users list and the assignment guard, plus its bound params in matching order.
    private function assignableRoleClause(string $alias = 'r'): array {
        $placeholders = implode(',', array_fill(0, count(self::ASSIGNABLE_BUILTIN_ROLES), '?'));
        $sql = "(({$alias}.tenant_id IS NULL AND {$alias}.name IN ({$placeholders})) OR {$alias}.tenant_id=?)";
        return [$sql, self::ASSIGNABLE_BUILTIN_ROLES];
    }

    private function allPermissionsByModule(): array {
        $perms = $this->db->fetchAll("SELECT * FROM permissions WHERE name != 'roles.manage' ORDER BY module, action");
        $grouped = [];
        foreach ($perms as $p) { $grouped[$p['module']][] = $p; }
        return $grouped;
    }

    public function index(): void {
        $this->requirePermission(['roles.manage']);
        $systemRoles = $this->db->fetchAll(
            "SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id=r.id AND u.tenant_id=?) AS user_count
             FROM roles r WHERE r.tenant_id IS NULL AND r.scope='school' ORDER BY r.name", [$this->tid]
        );
        $customRoles = $this->db->fetchAll(
            "SELECT r.*, (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id=r.id) AS permission_count,
                    (SELECT COUNT(*) FROM users u WHERE u.role_id=r.id) AS user_count
             FROM roles r WHERE r.tenant_id=? ORDER BY r.name", [$this->tid]
        );
        $this->view('school/roles/index', [
            'pageTitle' => 'Roles & Permissions', 'panelType' => 'school',
            'systemRoles' => $systemRoles, 'customRoles' => $customRoles,
            'flash' => $this->getFlash(),
        ]);
    }

    public function create(): void {
        $this->requirePermission(['roles.manage']);
        $this->view('school/roles/form', [
            'pageTitle' => 'New Role', 'panelType' => 'school',
            'role' => null, 'checked' => [], 'permissionsByModule' => $this->allPermissionsByModule(),
            'flash' => $this->getFlash(),
        ]);
    }

    private function syncPermissions(int $roleId, array $postedPermissions): void {
        $this->db->execute("DELETE FROM role_permissions WHERE role_id=?", [$roleId]);
        foreach ($postedPermissions as $module => $actions) {
            foreach ((array)$actions as $action) {
                $perm = $this->db->fetchOne("SELECT id FROM permissions WHERE module=? AND action=?", [$module, $action]);
                if ($perm) {
                    $this->db->execute("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)", [$roleId, $perm['id']]);
                }
            }
        }
    }

    public function store(): void {
        $this->requirePermission(['roles.manage']);
        $errors = $this->validate($_POST, ['name' => 'required|max:80']);
        if ($errors) { $this->failValidation($errors, '/school/roles/create'); }

        $roleId = $this->db->insert(
            "INSERT INTO roles (name, scope, tenant_id, description) VALUES (?, 'school', ?, ?)",
            [$_POST['name'], $this->tid, $_POST['description'] ?? '']
        );
        $this->syncPermissions((int)$roleId, $_POST['permissions'] ?? []);
        $this->flash('success', 'Role created.');
        $this->redirect('/school/roles');
    }

    private function findOwnedRole(string $id): array|false {
        return $this->db->fetchOne("SELECT * FROM roles WHERE id=? AND tenant_id=?", [$id, $this->tid]);
    }

    public function edit(string $id): void {
        $this->requirePermission(['roles.manage']);
        $role = $this->findOwnedRole($id);
        if (!$role) { $this->flash('danger', 'That role cannot be edited.'); $this->redirect('/school/roles'); }

        $existing = $this->db->fetchAll(
            "SELECT p.module, p.action FROM role_permissions rp JOIN permissions p ON rp.permission_id=p.id WHERE rp.role_id=?", [$id]
        );
        $checked = [];
        foreach ($existing as $p) { $checked[$p['module']][$p['action']] = true; }

        $this->view('school/roles/form', [
            'pageTitle' => 'Edit Role', 'panelType' => 'school',
            'role' => $role, 'checked' => $checked, 'permissionsByModule' => $this->allPermissionsByModule(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(string $id): void {
        $this->requirePermission(['roles.manage']);
        $role = $this->findOwnedRole($id);
        if (!$role) { $this->flash('danger', 'That role cannot be edited.'); $this->redirect('/school/roles'); }

        $errors = $this->validate($_POST, ['name' => 'required|max:80']);
        if ($errors) { $this->failValidation($errors, '/school/roles/'.$id.'/edit'); }

        $this->db->execute("UPDATE roles SET name=?, description=? WHERE id=? AND tenant_id=?",
            [$_POST['name'], $_POST['description'] ?? '', $id, $this->tid]);
        $this->syncPermissions((int)$id, $_POST['permissions'] ?? []);
        $this->flash('success', 'Role updated.');
        $this->redirect('/school/roles');
    }

    public function delete(string $id): void {
        $this->requirePermission(['roles.manage']);
        $role = $this->findOwnedRole($id);
        if (!$role) { $this->flash('danger', 'That role cannot be deleted.'); $this->redirect('/school/roles'); }

        $inUse = $this->db->fetchOne("SELECT COUNT(*) c FROM users WHERE role_id=?", [$id])['c'];
        if ($inUse > 0) {
            $this->flash('danger', "Reassign {$inUse} user(s) away from this role before deleting it.");
            $this->redirect('/school/roles');
        }
        $this->db->execute("DELETE FROM role_permissions WHERE role_id=?", [$id]);
        $this->db->execute("DELETE FROM roles WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Role deleted.');
        $this->redirect('/school/roles');
    }

    // --- USER ROLE ASSIGNMENT ---

    public function usersIndex(): void {
        $this->requirePermission(['roles.manage']);
        [$roleClause, $roleParams] = $this->assignableRoleClause();
        $users = $this->db->fetchAll(
            "SELECT u.id, u.name, u.email, r.id AS role_id, r.name AS role_name
             FROM users u JOIN roles r ON u.role_id=r.id
             WHERE u.tenant_id=? AND {$roleClause}
             ORDER BY u.name", array_merge([$this->tid], $roleParams, [$this->tid])
        );
        $assignableRoles = $this->db->fetchAll(
            "SELECT id, name FROM roles WHERE {$roleClause} ORDER BY name",
            array_merge($roleParams, [$this->tid])
        );
        $this->view('school/roles/users', [
            'pageTitle' => 'Assign Roles', 'panelType' => 'school',
            'users' => $users, 'assignableRoles' => $assignableRoles,
            'flash' => $this->getFlash(),
        ]);
    }

    public function assignUser(): void {
        $this->requirePermission(['roles.manage']);
        $errors = $this->validate($_POST, ['user_id' => 'required', 'role_id' => 'required']);
        if ($errors) { $this->failValidation($errors, '/school/roles/users'); }

        $user = $this->db->fetchOne("SELECT id FROM users WHERE id=? AND tenant_id=?", [$_POST['user_id'], $this->tid]);
        if (!$user) { $this->flash('danger', 'User not found.'); $this->redirect('/school/roles/users'); }

        [$roleClause, $roleParams] = $this->assignableRoleClause();
        $role = $this->db->fetchOne(
            "SELECT id, name FROM roles WHERE id=? AND {$roleClause}",
            array_merge([$_POST['role_id']], $roleParams, [$this->tid])
        );
        if (!$role) {
            $this->flash('danger', 'That role cannot be assigned here — Teacher, Student and Parent accounts are managed from their own sections.');
            $this->redirect('/school/roles/users');
        }

        $this->db->execute("UPDATE users SET role_id=? WHERE id=? AND tenant_id=?", [$role['id'], $_POST['user_id'], $this->tid]);
        $this->flash('success', 'Role updated.');
        $this->redirect('/school/roles/users');
    }
}
