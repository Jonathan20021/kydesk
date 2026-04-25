<?php
namespace App\Controllers\Admin;

class ReportController extends AdminController
{
    public function index(): void
    {
        $this->requireCan('reports.view');

        $kpis = [
            'total_tenants'   => (int)$this->db->val('SELECT COUNT(*) FROM tenants'),
            'paying_tenants'  => (int)$this->db->val("SELECT COUNT(*) FROM subscriptions WHERE status='active' AND amount > 0"),
            'trial_tenants'   => (int)$this->db->val("SELECT COUNT(*) FROM subscriptions WHERE status='trial'"),
            'churned'         => (int)$this->db->val("SELECT COUNT(*) FROM subscriptions WHERE status='cancelled'"),
            'mrr'             => (float)$this->db->val("SELECT COALESCE(SUM(amount),0) FROM subscriptions WHERE status='active' AND billing_cycle='monthly'"),
            'arr'             => (float)$this->db->val("SELECT COALESCE(SUM(amount*12),0) FROM subscriptions WHERE status='active' AND billing_cycle='monthly'") +
                                 (float)$this->db->val("SELECT COALESCE(SUM(amount),0) FROM subscriptions WHERE status='active' AND billing_cycle='yearly'"),
            'revenue_total'   => (float)$this->db->val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed'"),
            'revenue_month'   => (float)$this->db->val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed' AND DATE_FORMAT(paid_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')"),
            'revenue_year'    => (float)$this->db->val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed' AND YEAR(paid_at) = YEAR(NOW())"),
            'avg_arpu'        => (float)$this->db->val("SELECT COALESCE(AVG(amount),0) FROM subscriptions WHERE status='active' AND amount > 0"),
            'invoice_pending' => (float)$this->db->val("SELECT COALESCE(SUM(total - amount_paid),0) FROM invoices WHERE status IN ('pending','partial','overdue')"),
        ];

        $tenantsByMonth = $this->db->all(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS m, COUNT(*) AS c
             FROM tenants WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY m ORDER BY m ASC"
        );
        $revenueByMonth = $this->db->all(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') AS m, COALESCE(SUM(amount),0) AS c
             FROM payments WHERE status='completed' AND paid_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY m ORDER BY m ASC"
        );
        $usersByMonth = $this->db->all(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS m, COUNT(*) AS c
             FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY m ORDER BY m ASC"
        );
        $ticketsByMonth = $this->db->all(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS m, COUNT(*) AS c
             FROM tickets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY m ORDER BY m ASC"
        );

        $byPlan = $this->db->all(
            "SELECT p.name AS plan, p.color, COUNT(s.id) AS tenants, COALESCE(SUM(s.amount),0) AS mrr
             FROM plans p LEFT JOIN subscriptions s ON s.plan_id = p.id AND s.status='active'
             GROUP BY p.id ORDER BY p.sort_order ASC"
        );

        $topTenants = $this->db->all(
            "SELECT t.name, t.slug, COALESCE(SUM(p.amount),0) AS revenue,
                    (SELECT COUNT(*) FROM users u WHERE u.tenant_id = t.id) AS users,
                    (SELECT COUNT(*) FROM tickets ti WHERE ti.tenant_id = t.id) AS tickets
             FROM tenants t LEFT JOIN payments p ON p.tenant_id = t.id AND p.status='completed'
             GROUP BY t.id ORDER BY revenue DESC LIMIT 10"
        );

        $this->render('admin/reports/index', [
            'title' => 'Reportes SaaS',
            'pageHeading' => 'Reportes del SaaS',
            'kpis' => $kpis,
            'tenantsByMonth' => $tenantsByMonth,
            'revenueByMonth' => $revenueByMonth,
            'usersByMonth' => $usersByMonth,
            'ticketsByMonth' => $ticketsByMonth,
            'byPlan' => $byPlan,
            'topTenants' => $topTenants,
        ]);
    }

    public function audit(): void
    {
        $this->requireCan('reports.view');
        $logs = $this->db->all(
            "SELECT l.*, sa.name AS admin_name, sa.email AS admin_email
             FROM super_audit_logs l LEFT JOIN super_admins sa ON sa.id = l.super_admin_id
             ORDER BY l.created_at DESC LIMIT 200"
        );
        $this->render('admin/reports/audit', [
            'title' => 'Auditoría',
            'pageHeading' => 'Auditoría de Super Admin',
            'logs' => $logs,
        ]);
    }
}
