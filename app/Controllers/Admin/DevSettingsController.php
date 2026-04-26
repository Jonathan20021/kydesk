<?php
namespace App\Controllers\Admin;

class DevSettingsController extends AdminController
{
    public function index(): void
    {
        $this->requireSuperAuth();
        $rows = $this->db->all("SELECT `key`, `value` FROM saas_settings WHERE `key` LIKE 'dev_portal_%' ORDER BY `key`");
        $settings = [];
        foreach ($rows as $r) $settings[$r['key']] = $r['value'];

        $stats = [
            'developers_total'  => (int)$this->db->val("SELECT COUNT(*) FROM developers"),
            'developers_active' => (int)$this->db->val("SELECT COUNT(*) FROM developers WHERE is_active=1 AND suspended_at IS NULL"),
            'apps_total'        => (int)$this->db->val("SELECT COUNT(*) FROM dev_apps"),
            'tokens_active'     => (int)$this->db->val("SELECT COUNT(*) FROM dev_api_tokens WHERE revoked_at IS NULL"),
            'subs_active'       => (int)$this->db->val("SELECT COUNT(*) FROM dev_subscriptions WHERE status IN ('active','trial')"),
            'mtd_requests'      => (int)$this->db->val("SELECT IFNULL(SUM(requests),0) FROM dev_api_usage WHERE period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')"),
            'mtd_revenue'       => (float)$this->db->val("SELECT IFNULL(SUM(amount),0) FROM dev_payments WHERE status='completed' AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')"),
        ];

        $this->render('admin/dev_settings/index', [
            'title' => 'Ajustes Developer Portal',
            'pageHeading' => 'Ajustes del Developer Portal',
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    public function update(): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();

        $keys = [
            'dev_portal_enabled' => 'bool',
            'dev_portal_allow_registration' => 'bool',
            'dev_portal_require_verification' => 'bool',
            'dev_portal_enforce_quota' => 'bool',
            'dev_portal_enforce_rate_limit' => 'bool',
            'dev_portal_block_on_overage' => 'bool',
            'dev_portal_overage_enabled' => 'bool',
            'dev_portal_alert_at_pct' => 'int',
            'dev_portal_default_trial_days' => 'int',
            'dev_portal_name' => 'str',
            'dev_portal_company_label' => 'str',
            'dev_portal_tagline' => 'str',
            'dev_portal_support_email' => 'str',
            'dev_portal_default_plan' => 'str',
        ];

        foreach ($keys as $k => $type) {
            $val = $this->input($k);
            if ($type === 'bool') $val = $val ? '1' : '0';
            elseif ($type === 'int') $val = (string)(int)$val;
            else $val = trim((string)$val);
            $exists = $this->db->val('SELECT 1 FROM saas_settings WHERE `key`=?', [$k]);
            if ($exists) {
                $this->db->update('saas_settings', ['value' => $val], '`key`=?', [$k]);
            } else {
                $this->db->insert('saas_settings', ['key' => $k, 'value' => $val]);
            }
        }

        $this->superAuth->log('dev_portal.settings.update', 'saas_settings');
        $this->session->flash('success', 'Ajustes del Developer Portal actualizados.');
        $this->redirect('/admin/dev-settings');
    }
}
