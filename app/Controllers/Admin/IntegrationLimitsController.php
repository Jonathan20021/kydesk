<?php
namespace App\Controllers\Admin;

use App\Core\IntegrationRegistry;

class IntegrationLimitsController extends AdminController
{
    public function index(): void
    {
        $this->requireSuperAuth();
        $plans = ['free', 'pro', 'business', 'enterprise'];
        $limits = [];
        $providersByPlan = [];
        foreach ($plans as $p) {
            $limits[$p] = (int)$this->db->val('SELECT `value` FROM saas_settings WHERE `key`=?', ['integrations_max_' . $p]);
            $val = (string)$this->db->val('SELECT `value` FROM saas_settings WHERE `key`=?', ['integrations_providers_' . $p]);
            $providersByPlan[$p] = $val === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $val))));
        }
        $allProviders = IntegrationRegistry::all();

        $usage = $this->db->all(
            "SELECT t.id, t.slug, t.name, t.plan, COUNT(i.id) AS count_total,
                    SUM(CASE WHEN i.is_active=1 THEN 1 ELSE 0 END) AS count_active
             FROM tenants t LEFT JOIN integrations i ON i.tenant_id = t.id
             WHERE COALESCE(t.is_developer_sandbox,0)=0
             GROUP BY t.id, t.slug, t.name, t.plan
             ORDER BY count_total DESC, t.id"
        );

        $totals = [
            'integrations' => (int)$this->db->val('SELECT COUNT(*) FROM integrations'),
            'active'       => (int)$this->db->val('SELECT COUNT(*) FROM integrations WHERE is_active=1'),
            'tenants'      => (int)$this->db->val('SELECT COUNT(DISTINCT tenant_id) FROM integrations'),
            'logs_24h'     => (int)$this->db->val('SELECT COUNT(*) FROM integration_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
        ];

        $this->render('admin/integration_limits/index', [
            'title' => 'Límites de Integraciones',
            'pageHeading' => 'Configuración de Integraciones',
            'plans' => $plans,
            'limits' => $limits,
            'providersByPlan' => $providersByPlan,
            'allProviders' => $allProviders,
            'usage' => $usage,
            'totals' => $totals,
        ]);
    }

    public function update(): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $plans = ['free', 'pro', 'business', 'enterprise'];
        $upsert = $this->db->pdo()->prepare("INSERT INTO saas_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
        foreach ($plans as $p) {
            $max = max(0, (int)$this->input('max_' . $p, 0));
            $upsert->execute(['integrations_max_' . $p, (string)$max]);
            $providers = $_POST['providers_' . $p] ?? [];
            if (!is_array($providers)) $providers = [];
            $allProviders = array_keys(IntegrationRegistry::all());
            $providers = array_values(array_intersect($allProviders, $providers));
            $val = $providers ? implode(',', $providers) : '';
            $upsert->execute(['integrations_providers_' . $p, $val]);
        }
        $this->superAuth->log('integrations.limits.updated', 'saas_settings', null, []);
        $this->session->flash('success', 'Límites de integraciones actualizados.');
        $this->redirect('/admin/integration-limits');
    }
}
