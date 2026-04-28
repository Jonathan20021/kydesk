<?php
namespace App\Controllers;

use App\Core\Controller;

class ReportBuilderController extends Controller
{
    /** Catálogo de widgets disponibles. */
    public const WIDGETS = [
        'tickets_by_status' => ['title' => 'Tickets por estado', 'type' => 'donut', 'icon' => 'pie-chart'],
        'tickets_by_priority' => ['title' => 'Tickets por prioridad', 'type' => 'donut', 'icon' => 'flag'],
        'tickets_by_category' => ['title' => 'Tickets por categoría', 'type' => 'bar', 'icon' => 'tag'],
        'tickets_by_agent' => ['title' => 'Tickets por agente', 'type' => 'bar', 'icon' => 'users'],
        'tickets_per_day' => ['title' => 'Tickets por día (últimos 30)', 'type' => 'line', 'icon' => 'line-chart'],
        'avg_resolution_time' => ['title' => 'Tiempo promedio de resolución', 'type' => 'kpi', 'icon' => 'timer'],
        'sla_compliance' => ['title' => 'Cumplimiento SLA', 'type' => 'kpi', 'icon' => 'gauge'],
        'open_tickets' => ['title' => 'Tickets abiertos', 'type' => 'kpi', 'icon' => 'inbox'],
        'csat_score' => ['title' => 'CSAT score', 'type' => 'kpi', 'icon' => 'smile'],
        'top_companies' => ['title' => 'Top 10 empresas', 'type' => 'table', 'icon' => 'building-2'],
    ];

    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('reports_builder');
        $this->requireCan('reports.builder');

