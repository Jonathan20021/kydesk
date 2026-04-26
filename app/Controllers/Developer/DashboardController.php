<?php
namespace App\Controllers\Developer;

class DashboardController extends DeveloperController
{
    public function index(): void
    {
        $this->requireDeveloper();
        $devId = $this->devAuth->id();

        $apps = $this->db->all(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM dev_api_tokens t WHERE t.app_id=a.id AND t.revoked_at IS NULL) AS active_tokens,
                    IFNULL((SELECT SUM(u.requests) FROM dev_api_usage u WHERE u.app_id=a.id AND u.period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')),0) AS month_requests
             FROM dev_apps a WHERE a.developer_id=? ORDER BY a.id DESC LIMIT 8",
            [$devId]
        );
        $tokens = $this->db->all('SELECT t.*, a.name AS app_name FROM dev_api_tokens t LEFT JOIN dev_apps a ON a.id=t.app_id WHERE t.developer_id=? AND t.revoked_at IS NULL ORDER BY t.id DESC LIMIT 6', [$devId]);

        $monthRequests = (int)$this->db->val(
            "SELECT IFNULL(SUM(requests),0) FROM dev_api_usage WHERE developer_id=? AND period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')",
            [$devId]
        );
        $monthErrors = (int)$this->db->val(
            "SELECT IFNULL(SUM(errors),0) FROM dev_api_usage WHERE developer_id=? AND period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')",
            [$devId]
        );
        $todayRequests = (int)$this->db->val(
            "SELECT IFNULL(SUM(requests),0) FROM dev_api_usage WHERE developer_id=? AND period_date=CURDATE()",
            [$devId]
        );
        $minuteRequests = (int)$this->db->val(
            "SELECT COUNT(*) FROM dev_api_request_log WHERE developer_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)",
            [$devId]
        );
        $avgLatency = (int)$this->db->val(
            "SELECT IFNULL(AVG(duration_ms),0) FROM dev_api_request_log WHERE developer_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            [$devId]
        );

        $usageDaily = $this->db->all(
            "SELECT period_date, SUM(requests) AS requests, SUM(errors) AS errors
             FROM dev_api_usage
             WHERE developer_id=? AND period_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY period_date ORDER BY period_date ASC",
            [$devId]
        );

        $recentActivity = $this->db->all(
            "SELECT method, path, status_code, duration_ms, created_at FROM dev_api_request_log WHERE developer_id=? ORDER BY id DESC LIMIT 8",
            [$devId]
        );

        $unpaidInvoices = (int)$this->db->val(
            "SELECT COUNT(*) FROM dev_invoices WHERE developer_id=? AND status IN ('pending','overdue','partial')",
            [$devId]
        );
        $invoices = $this->db->all(
            'SELECT * FROM dev_invoices WHERE developer_id=? ORDER BY created_at DESC LIMIT 5',
            [$devId]
        );

        $plan = $this->devAuth->plan();
        $quota = (int)($plan['max_requests_month'] ?? 0);
        $rateLimit = (int)($plan['rate_limit_per_min'] ?? 0);

        $this->render('developers/dashboard/index', [
            'title' => 'Dashboard',
            'pageHeading' => '¡Hola, ' . explode(' ', (string)$this->devAuth->developer()['name'])[0] . '!',
            'apps' => $apps,
            'tokens' => $tokens,
            'monthRequests' => $monthRequests,
            'monthErrors' => $monthErrors,
            'todayRequests' => $todayRequests,
            'minuteRequests' => $minuteRequests,
            'avgLatency' => $avgLatency,
            'usageDaily' => $usageDaily,
            'recentActivity' => $recentActivity,
            'unpaidInvoices' => $unpaidInvoices,
            'invoices' => $invoices,
            'quota' => $quota,
            'rateLimit' => $rateLimit,
        ]);
    }
}
