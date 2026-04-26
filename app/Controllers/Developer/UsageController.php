<?php
namespace App\Controllers\Developer;

class UsageController extends DeveloperController
{
    public function index(): void
    {
        $this->requireDeveloper();
        $devId = $this->devAuth->id();

        $monthRequests = (int)$this->db->val(
            "SELECT IFNULL(SUM(requests),0) FROM dev_api_usage WHERE developer_id=? AND period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')",
            [$devId]
        );
        $monthErrors = (int)$this->db->val(
            "SELECT IFNULL(SUM(errors),0) FROM dev_api_usage WHERE developer_id=? AND period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')",
            [$devId]
        );
        $totalRequests = (int)$this->db->val(
            "SELECT IFNULL(SUM(requests),0) FROM dev_api_usage WHERE developer_id=?",
            [$devId]
        );

        $daily = $this->db->all(
            "SELECT period_date, SUM(requests) AS requests, SUM(errors) AS errors
             FROM dev_api_usage WHERE developer_id=? AND period_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
             GROUP BY period_date ORDER BY period_date ASC",
            [$devId]
        );

        $byApp = $this->db->all(
            "SELECT a.id, a.name, a.environment,
                    IFNULL((SELECT SUM(u.requests) FROM dev_api_usage u WHERE u.app_id=a.id AND u.period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')),0) AS month_requests,
                    IFNULL((SELECT SUM(u.requests) FROM dev_api_usage u WHERE u.app_id=a.id),0) AS total_requests,
                    IFNULL((SELECT SUM(u.errors) FROM dev_api_usage u WHERE u.app_id=a.id AND u.period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')),0) AS month_errors
             FROM dev_apps a WHERE a.developer_id=? ORDER BY month_requests DESC",
            [$devId]
        );

        $plan = $this->devAuth->plan();
        $quota = (int)($plan['max_requests_month'] ?? 0);
        $pct = $quota > 0 ? min(100, round($monthRequests / $quota * 100)) : 0;

        $this->render('developers/usage/index', [
            'title' => 'Uso de API',
            'pageHeading' => 'Uso y métricas',
            'monthRequests' => $monthRequests,
            'monthErrors' => $monthErrors,
            'totalRequests' => $totalRequests,
            'daily' => $daily,
            'byApp' => $byApp,
            'quota' => $quota,
            'pct' => $pct,
        ]);
    }
}
