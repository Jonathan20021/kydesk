<?php
namespace App\Controllers\Developer;

class DashboardController extends DeveloperController
{
    public function index(): void
    {
        $this->requireDeveloper();
        $devId = $this->devAuth->id();

        $apps = $this->db->all('SELECT * FROM dev_apps WHERE developer_id=? ORDER BY id DESC LIMIT 6', [$devId]);
        $tokens = $this->db->all('SELECT * FROM dev_api_tokens WHERE developer_id=? AND revoked_at IS NULL ORDER BY id DESC LIMIT 6', [$devId]);

        $monthRequests = (int)$this->db->val(
            "SELECT IFNULL(SUM(requests),0) FROM dev_api_usage WHERE developer_id=? AND period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')",
            [$devId]
        );
        $monthErrors = (int)$this->db->val(
            "SELECT IFNULL(SUM(errors),0) FROM dev_api_usage WHERE developer_id=? AND period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')",
            [$devId]
        );

        $usageDaily = $this->db->all(
            "SELECT period_date, SUM(requests) AS requests, SUM(errors) AS errors
             FROM dev_api_usage
             WHERE developer_id=? AND period_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY period_date ORDER BY period_date ASC",
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

        $this->render('developers/dashboard/index', [
            'title' => 'Panel Developer',
            'pageHeading' => 'Tu panel',
            'apps' => $apps,
            'tokens' => $tokens,
            'monthRequests' => $monthRequests,
            'monthErrors' => $monthErrors,
            'usageDaily' => $usageDaily,
            'unpaidInvoices' => $unpaidInvoices,
            'invoices' => $invoices,
        ]);
    }
}
