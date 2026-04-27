<?php
namespace App\Controllers;

use App\Core\Controller;

class SlaController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']); $this->requireFeature('sla');
        $this->requireCan('sla.view');
        $hasDeptCol = false;
        try { $hasDeptCol = (bool)$this->db->one("SHOW COLUMNS FROM sla_policies LIKE 'department_id'"); } catch (\Throwable $_e) {}
        $deptSelect = $hasDeptCol ? ', d.name AS department_name, d.color AS department_color' : '';
        $deptJoin = $hasDeptCol ? ' LEFT JOIN departments d ON d.id = sla_policies.department_id' : '';
        $policies = $this->db->all(
            'SELECT sla_policies.*' . $deptSelect . ' FROM sla_policies' . $deptJoin . '
             WHERE sla_policies.tenant_id=? ORDER BY FIELD(sla_policies.priority,"urgent","high","medium","low"), sla_policies.id',
            [$tenant->id]
        );

        // Auto-seed políticas por defecto si están vacías
        if (empty($policies)) {
            $defaults = [
                ['Urgente · Atención inmediata', 'urgent', 15,  240,  'Sistemas caídos o impacto severo en operaciones.'],
                ['Alta · Respuesta prioritaria', 'high',   60,  480,  'Funcionalidad importante afectada para múltiples usuarios.'],
                ['Media · Estándar',             'medium', 240, 1440, 'Tickets normales sin bloqueo grave.'],
                ['Baja · Mejoras y consultas',   'low',    480, 2880, 'Solicitudes informativas o problemas menores.'],
            ];
            foreach ($defaults as [$n, $p, $rsp, $res, $d]) {
                $this->db->insert('sla_policies', [
                    'tenant_id' => $tenant->id,
                    'name' => $n, 'priority' => $p,
                    'response_minutes' => $rsp, 'resolve_minutes' => $res,
                    'description' => $d, 'active' => 1,
                ]);
            }
            $policies = $this->db->all('SELECT * FROM sla_policies WHERE tenant_id=? ORDER BY FIELD(priority,"urgent","high","medium","low")', [$tenant->id]);
        }

        $compliance = (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status IN ('resolved','closed') AND sla_breached=0", [$tenant->id]);
        $breached = (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND sla_breached=1", [$tenant->id]);
        $atRisk = (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status IN ('open','in_progress') AND sla_due_at IS NOT NULL AND TIMESTAMPDIFF(MINUTE, NOW(), sla_due_at) BETWEEN 0 AND 60", [$tenant->id]);

        $departments = [];
        if ($hasDeptCol && \App\Core\Plan::has($tenant, 'departments')) {
            try {
                $departments = $this->db->all('SELECT id, name, color FROM departments WHERE tenant_id=? AND is_active=1 ORDER BY sort_order, name', [$tenant->id]);
            } catch (\Throwable $_e) {}
        }

        $this->render('sla/index', [
            'title'=>'Políticas SLA',
            'policies'=>$policies,
            'compliance'=>$compliance,
            'breached'=>$breached,
            'atRisk'=>$atRisk,
            'departments'=>$departments,
            'hasDeptCol'=>$hasDeptCol,
        ]);
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']); $this->requireFeature('sla');
        $this->requireCan('sla.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $data = [
            'name' => (string)$this->input('name','Policy'),
            'response_minutes' => (int)$this->input('response_minutes',60),
            'resolve_minutes' => (int)$this->input('resolve_minutes',1440),
            'active' => (int)($this->input('active',0) ? 1 : 0),
        ];
        try {
            if ($this->db->one("SHOW COLUMNS FROM sla_policies LIKE 'department_id'")) {
                $data['department_id'] = ((int)$this->input('department_id', 0)) ?: null;
            }
        } catch (\Throwable $_e) {}
        $this->db->update('sla_policies', $data, 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success','Política actualizada.');
        $this->redirect('/t/' . $tenant->slug . '/sla');
    }
}
