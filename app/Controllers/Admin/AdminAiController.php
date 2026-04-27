<?php
namespace App\Controllers\Admin;

use App\Core\License;
use App\Core\Plan;
use App\Core\Tenant as TenantModel;

class AdminAiController extends AdminController
{
    public function index(): void
    {
        $this->requireCan('settings.view');
        $cfg = $this->loadGlobalSettings();

        // Stats
        $totalCompletions = (int)$this->db->val('SELECT COUNT(*) FROM ai_completions WHERE created_at >= DATE_FORMAT(NOW(), "%Y-%m-01")');
        $totalTokens = (int)$this->db->val('SELECT IFNULL(SUM(tokens_in + tokens_out), 0) FROM ai_completions WHERE created_at >= DATE_FORMAT(NOW(), "%Y-%m-01")');
        $tenantCount = (int)$this->db->val("SELECT COUNT(*) FROM ai_settings WHERE is_assigned = 1");

        // List of tenants with their AI status (joined with subscription/plan to know if Enterprise)
        $rows = $this->db->all(
            "SELECT t.id, t.name, t.slug, t.is_demo, t.is_active, t.suspended_at,
                    s.status AS sub_status, p.slug AS plan_slug, p.name AS plan_name,
                    a.is_assigned, a.is_enabled, a.monthly_quota, a.used_this_month,
                    a.suggest_replies, a.auto_summarize, a.auto_categorize, a.detect_sentiment, a.auto_translate,
                    a.assigned_at, a.assigned_by_admin, a.unassigned_at,
                    sa.name AS admin_name,
                    (SELECT COUNT(*) FROM ai_completions WHERE tenant_id = t.id AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')) AS completions_this_month
             FROM tenants t
             LEFT JOIN subscriptions s ON s.id = (SELECT MAX(id) FROM subscriptions WHERE tenant_id = t.id)
             LEFT JOIN plans p ON p.id = s.plan_id
             LEFT JOIN ai_settings a ON a.tenant_id = t.id
             LEFT JOIN super_admins sa ON sa.id = a.assigned_by_admin
             WHERE IFNULL(t.is_developer_sandbox, 0) = 0
             ORDER BY a.is_assigned DESC, t.name ASC"
        );

        // Recent completions (cross-tenant)
        $recentLogs = $this->db->all(
            "SELECT c.*, t.name AS tenant_name, t.slug AS tenant_slug, tk.code AS ticket_code, u.name AS user_name
             FROM ai_completions c
             LEFT JOIN tenants t ON t.id = c.tenant_id
             LEFT JOIN tickets tk ON tk.id = c.ticket_id
             LEFT JOIN users u ON u.id = c.user_id
             ORDER BY c.created_at DESC LIMIT 30"
        );

        $this->render('admin/ai/index', [
            'title' => 'IA Asistente',
            'pageHeading' => 'IA Asistente · Configuración global',
            'cfg' => $cfg,
            'tenants' => $rows,
            'stats' => [
                'completions' => $totalCompletions,
                'tokens' => $totalTokens,
                'assigned_tenants' => $tenantCount,
            ],
            'recentLogs' => $recentLogs,
        ]);
    }

    public function settingsUpdate(): void
    {
        $this->requireCan('settings.edit');
        $this->validateCsrf();

        $newKey = trim((string)$this->input('ai_api_key', ''));
        $cfg = [
            'ai_provider'        => in_array($this->input('ai_provider'), ['anthropic','openai','disabled'], true) ? (string)$this->input('ai_provider') : 'anthropic',
            'ai_default_model'   => trim((string)$this->input('ai_default_model','claude-haiku-4-5')) ?: 'claude-haiku-4-5',
            'ai_default_quota'   => (string)max(0, (int)$this->input('ai_default_quota', 1000)),
            'ai_global_enabled'  => $this->input('ai_global_enabled') ? '1' : '0',
        ];
        if ($newKey !== '') $cfg['ai_api_key'] = $newKey;

        foreach ($cfg as $k => $v) $this->setSetting($k, $v);
        $this->superAuth->log('ai.settings_updated', 'saas', 0);
        $this->session->flash('success', 'Configuración global de IA guardada.');
        $this->redirect('/admin/ai');
    }

    public function assign(array $params): void
    {
        $this->requireCan('settings.edit');
        $this->validateCsrf();
        $tenantId = (int)$params['id'];
        $tenant = $this->db->one('SELECT * FROM tenants WHERE id=?', [$tenantId]);
        if (!$tenant) { $this->redirect('/admin/ai'); }

        // Validar que esté en plan Enterprise (o forzar override)
        $tenantModel = new TenantModel($tenant);
        $planSlug = Plan::tenantPlan($tenantModel);
        $force = (bool)$this->input('force', 0);
        if ($planSlug !== 'enterprise' && !$force) {
            $this->session->flash('error', "Tenant '{$tenant['name']}' no está en plan Enterprise. Activá su plan o usá la asignación forzada.");
            $this->redirect('/admin/ai');
        }

        $cfg = $this->loadGlobalSettings();
        $defaultQuota = (int)$cfg['ai_default_quota'];
        $defaultModel = $cfg['ai_default_model'];
        $existing = $this->db->one('SELECT * FROM ai_settings WHERE tenant_id = ?', [$tenantId]);

        $payload = [
            'is_assigned'       => 1,
            'is_enabled'        => 1,
            'assigned_by_admin' => $this->superAuth->id(),
            'assigned_at'       => date('Y-m-d H:i:s'),
            'unassigned_at'     => null,
            'monthly_quota'     => max($defaultQuota, (int)$this->input('quota', $defaultQuota)),
            'model'             => $defaultModel,
            'provider'          => $cfg['ai_provider'],
        ];

        if ($existing) {
            $this->db->update('ai_settings', $payload, 'tenant_id = :tid', ['tid' => $tenantId]);
        } else {
            $this->db->insert('ai_settings', array_merge([
                'tenant_id' => $tenantId,
                'suggest_replies' => 1,
                'auto_summarize' => 1,
                'auto_categorize' => 0,
                'detect_sentiment' => 0,
                'auto_translate' => 0,
                'target_language' => 'es',
                'used_this_month' => 0,
            ], $payload));
        }

        $this->superAuth->log('ai.tenant_assigned', 'tenant', $tenantId, [
            'plan' => $planSlug,
            'forced' => $force ? 1 : 0,
        ]);
        $this->session->flash('success', "IA asignada a {$tenant['name']}.");
        $this->redirect('/admin/ai');
    }

    public function unassign(array $params): void
    {
        $this->requireCan('settings.edit');
        $this->validateCsrf();
        $tenantId = (int)$params['id'];
        $tenant = $this->db->one('SELECT * FROM tenants WHERE id=?', [$tenantId]);
        if (!$tenant) { $this->redirect('/admin/ai'); }

        $this->db->update('ai_settings', [
            'is_assigned'   => 0,
            'is_enabled'    => 0,
            'unassigned_at' => date('Y-m-d H:i:s'),
        ], 'tenant_id = :tid', ['tid' => $tenantId]);
        $this->superAuth->log('ai.tenant_unassigned', 'tenant', $tenantId);
        $this->session->flash('success', "IA desactivada para {$tenant['name']}.");
        $this->redirect('/admin/ai');
    }

    public function tenantUpdate(array $params): void
    {
        $this->requireCan('settings.edit');
        $this->validateCsrf();
        $tenantId = (int)$params['id'];
        $existing = $this->db->one('SELECT * FROM ai_settings WHERE tenant_id = ?', [$tenantId]);
        if (!$existing || !(int)$existing['is_assigned']) { $this->session->flash('error','No está asignado.'); $this->redirect('/admin/ai'); }

        $newQuota = max(0, (int)$this->input('monthly_quota', (int)$existing['monthly_quota']));
        $resetUsage = (bool)$this->input('reset_usage', 0);
        $data = [
            'monthly_quota' => $newQuota,
        ];
        if ($resetUsage) $data['used_this_month'] = 0;

        $this->db->update('ai_settings', $data, 'tenant_id = :tid', ['tid' => $tenantId]);
        $this->superAuth->log('ai.tenant_updated', 'tenant', $tenantId, $data);
        $this->session->flash('success', 'Cuota actualizada.');
        $this->redirect('/admin/ai');
    }

    /* ─────── helpers ─────── */

    protected function loadGlobalSettings(): array
    {
        $rows = $this->db->all("SELECT `key`,`value` FROM saas_settings WHERE `key` LIKE 'ai_%'");
        $map = [];
        foreach ($rows as $r) $map[$r['key']] = $r['value'];
        return [
            'ai_provider'        => $map['ai_provider'] ?? 'anthropic',
            'ai_api_key'         => $map['ai_api_key'] ?? '',
            'ai_default_model'   => $map['ai_default_model'] ?? 'claude-haiku-4-5',
            'ai_default_quota'   => $map['ai_default_quota'] ?? '1000',
            'ai_global_enabled'  => $map['ai_global_enabled'] ?? '1',
        ];
    }

    protected function setSetting(string $key, string $value): void
    {
        // saas_settings tiene `key` como PK (no `id`), así que usamos upsert atómico
        $this->db->run(
            "INSERT INTO saas_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = NOW()",
            [$key, $value]
        );
    }
}
