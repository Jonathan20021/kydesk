<?php
namespace App\Controllers;

use App\Core\Controller;

class DepartmentController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('departments');
        $this->requireCan('departments.view');

        $departments = $this->db->all(
            "SELECT d.*, u.name AS manager_name, u.email AS manager_email,
                    (SELECT COUNT(*) FROM department_users du WHERE du.department_id = d.id) AS agents_count,
                    (SELECT COUNT(*) FROM tickets t WHERE t.department_id = d.id) AS tickets_count,
                    (SELECT COUNT(*) FROM tickets t WHERE t.department_id = d.id AND t.status IN ('open','in_progress')) AS open_count
             FROM departments d
             LEFT JOIN users u ON u.id = d.manager_user_id
             WHERE d.tenant_id = ?
             ORDER BY d.is_active DESC, d.sort_order, d.name",
            [$tenant->id]
        );

        $stats = [
            'total'   => count($departments),
            'active'  => count(array_filter($departments, fn($d) => (int)$d['is_active'] === 1)),
            'agents'  => (int)$this->db->val(
                "SELECT COUNT(DISTINCT du.user_id) FROM department_users du
                 JOIN departments d ON d.id = du.department_id WHERE d.tenant_id = ?",
                [$tenant->id]
            ),
            'unassigned' => (int)$this->db->val(
                "SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND department_id IS NULL",
                [$tenant->id]
            ),
        ];

        $technicians = $this->db->all(
            'SELECT id, name, email, title FROM users WHERE tenant_id=? AND is_technician=1 AND is_active=1 ORDER BY name',
            [$tenant->id]
        );

        $this->render('departments/index', [
            'title' => 'Departamentos',
            'departments' => $departments,
            'stats' => $stats,
            'technicians' => $technicians,
        ]);
    }

    public function show(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('departments');
        $this->requireCan('departments.view');

        $id = (int)$params['id'];
        $dept = $this->db->one(
            "SELECT d.*, u.name AS manager_name, u.email AS manager_email
             FROM departments d LEFT JOIN users u ON u.id = d.manager_user_id
             WHERE d.id=? AND d.tenant_id=?",
            [$id, $tenant->id]
        );
        if (!$dept) {
            $this->session->flash('error', 'Departamento no encontrado.');
            $this->redirect('/t/' . $tenant->slug . '/departments');
        }

        $agents = $this->db->all(
            "SELECT u.id, u.name, u.email, u.title, du.is_lead,
                    (SELECT COUNT(*) FROM tickets t WHERE t.assigned_to = u.id AND t.department_id = ?) AS tickets_in_dept
             FROM department_users du
             JOIN users u ON u.id = du.user_id
             WHERE du.department_id = ? AND u.tenant_id = ?
             ORDER BY du.is_lead DESC, u.name",
            [$id, $id, $tenant->id]
        );

        $availableAgents = $this->db->all(
            "SELECT u.id, u.name, u.email, u.title FROM users u
             WHERE u.tenant_id=? AND u.is_technician=1 AND u.is_active=1
               AND u.id NOT IN (SELECT user_id FROM department_users WHERE department_id=?)
             ORDER BY u.name",
            [$tenant->id, $id]
        );

        $tickets = $this->db->all(
            "SELECT t.id, t.code, t.subject, t.status, t.priority, t.created_at,
                    u.name AS assigned_name
             FROM tickets t LEFT JOIN users u ON u.id = t.assigned_to
             WHERE t.department_id = ? AND t.tenant_id = ?
             ORDER BY FIELD(t.status,'open','in_progress','on_hold','resolved','closed'),
                      FIELD(t.priority,'urgent','high','medium','low'),
                      t.updated_at DESC LIMIT 25",
            [$id, $tenant->id]
        );

        $stats = [
            'total'       => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND department_id=?", [$tenant->id, $id]),
            'open'        => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND department_id=? AND status='open'", [$tenant->id, $id]),
            'in_progress' => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND department_id=? AND status='in_progress'", [$tenant->id, $id]),
            'resolved'    => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND department_id=? AND status='resolved'", [$tenant->id, $id]),
            'avg_hours'   => (float)$this->db->val("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) FROM tickets WHERE tenant_id=? AND department_id=? AND resolved_at IS NOT NULL", [$tenant->id, $id]),
            'breached'    => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND department_id=? AND sla_breached=1", [$tenant->id, $id]),
        ];

        $technicians = $this->db->all(
            'SELECT id, name FROM users WHERE tenant_id=? AND is_technician=1 AND is_active=1 ORDER BY name',
            [$tenant->id]
        );

        $slaPolicies = [];
        try {
            $slaPolicies = $this->db->all(
                'SELECT * FROM sla_policies WHERE tenant_id=? AND department_id=? ORDER BY FIELD(priority,"urgent","high","medium","low")',
                [$tenant->id, $id]
            );
        } catch (\Throwable $_e) { /* tabla puede no tener la columna aún */ }

        $this->render('departments/show', [
            'title' => $dept['name'],
            'dept' => $dept,
            'agents' => $agents,
            'availableAgents' => $availableAgents,
            'tickets' => $tickets,
            'stats' => $stats,
            'technicians' => $technicians,
            'slaPolicies' => $slaPolicies,
        ]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('departments');
        $this->requireCan('departments.create');
        $this->validateCsrf();

        $name = trim((string)$this->input('name', ''));
        if ($name === '') {
            $this->session->flash('error', 'El nombre es obligatorio.');
            $this->redirect('/t/' . $tenant->slug . '/departments');
        }

        $slug = $this->uniqueSlug($tenant->id, $name);
        $color = (string)$this->input('color', '#3b82f6');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#3b82f6';

        $id = $this->db->insert('departments', [
            'tenant_id'       => $tenant->id,
            'name'            => $name,
            'slug'            => $slug,
            'description'     => (string)$this->input('description', ''),
            'color'           => $color,
            'icon'            => (string)$this->input('icon', 'layers'),
            'manager_user_id' => ((int)$this->input('manager_user_id', 0)) ?: null,
            'email'           => (string)$this->input('email', '') ?: null,
            'is_active'       => (int)($this->input('is_active', 1) ? 1 : 0),
            'sort_order'      => (int)$this->input('sort_order', 0),
            'created_by'      => $this->auth->userId(),
        ]);

        // Si se asignó manager → meterlo en el pivote como lead
        $managerId = (int)$this->input('manager_user_id', 0);
        if ($managerId) {
            $this->db->run(
                'INSERT IGNORE INTO department_users (department_id, user_id, is_lead) VALUES (?,?,1)',
                [$id, $managerId]
            );
        }

        $this->logAudit('department.created', 'department', $id, ['name' => $name]);
        $this->session->flash('success', 'Departamento creado.');
        $this->redirect('/t/' . $tenant->slug . '/departments/' . $id);
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('departments');
        $this->requireCan('departments.edit');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $existing = $this->db->one('SELECT * FROM departments WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$existing) {
            $this->session->flash('error', 'Departamento no encontrado.');
            $this->redirect('/t/' . $tenant->slug . '/departments');
        }

        $name = trim((string)$this->input('name', ''));
        if ($name === '') {
            $this->session->flash('error', 'El nombre es obligatorio.');
            $this->redirect('/t/' . $tenant->slug . '/departments/' . $id);
        }

        $color = (string)$this->input('color', '#3b82f6');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#3b82f6';

        $data = [
            'name'            => $name,
            'description'     => (string)$this->input('description', ''),
            'color'           => $color,
            'icon'            => (string)$this->input('icon', 'layers'),
            'manager_user_id' => ((int)$this->input('manager_user_id', 0)) ?: null,
            'email'           => (string)$this->input('email', '') ?: null,
            'is_active'       => (int)($this->input('is_active', 0) ? 1 : 0),
            'sort_order'      => (int)$this->input('sort_order', 0),
        ];

        // Renombrar slug si cambió el nombre y no choca
        if ($name !== $existing['name']) {
            $data['slug'] = $this->uniqueSlug($tenant->id, $name, $id);
        }

        $this->db->update('departments', $data, 'id=? AND tenant_id=?', [$id, $tenant->id]);

        // Si cambió el manager → asegurar que esté en el pivote como lead
        $managerId = (int)$this->input('manager_user_id', 0);
        if ($managerId) {
            $this->db->run('UPDATE department_users SET is_lead=0 WHERE department_id=?', [$id]);
            $this->db->run(
                'INSERT INTO department_users (department_id, user_id, is_lead) VALUES (?,?,1)
                 ON DUPLICATE KEY UPDATE is_lead=1',
                [$id, $managerId]
            );
        }

        $this->logAudit('department.updated', 'department', $id, $data);
        $this->session->flash('success', 'Departamento actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/departments/' . $id);
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('departments');
        $this->requireCan('departments.delete');
        $this->validateCsrf();

        $id = (int)$params['id'];
        // Desvincular tickets y SLAs
        $this->db->run('UPDATE tickets SET department_id=NULL WHERE department_id=? AND tenant_id=?', [$id, $tenant->id]);
        try { $this->db->run('UPDATE sla_policies SET department_id=NULL WHERE department_id=? AND tenant_id=?', [$id, $tenant->id]); } catch (\Throwable $_e) {}
        $this->db->delete('department_users', 'department_id=?', [$id]);
        $this->db->delete('departments', 'id=? AND tenant_id=?', [$id, $tenant->id]);

        $this->logAudit('department.deleted', 'department', $id);
        $this->session->flash('success', 'Departamento eliminado.');
        $this->redirect('/t/' . $tenant->slug . '/departments');
    }

    public function addAgent(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('departments');
        $this->requireCan('departments.assign');
        $this->validateCsrf();

        $deptId = (int)$params['id'];
        $userId = (int)$this->input('user_id', 0);
        if (!$userId) { $this->back(); return; }

        // Validar que el usuario es del tenant
        $u = $this->db->one('SELECT id FROM users WHERE id=? AND tenant_id=?', [$userId, $tenant->id]);
        $d = $this->db->one('SELECT id FROM departments WHERE id=? AND tenant_id=?', [$deptId, $tenant->id]);
        if (!$u || !$d) { $this->back(); return; }

        $isLead = (int)($this->input('is_lead', 0) ? 1 : 0);
        $this->db->run(
            'INSERT INTO department_users (department_id, user_id, is_lead) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE is_lead=VALUES(is_lead)',
            [$deptId, $userId, $isLead]
        );
        $this->logAudit('department.agent_added', 'department', $deptId, ['user_id' => $userId, 'is_lead' => $isLead]);
        $this->session->flash('success', 'Agente añadido al departamento.');
        $this->redirect('/t/' . $tenant->slug . '/departments/' . $deptId);
    }

    public function removeAgent(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('departments');
        $this->requireCan('departments.assign');
        $this->validateCsrf();

        $deptId = (int)$params['id'];
        $userId = (int)$params['userId'];

        $d = $this->db->one('SELECT id FROM departments WHERE id=? AND tenant_id=?', [$deptId, $tenant->id]);
        if (!$d) { $this->back(); return; }

        $this->db->delete('department_users', 'department_id=? AND user_id=?', [$deptId, $userId]);
        $this->logAudit('department.agent_removed', 'department', $deptId, ['user_id' => $userId]);
        $this->session->flash('success', 'Agente removido del departamento.');
        $this->redirect('/t/' . $tenant->slug . '/departments/' . $deptId);
    }

    public function toggleLead(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('departments');
        $this->requireCan('departments.assign');
        $this->validateCsrf();

        $deptId = (int)$params['id'];
        $userId = (int)$params['userId'];
        $d = $this->db->one('SELECT id FROM departments WHERE id=? AND tenant_id=?', [$deptId, $tenant->id]);
        if (!$d) { $this->back(); return; }

        $row = $this->db->one('SELECT is_lead FROM department_users WHERE department_id=? AND user_id=?', [$deptId, $userId]);
        if (!$row) { $this->back(); return; }
        $newLead = $row['is_lead'] ? 0 : 1;
        $this->db->run('UPDATE department_users SET is_lead=? WHERE department_id=? AND user_id=?', [$newLead, $deptId, $userId]);
        $this->session->flash('success', $newLead ? 'Marcado como líder.' : 'Quitado como líder.');
        $this->redirect('/t/' . $tenant->slug . '/departments/' . $deptId);
    }

    protected function uniqueSlug(int $tenantId, string $name, ?int $exceptId = null): string
    {
        $base = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
        $base = trim($base, '-');
        if ($base === '') $base = 'dept';
        $slug = substr($base, 0, 70);
        $i = 1;
        while (true) {
            $sql = 'SELECT id FROM departments WHERE tenant_id=? AND slug=?';
            $args = [$tenantId, $slug];
            if ($exceptId) { $sql .= ' AND id<>?'; $args[] = $exceptId; }
            if (!$this->db->val($sql, $args)) return $slug;
            $i++;
            $slug = substr($base, 0, 70 - strlen((string)$i) - 1) . '-' . $i;
        }
    }

    protected function logAudit(string $action, string $entity, int $entityId, array $meta = []): void
    {
        $tenant = $this->app->tenant;
        if (!$tenant) return;
        try {
            $this->db->insert('audit_logs', [
                'tenant_id' => $tenant->id,
                'user_id'   => $this->auth->userId(),
                'action'    => $action,
                'entity'    => $entity,
                'entity_id' => $entityId,
                'meta'      => json_encode($meta, JSON_UNESCAPED_UNICODE),
                'ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua'        => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (\Throwable $_e) { /* tabla audit puede no estar */ }
    }
}
