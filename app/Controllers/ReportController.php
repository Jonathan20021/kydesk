<?php
namespace App\Controllers;

use App\Core\Controller;

class ReportController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('reports.view');
        $tid = $tenant->id;

        // 30 días
        $series = [];
        for ($i = 29; $i >= 0; $i--) { $series[date('Y-m-d', strtotime("-$i days"))] = ['created'=>0,'resolved'=>0]; }
        foreach ($this->db->all("SELECT DATE(created_at) d, COUNT(*) c FROM tickets WHERE tenant_id=? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(created_at)", [$tid]) as $r) {
            if (isset($series[$r['d']])) $series[$r['d']]['created'] = (int)$r['c'];
        }
        foreach ($this->db->all("SELECT DATE(resolved_at) d, COUNT(*) c FROM tickets WHERE tenant_id=? AND resolved_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(resolved_at)", [$tid]) as $r) {
            if (isset($series[$r['d']])) $series[$r['d']]['resolved'] = (int)$r['c'];
        }

        $byStatus = $this->db->all("SELECT status, COUNT(*) c FROM tickets WHERE tenant_id=? GROUP BY status", [$tid]);
        $byPriority = $this->db->all("SELECT priority, COUNT(*) c FROM tickets WHERE tenant_id=? GROUP BY priority", [$tid]);
        $byChannel = $this->db->all("SELECT channel, COUNT(*) c FROM tickets WHERE tenant_id=? GROUP BY channel", [$tid]);
        $byCategory = $this->db->all(
            "SELECT c.name, c.color, COUNT(t.id) c FROM ticket_categories c
             LEFT JOIN tickets t ON t.category_id = c.id
             WHERE c.tenant_id=? GROUP BY c.id, c.name, c.color ORDER BY c DESC",
            [$tid]
        );

        $byDepartment = [];
        if (\App\Core\Plan::has($tenant, 'departments')) {
            try {
                $byDepartment = $this->db->all(
                    "SELECT d.id, d.name, d.color, d.icon,
                            COUNT(t.id) total,
                            SUM(CASE WHEN t.status IN ('open','in_progress') THEN 1 ELSE 0 END) AS open_count,
                            SUM(CASE WHEN t.status IN ('resolved','closed') THEN 1 ELSE 0 END) AS resolved_count,
                            SUM(CASE WHEN t.sla_breached=1 THEN 1 ELSE 0 END) AS breached,
                            AVG(TIMESTAMPDIFF(HOUR, t.created_at, COALESCE(t.resolved_at, NOW()))) AS avg_hours
                     FROM departments d
                     LEFT JOIN tickets t ON t.department_id = d.id AND t.tenant_id = d.tenant_id
                     WHERE d.tenant_id=? GROUP BY d.id, d.name, d.color, d.icon
                     ORDER BY total DESC",
                    [$tid]
                );
            } catch (\Throwable $_e) { /* tabla no existe */ }
        }
        $agentPerf = $this->db->all(
            "SELECT u.name,
                    COUNT(t.id) total,
                    SUM(CASE WHEN t.status IN ('resolved','closed') THEN 1 ELSE 0 END) resolved,
                    AVG(TIMESTAMPDIFF(HOUR, t.created_at, COALESCE(t.resolved_at, NOW()))) avg_hours
             FROM users u LEFT JOIN tickets t ON t.assigned_to = u.id AND t.tenant_id = u.tenant_id
             WHERE u.tenant_id = ? AND u.is_active = 1
             GROUP BY u.id, u.name
             HAVING total > 0
             ORDER BY resolved DESC",
            [$tid]
        );

        $avgResolve = (float)$this->db->val("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) FROM tickets WHERE tenant_id=? AND resolved_at IS NOT NULL", [$tid]);

        $this->render('reports/index', [
            'title' => 'Reportes',
            'series' => $series,
            'byStatus' => $byStatus,
            'byPriority' => $byPriority,
            'byChannel' => $byChannel,
            'byCategory' => $byCategory,
            'byDepartment' => $byDepartment,
            'agentPerf' => $agentPerf,
            'avgResolve' => $avgResolve,
        ]);
    }

    public function data(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->json(['ok' => true, 'tenant' => $tenant->slug]);
    }
}
