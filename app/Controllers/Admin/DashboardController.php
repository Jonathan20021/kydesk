<?php
namespace App\Controllers\Admin;

class DashboardController extends AdminController
{
    public function index(): void
    {
        $this->requireSuperAuth();

        // KPIs
        $stats = [
            'total_tenants'    => (int)$this->db->val('SELECT COUNT(*) FROM tenants'),
            'active_tenants'   => (int)$this->db->val('SELECT COUNT(*) FROM tenants WHERE is_active = 1'),
            'demo_tenants'     => (int)$this->db->val('SELECT COUNT(*) FROM tenants WHERE is_demo = 1'),
            'total_users'      => (int)$this->db->val('SELECT COUNT(*) FROM users'),
            'total_tickets'    => (int)$this->db->val('SELECT COUNT(*) FROM tickets'),
            'open_tickets'     => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE status IN ('open','in_progress')"),
            'mrr'              => (float)$this->db->val("SELECT COALESCE(SUM(amount),0) FROM subscriptions WHERE status='active' AND billing_cycle='monthly'"),
            'arr'              => (float)$this->db->val("SELECT COALESCE(SUM(amount*12),0) FROM subscriptions WHERE status='active' AND billing_cycle='monthly'") +
                                  (float)$this->db->val("SELECT COALESCE(SUM(amount),0) FROM subscriptions WHERE status='active' AND billing_cycle='yearly'"),
            'total_revenue'    => (float)$this->db->val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed'"),
            'pending_invoices' => (int)$this->db->val("SELECT COUNT(*) FROM invoices WHERE status IN ('pending','overdue')"),
            'pending_amount'   => (float)$this->db->val("SELECT COALESCE(SUM(total - amount_paid),0) FROM invoices WHERE status IN ('pending','overdue')"),
            'open_support'     => (int)$this->db->val("SELECT COUNT(*) FROM saas_support_tickets WHERE status IN ('open','in_progress')"),
        ];

        // Recent tenants
        $recentTenants = $this->db->all(
            "SELECT t.id, t.name, t.slug, t.plan, t.is_active, t.is_demo, t.created_at,
                    (SELECT COUNT(*) FROM users u WHERE u.tenant_id = t.id) AS users_count,
                    (SELECT COUNT(*) FROM tickets ti WHERE ti.tenant_id = t.id) AS tickets_count
             FROM tenants t
             ORDER BY t.created_at DESC LIMIT 8"
        );

        // Recent invoices
        $recentInvoices = $this->db->all(
            "SELECT i.*, t.name AS tenant_name, t.slug AS tenant_slug
             FROM invoices i LEFT JOIN tenants t ON t.id = i.tenant_id
             ORDER BY i.created_at DESC LIMIT 6"
        );

        // Plan distribution
        $planDistribution = $this->db->all(
            "SELECT plan, COUNT(*) as count FROM tenants WHERE is_active=1 GROUP BY plan ORDER BY count DESC"
        );

        // Tenants by month (last 6 months)
        $tenantsByMonth = $this->db->all(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
             FROM tenants WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY month ORDER BY month ASC"
        );

        // Revenue by month (last 6)
        $revenueByMonth = $this->db->all(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') as month, COALESCE(SUM(amount),0) as total
             FROM payments WHERE status='completed' AND paid_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY month ORDER BY month ASC"
        );

        // ============ DEVELOPER PORTAL stats ============
        $devStats = [];
        try {
            $devStats = [
                'developers_total' => (int)$this->db->val("SELECT COUNT(*) FROM developers"),
                'developers_active' => (int)$this->db->val("SELECT COUNT(*) FROM developers WHERE is_active=1 AND suspended_at IS NULL"),
                'apps_total' => (int)$this->db->val("SELECT COUNT(*) FROM dev_apps"),
                'apps_active' => (int)$this->db->val("SELECT COUNT(*) FROM dev_apps WHERE status='active'"),
                'tokens_active' => (int)$this->db->val("SELECT COUNT(*) FROM dev_api_tokens WHERE revoked_at IS NULL"),
                'subs_active' => (int)$this->db->val("SELECT COUNT(*) FROM dev_subscriptions WHERE status IN ('active','trial')"),
                'mtd_requests' => (int)$this->db->val("SELECT IFNULL(SUM(requests),0) FROM dev_api_usage WHERE period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')"),
                'mtd_errors' => (int)$this->db->val("SELECT IFNULL(SUM(errors),0) FROM dev_api_usage WHERE period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')"),
                'mtd_revenue' => (float)$this->db->val("SELECT IFNULL(SUM(amount),0) FROM dev_payments WHERE status='completed' AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')"),
                'dev_mrr' => (float)$this->db->val("SELECT COALESCE(SUM(amount),0) FROM dev_subscriptions WHERE status IN ('active','trial') AND billing_cycle='monthly'"),
                'unpaid_invoices' => (int)$this->db->val("SELECT COUNT(*) FROM dev_invoices WHERE status IN ('pending','overdue','partial')"),
            ];
        } catch (\Throwable $e) { $devStats = []; }

        $devUsageByDay = [];
        try {
            $devUsageByDay = $this->db->all(
                "SELECT period_date, IFNULL(SUM(requests),0) AS requests
                 FROM dev_api_usage
                 WHERE period_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY period_date ORDER BY period_date ASC"
            );
        } catch (\Throwable $e) {}

        $topDevelopers = [];
        try {
            $topDevelopers = $this->db->all(
                "SELECT d.id, d.name, d.email, d.company,
                        IFNULL((SELECT SUM(u.requests) FROM dev_api_usage u WHERE u.developer_id=d.id AND u.period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')),0) AS month_requests,
                        (SELECT p.name FROM dev_subscriptions s JOIN dev_plans p ON p.id=s.plan_id WHERE s.developer_id=d.id AND s.status IN ('active','trial') ORDER BY s.id DESC LIMIT 1) AS plan_name
                 FROM developers d
                 ORDER BY month_requests DESC LIMIT 5"
            );
        } catch (\Throwable $e) {}

        $this->render('admin/dashboard/index', [
            'title' => 'Dashboard',
            'pageHeading' => 'Panel de Control',
            'stats' => $stats,
            'recentTenants' => $recentTenants,
            'recentInvoices' => $recentInvoices,
            'planDistribution' => $planDistribution,
            'tenantsByMonth' => $tenantsByMonth,
            'revenueByMonth' => $revenueByMonth,
            'devStats' => $devStats,
            'devUsageByDay' => $devUsageByDay,
            'topDevelopers' => $topDevelopers,
        ]);
    }
}