        $reports = $this->db->all(
            'SELECT r.*, u.name AS author_name FROM custom_reports r LEFT JOIN users u ON u.id = r.created_by WHERE r.tenant_id = ? ORDER BY r.is_favorite DESC, r.updated_at DESC',
            [$tenant->id]
        );
        $this->render('reports_builder/index', [
            'title' => 'Reports Builder',
            'reports' => $reports,
            'widgets' => self::WIDGETS,
        ]);
    }

    public function create(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('reports_builder');
        $this->requireCan('reports.builder');
        $id = $this->db->insert('custom_reports', [
            'tenant_id' => $tenant->id,
            'name' => 'Nuevo reporte',
            'layout' => json_encode([]),
            'filters' => json_encode([]),
            'created_by' => $this->auth->userId(),
        ]);
        $this->redirect('/t/' . $tenant->slug . '/reports-builder/' . $id);
    }

    public function show(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('reports_builder');
        $this->requireCan('reports.builder');
        $id = (int)$params['id'];
        $report = $this->db->one('SELECT * FROM custom_reports WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$report) { $this->redirect('/t/' . $tenant->slug . '/reports-builder'); }

        $layout = json_decode($report['layout'] ?? '[]', true) ?: [];
        $filters = json_decode($report['filters'] ?? '[]', true) ?: [];
        $filters['from'] = $filters['from'] ?? date('Y-m-01');
        $filters['to'] = $filters['to'] ?? date('Y-m-d');

        $widgetData = [];
        foreach ($layout as $w) {
            $key = $w['key'] ?? '';
            if (!isset(self::WIDGETS[$key])) continue;
            $widgetData[$key] = $this->computeWidget($tenant->id, $key, $filters);
        }

        $this->render('reports_builder/show', [
            'title' => $report['name'],
            'report' => $report,
            'layout' => $layout,
            'filters' => $filters,
            'widgets' => self::WIDGETS,
            'widgetData' => $widgetData,
        ]);
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('reports_builder');
        $this->requireCan('reports.builder');
        $this->validateCsrf();
        $id = (int)$params['id'];

        $layout = $this->input('layout');
        $layoutArr = is_string($layout) ? (json_decode($layout, true) ?: []) : (is_array($layout) ? $layout : []);
        $filters = [
            'from' => (string)$this->input('filter_from', date('Y-m-01')),
            'to' => (string)$this->input('filter_to', date('Y-m-d')),
        ];

        $this->db->update('custom_reports', [
            'name' => trim((string)$this->input('name','Reporte')),
            'description' => (string)$this->input('description','') ?: null,
            'layout' => json_encode($layoutArr),
            'filters' => json_encode($filters),
            'is_shared' => (int)($this->input('is_shared') ? 1 : 0),
            'is_favorite' => (int)($this->input('is_favorite') ? 1 : 0),
            'schedule_emails' => (string)$this->input('schedule_emails','') ?: null,
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);

        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        if ($isAjax) $this->json(['ok'=>true]);
        else { $this->session->flash('success','Reporte guardado.'); $this->redirect('/t/' . $tenant->slug . '/reports-builder/' . $id); }
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('reports_builder');
        $this->requireCan('reports.builder');
        $this->validateCsrf();
        $this->db->delete('custom_reports', 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->redirect('/t/' . $tenant->slug . '/reports-builder');
    }

    /** Compute data for a widget. */
    protected function computeWidget(int $tenantId, string $key, array $filters): array
    {
        $from = $filters['from'] . ' 00:00:00';
        $to   = $filters['to']   . ' 23:59:59';
        // Sin alias para queries simples sobre tickets
        $baseT = 'WHERE tenant_id = ? AND created_at BETWEEN ? AND ?';
        // Con alias `t.` para queries con JOIN — evita ambigüedad de columnas comunes (tenant_id)
        $baseTAlias = 'WHERE t.tenant_id = ? AND t.created_at BETWEEN ? AND ?';
        $args = [$tenantId, $from, $to];

        switch ($key) {
            case 'tickets_by_status':
                $rows = $this->db->all("SELECT status AS label, COUNT(*) AS value FROM tickets $baseT GROUP BY status", $args);
                return ['type' => 'donut', 'rows' => $rows];
            case 'tickets_by_priority':
                $rows = $this->db->all("SELECT priority AS label, COUNT(*) AS value FROM tickets $baseT GROUP BY priority", $args);
                return ['type' => 'donut', 'rows' => $rows];
            case 'tickets_by_category':
                $rows = $this->db->all(
                    "SELECT IFNULL(c.name, 'Sin categoría') AS label, COUNT(*) AS value
                     FROM tickets t LEFT JOIN ticket_categories c ON c.id = t.category_id
                     $baseTAlias
                     GROUP BY c.id, c.name ORDER BY value DESC LIMIT 10", $args);
                return ['type' => 'bar', 'rows' => $rows];
            case 'tickets_by_agent':
                $rows = $this->db->all(
                    "SELECT IFNULL(u.name, 'Sin asignar') AS label, COUNT(*) AS value
                     FROM tickets t LEFT JOIN users u ON u.id = t.assigned_to
                     $baseTAlias
                     GROUP BY u.id, u.name ORDER BY value DESC LIMIT 10", $args);
                return ['type' => 'bar', 'rows' => $rows];
            case 'tickets_per_day':
                $rows = $this->db->all(
                    "SELECT DATE(created_at) AS label, COUNT(*) AS value FROM tickets
                     WHERE tenant_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                     GROUP BY DATE(created_at) ORDER BY label ASC",
                    [$tenantId]);
                return ['type' => 'line', 'rows' => $rows];
            case 'avg_resolution_time':
                $hours = (float)$this->db->val(
                    "SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at) / 60) FROM tickets
                     $baseT AND resolved_at IS NOT NULL", $args);
                return ['type' => 'kpi', 'value' => round($hours, 1) . 'h', 'sub' => 'Promedio'];
            case 'sla_compliance':
                $total = (int)$this->db->val("SELECT COUNT(*) FROM tickets $baseT", $args);
                $breached = (int)$this->db->val("SELECT COUNT(*) FROM tickets $baseT AND sla_breached = 1", $args);
                $pct = $total > 0 ? round((($total - $breached) / $total) * 100, 1) : 100;
                return ['type' => 'kpi', 'value' => $pct . '%', 'sub' => "$breached / $total breached"];
            case 'open_tickets':
                $cnt = (int)$this->db->val(
                    "SELECT COUNT(*) FROM tickets WHERE tenant_id = ? AND status IN ('open','in_progress','on_hold')",
                    [$tenantId]);
                return ['type' => 'kpi', 'value' => $cnt, 'sub' => 'Sin resolver'];
            case 'csat_score':
                try {
                    $avg = (float)$this->db->val(
                        "SELECT AVG(score) FROM csat_surveys WHERE tenant_id = ? AND type = 'csat' AND responded_at BETWEEN ? AND ?",
                        [$tenantId, $from, $to]);
                    return ['type' => 'kpi', 'value' => $avg > 0 ? round($avg, 2) : '—', 'sub' => 'CSAT (1-5)'];
                } catch (\Throwable $e) {
                    return ['type' => 'kpi', 'value' => '—', 'sub' => 'No disponible'];
                }
            case 'top_companies':
                $rows = $this->db->all(
                    "SELECT IFNULL(c.name, 'Sin empresa') AS label, COUNT(*) AS value
                     FROM tickets t LEFT JOIN companies c ON c.id = t.company_id
                     $baseTAlias
                     GROUP BY c.id, c.name ORDER BY value DESC LIMIT 10", $args);
                return ['type' => 'table', 'rows' => $rows];
        }
        return ['type' => 'kpi', 'value' => '—'];
    }
}
