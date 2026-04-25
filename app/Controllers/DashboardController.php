<?php
namespace App\Controllers;

use App\Core\Controller;

class DashboardController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $tid = $tenant->id;

        $stats = [
            'total'       => (int)$this->db->val('SELECT COUNT(*) FROM tickets WHERE tenant_id=?', [$tid]),
            'open'        => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='open'", [$tid]),
            'in_progress' => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='in_progress'", [$tid]),
            'resolved'    => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='resolved'", [$tid]),
            'closed'      => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status='closed'", [$tid]),
            'urgent'      => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND priority='urgent' AND status NOT IN ('resolved','closed')", [$tid]),
            'technicians' => (int)$this->db->val("SELECT COUNT(*) FROM users WHERE tenant_id=? AND is_technician=1", [$tid]),
            'users'       => (int)$this->db->val("SELECT COUNT(*) FROM users WHERE tenant_id=?", [$tid]),
            'companies'   => (int)$this->db->val("SELECT COUNT(*) FROM companies WHERE tenant_id=?", [$tid]),
            'assets'      => (int)$this->db->val("SELECT COUNT(*) FROM assets WHERE tenant_id=?", [$tid]),
            'kb_views'    => (int)$this->db->val("SELECT SUM(views) FROM kb_articles WHERE tenant_id=?", [$tid]) ?: 0,
            'automations' => (int)$this->db->val("SELECT COUNT(*) FROM automations WHERE tenant_id=? AND active=1", [$tid]),
        ];
        $stats['avg_response'] = (int)$this->db->val("SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) FROM tickets WHERE tenant_id=? AND first_response_at IS NOT NULL", [$tid]);
        $stats['sla_compliance'] = $stats['resolved'] + $stats['closed'] > 0
            ? round(((int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND status IN ('resolved','closed') AND sla_breached=0", [$tid])) * 100 / ($stats['resolved']+$stats['closed']))
            : 100;

        // series 14 días
        $series = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $series[$d] = ['created' => 0, 'resolved' => 0];
        }
        foreach ($this->db->all("SELECT DATE(created_at) d, COUNT(*) c FROM tickets WHERE tenant_id=? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) GROUP BY DATE(created_at)", [$tid]) as $r) {
            if (isset($series[$r['d']])) $series[$r['d']]['created'] = (int)$r['c'];
        }
        foreach ($this->db->all("SELECT DATE(resolved_at) d, COUNT(*) c FROM tickets WHERE tenant_id=? AND resolved_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) GROUP BY DATE(resolved_at)", [$tid]) as $r) {
            if (isset($series[$r['d']])) $series[$r['d']]['resolved'] = (int)$r['c'];
        }

        $byStatus = $this->db->all("SELECT status, COUNT(*) c FROM tickets WHERE tenant_id=? GROUP BY status", [$tid]);
        $byPriority = $this->db->all("SELECT priority, COUNT(*) c FROM tickets WHERE tenant_id=? GROUP BY priority", [$tid]);
        $byCategory = $this->db->all(
            "SELECT c.name, c.color, COUNT(t.id) AS c FROM ticket_categories c
             LEFT JOIN tickets t ON t.category_id = c.id AND t.tenant_id = c.tenant_id
             WHERE c.tenant_id = ? GROUP BY c.id, c.name, c.color ORDER BY c DESC LIMIT 6",
            [$tid]
        );

        $recentTickets = $this->db->all(
            "SELECT t.*, c.name AS category_name, c.color AS category_color, co.name AS company_name,
                    u.name AS assigned_name, u.email AS assigned_email
             FROM tickets t
             LEFT JOIN ticket_categories c ON c.id = t.category_id
             LEFT JOIN companies co ON co.id = t.company_id
             LEFT JOIN users u ON u.id = t.assigned_to
             WHERE t.tenant_id = ?
             ORDER BY t.updated_at DESC LIMIT 8",
            [$tid]
        );

        $myTodos = $this->db->all(
            "SELECT * FROM todos WHERE tenant_id=? AND user_id=? AND completed=0 ORDER BY FIELD(priority,'urgent','high','medium','low'), created_at DESC LIMIT 5",
            [$tid, $this->auth->userId()]
        );

        $topTechs = $this->db->all(
            "SELECT u.id, u.name, u.email,
                    SUM(CASE WHEN t.status IN ('resolved','closed') THEN 1 ELSE 0 END) AS resolved,
                    COUNT(t.id) AS total,
                    AVG(TIMESTAMPDIFF(MINUTE, t.created_at, COALESCE(t.first_response_at, NOW()))) AS avg_response
             FROM users u
             LEFT JOIN tickets t ON t.assigned_to = u.id AND t.tenant_id = u.tenant_id
             WHERE u.tenant_id = ? AND u.is_technician = 1
             GROUP BY u.id, u.name, u.email
             ORDER BY resolved DESC LIMIT 5",
            [$tid]
        );

        $atRiskTickets = $this->db->all(
            "SELECT t.*, u.name AS assigned_name, u.email AS assigned_email
             FROM tickets t LEFT JOIN users u ON u.id = t.assigned_to
             WHERE t.tenant_id=? AND t.status IN ('open','in_progress')
             AND t.sla_due_at IS NOT NULL
             AND TIMESTAMPDIFF(MINUTE, NOW(), t.sla_due_at) BETWEEN 0 AND 240
             ORDER BY t.sla_due_at ASC LIMIT 5",
            [$tid]
        );

        $activity = $this->db->all(
            "SELECT a.*, u.name user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
             WHERE a.tenant_id=? ORDER BY a.created_at DESC LIMIT 8",
            [$tid]
        );

        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'series' => $series,
            'byStatus' => $byStatus,
            'byPriority' => $byPriority,
            'byCategory' => $byCategory,
            'recentTickets' => $recentTickets,
            'myTodos' => $myTodos,
            'topTechs' => $topTechs,
            'atRiskTickets' => $atRiskTickets,
            'activity' => $activity,
        ]);
    }
}
