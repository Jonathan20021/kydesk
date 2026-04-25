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

        $this->render('admin/dashboard/index', [
            'title' => 'Dashboard',
            'pageHeading' => 'Panel de Control',
            'stats' => $stats,
            'recentTenants' => $recentTenants,
            'recentInvoices' => $recentInvoices,
            'planDistribution' => $planDistribution,
            'tenantsByMonth' => $tenantsByMonth,
            'revenueByMonth' => $revenueByMonth,
        ]);
    }
}
