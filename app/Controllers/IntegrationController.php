<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\IntegrationRegistry;
use App\Core\IntegrationDispatcher;
use App\Core\Plan;

class IntegrationController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('integrations');
        $this->requireCan('integrations.view');

        $allowed = $this->allowedProviders($tenant);
        $providers = IntegrationRegistry::all();
        $marketplace = [];
        foreach ($providers as $slug => $def) {
            if (!in_array($slug, $allowed, true)) continue;
            $marketplace[$slug] = $def;
        }

        $installed = $this->db->all(
            'SELECT * FROM integrations WHERE tenant_id=? ORDER BY is_active DESC, id DESC',
            [$tenant->id]
        );

        $stats = [
            'installed' => count($installed),
            'active'    => count(array_filter($installed, fn($i) => (int)$i['is_active'] === 1)),
            'success'   => array_sum(array_map(fn($i) => (int)$i['success_count'], $installed)),
            'errors'    => array_sum(array_map(fn($i) => (int)$i['error_count'], $installed)),
        ];

        $maxAllowed = $this->maxAllowed($tenant);

        $this->render('integrations/index', [
            'title' => 'Integraciones',
            'marketplace' => $marketplace,
            'installed' => $installed,
            'allProviders' => $providers,
            'categories' => IntegrationRegistry::categories(),
            'stats' => $stats,
            'maxAllowed' => $maxAllowed,
            'planLabel' => Plan::label($tenant),
        ]);
    }

    public function configure(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('integrations');
        $this->requireCan('integrations.install');

        $providerSlug = (string)$params['provider'];
        $providerDef = IntegrationRegistry::get($providerSlug);
        if (!$providerDef) {
            $this->session->flash('error', 'Proveedor no encontrado.');
            $this->redirect('/t/' . $tenant->slug . '/integrations');
        }

        $allowed = $this->allowedProviders($tenant);
        if (!in_array($providerSlug, $allowed, true)) {
            $this->session->flash('error', 'Este proveedor no está disponible en tu plan.');
            $this->redirect('/t/' . $tenant->slug . '/integrations');
        }

        $integrationId = (int)($params['id'] ?? 0);
        $integration = null;
        if ($integrationId) {
            $integration = $this->db->one('SELECT * FROM integrations WHERE id=? AND tenant_id=?', [$integrationId, $tenant->id]);
            if (!$integration) {
                $this->session->flash('error', 'Integración no encontrada.');
                $this->redirect('/t/' . $tenant->slug . '/integrations');
            }
        }

        $logs = [];
        if ($integration) {
            $logs = $this->db->all(
                'SELECT * FROM integration_logs WHERE integration_id=? ORDER BY id DESC LIMIT 30',
                [$integration['id']]
            );
        }

        // Info extra para Telegram: resolver bot info + verificar chat_id si ya hay datos guardados
        $telegram = null;
        if ($providerSlug === 'telegram' && $integration) {
            $cfg = json_decode((string)$integration['config'], true) ?: [];
            $token = (string)($cfg['bot_token'] ?? '');
            $chatId = (string)($cfg['chat_id'] ?? '');
            if ($token !== '') {
                $bot = IntegrationDispatcher::telegramGetMe($token);
                $telegram = ['bot' => $bot, 'chat' => null, 'chat_error' => null];
                if ($chatId !== '') {
                    $check = IntegrationDispatcher::telegramGetChat($token, $chatId);
                    $telegram['chat'] = $check['chat'];
                    $telegram['chat_error'] = $check['ok'] ? null : $check['error'];
                }
            }
        }

        $this->render('integrations/configure', [
            'title' => $providerDef['name'] . ' · Configurar',
            'provider' => $providerDef,
            'integration' => $integration,
            'logs' => $logs,
            'availableEvents' => IntegrationRegistry::availableEvents(),
            'telegram' => $telegram,
        ]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('integrations');
        $this->requireCan('integrations.install');
        $this->validateCsrf();

        $providerSlug = (string)$params['provider'];
        $providerDef = IntegrationRegistry::get($providerSlug);
        if (!$providerDef) { $this->back(); return; }

        $allowed = $this->allowedProviders($tenant);
        if (!in_array($providerSlug, $allowed, true)) {
            $this->session->flash('error', 'Este proveedor no está disponible en tu plan.');
            $this->redirect('/t/' . $tenant->slug . '/integrations');
        }

        $current = (int)$this->db->val('SELECT COUNT(*) FROM integrations WHERE tenant_id=?', [$tenant->id]);
        $max = $this->maxAllowed($tenant);
        if ($max > 0 && $current >= $max) {
            $this->session->flash('error', "Has alcanzado el límite de tu plan ($max integraciones). Haz upgrade o desinstala alguna.");
            $this->redirect('/t/' . $tenant->slug . '/integrations');
        }

        [$config, $err] = $this->collectConfig($providerDef);
        if ($err) {
            $this->session->flash('error', $err);
            $this->redirect('/t/' . $tenant->slug . '/integrations/' . $providerSlug);
        }

        $events = $this->collectEvents();
        $name = trim((string)$this->input('name', '')) ?: $providerDef['name'];

        $id = $this->db->insert('integrations', [
            'tenant_id' => $tenant->id,
            'provider'  => $providerSlug,
            'name'      => $name,
            'config'    => json_encode($config, JSON_UNESCAPED_UNICODE),
            'events'    => json_encode($events, JSON_UNESCAPED_UNICODE),
            'is_active' => (int)($this->input('is_active', 1) ? 1 : 0),
            'created_by' => $this->auth->userId(),
        ]);
        $this->logAudit('integration.installed', 'integration', $id, ['provider' => $providerSlug, 'name' => $name]);
        $this->session->flash('success', 'Integración con ' . $providerDef['name'] . ' configurada.');
        $this->redirect('/t/' . $tenant->slug . '/integrations/' . $providerSlug . '/' . $id);
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('integrations');
        $this->requireCan('integrations.edit');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $integration = $this->db->one('SELECT * FROM integrations WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$integration) { $this->back(); return; }

        $providerDef = IntegrationRegistry::get((string)$integration['provider']);
        if (!$providerDef) { $this->back(); return; }

        [$config, $err] = $this->collectConfig($providerDef, $integration);
        if ($err) {
            $this->session->flash('error', $err);
            $this->redirect('/t/' . $tenant->slug . '/integrations/' . $integration['provider'] . '/' . $id);
        }

        $events = $this->collectEvents();
        $name = trim((string)$this->input('name', '')) ?: $providerDef['name'];

        $this->db->update('integrations', [
            'name'      => $name,
            'config'    => json_encode($config, JSON_UNESCAPED_UNICODE),
            'events'    => json_encode($events, JSON_UNESCAPED_UNICODE),
            'is_active' => (int)($this->input('is_active', 0) ? 1 : 0),
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);

        $this->logAudit('integration.updated', 'integration', $id, ['provider' => $integration['provider']]);
        $this->session->flash('success', 'Integración actualizada.');
        $this->redirect('/t/' . $tenant->slug . '/integrations/' . $integration['provider'] . '/' . $id);
    }

    public function toggle(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('integrations');
        $this->requireCan('integrations.edit');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $i = $this->db->one('SELECT id, is_active, provider FROM integrations WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$i) { $this->back(); return; }
        $newState = (int)$i['is_active'] ? 0 : 1;
        $this->db->update('integrations', ['is_active' => $newState], 'id=?', [$id]);
        $this->logAudit('integration.toggled', 'integration', $id, ['active' => $newState]);
        $this->session->flash('success', $newState ? 'Integración activada.' : 'Integración desactivada.');
        $this->redirect('/t/' . $tenant->slug . '/integrations/' . $i['provider'] . '/' . $id);
    }

    public function test(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('integrations');
        $this->requireCan('integrations.test');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $integration = $this->db->one('SELECT * FROM integrations WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$integration) {
            $this->session->flash('error', 'Integración no encontrada.');
            $this->redirect('/t/' . $tenant->slug . '/integrations');
        }
        $providerDef = IntegrationRegistry::get((string)$integration['provider']);
        $config = json_decode((string)$integration['config'], true) ?: [];

        $result = IntegrationDispatcher::testSend($providerDef, $config, (int)$integration['id'], $tenant->id);
        if ($result['ok']) {
            $this->session->flash('success', 'Prueba enviada correctamente · ' . ($result['latency_ms'] ?? '?') . 'ms · HTTP ' . $result['status_code']);
        } else {
            $errMsg = (string)($result['error'] ?? 'desconocido');
            // Pista accionable según el provider y el error
            $hint = $this->errorHint((string)$integration['provider'], $errMsg, (int)($result['status_code'] ?? 0));
            $this->session->flash('error', 'Error en la prueba: ' . $errMsg . ($hint ? ' · ' . $hint : ''));
        }
        $this->redirect('/t/' . $tenant->slug . '/integrations/' . $integration['provider'] . '/' . $id);
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('integrations');
        $this->requireCan('integrations.delete');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $i = $this->db->one('SELECT id, provider FROM integrations WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$i) { $this->back(); return; }
        $this->db->delete('integration_logs', 'integration_id=?', [$id]);
        $this->db->delete('integrations', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->logAudit('integration.deleted', 'integration', $id, ['provider' => $i['provider']]);
        $this->session->flash('success', 'Integración eliminada.');
        $this->redirect('/t/' . $tenant->slug . '/integrations');
    }

    /* ─────────────── Helpers ─────────────── */

    /**
     * Pista accionable cuando un test falla, según provider + status + texto.
     */
    protected function errorHint(string $provider, string $err, int $status): string
    {
        $lower = strtolower($err);
        switch ($provider) {
            case 'telegram':
                if (str_contains($lower, 'chat not found')) {
                    return 'Verificá el Chat ID. Para chats privados, el usuario debe enviar /start al bot primero. Para grupos usá el ID negativo (ej: -1001234567890). Para canales usá @username.';
                }
                if (str_contains($lower, 'unauthorized') || str_contains($lower, 'token')) {
                    return 'Token del bot inválido. Generá uno nuevo con @BotFather.';
                }
                if (str_contains($lower, 'forbidden') || str_contains($lower, 'bot was kicked') || str_contains($lower, 'blocked')) {
                    return 'El bot no tiene permiso en ese chat. Agregalo al grupo/canal o pedí al usuario que lo desbloquee.';
                }
                if (str_contains($lower, "can't parse entities") || str_contains($lower, 'parse')) {
                    return 'El mensaje contiene HTML inválido para Telegram.';
                }
                break;
            case 'slack':
            case 'mattermost':
            case 'rocketchat':
                if ($status === 404 || str_contains($lower, 'no_service') || str_contains($lower, 'invalid_token')) {
                    return 'Webhook URL inválido o expirado. Generá uno nuevo en Slack > Apps > Incoming Webhooks.';
                }
                if (str_contains($lower, 'channel_not_found')) {
                    return 'El canal configurado no existe o el bot no fue invitado.';
                }
                break;
            case 'discord':
                if ($status === 404) return 'Webhook URL no existe (revisá si fue eliminado en Discord).';
                if ($status === 401) return 'Webhook URL inválido.';
                break;
            case 'teams':
                if ($status === 410 || $status === 404) return 'El connector de Teams expiró o fue eliminado. Recreálo.';
                break;
            case 'pushover':
                if (str_contains($lower, 'invalid')) return 'User key o token de aplicación inválidos.';
                break;
            case 'webhook':
            case 'zapier':
            case 'n8n':
            case 'make':
                if ($status === 0) return 'No se pudo alcanzar la URL (verificá DNS/firewall).';
                if ($status === 401 || $status === 403) return 'El destino rechazó la autenticación. Revisá secret/auth_header.';
                break;
        }
        if ($status === 0 && (str_contains($lower, 'timeout') || str_contains($lower, 'resolve'))) {
            return 'No se pudo conectar al destino.';
        }
        return '';
    }

    /**
     * Recopila los valores del form según el config schema del provider.
     * Si la integración existía y un campo password está vacío, mantiene el anterior.
     */
    protected function collectConfig(array $providerDef, ?array $existing = null): array
    {
        $existingConfig = $existing ? (json_decode((string)$existing['config'], true) ?: []) : [];
        $out = [];
        foreach ($providerDef['config'] as $field) {
            $key = $field['key'];
            $type = $field['type'] ?? 'text';
            $val = $this->input($key, null);
            if (($val === null || $val === '') && $type === 'password' && isset($existingConfig[$key])) {
                $out[$key] = $existingConfig[$key];
                continue;
            }
            $val = is_string($val) ? trim($val) : $val;
            if (!empty($field['required']) && ($val === null || $val === '')) {
                return [[], 'El campo "' . ($field['label'] ?? $key) . '" es obligatorio.'];
            }
            if ($val === null || $val === '') {
                if (isset($field['default'])) $val = $field['default'];
                else { $out[$key] = ''; continue; }
            }
            if ($type === 'url' && !filter_var($val, FILTER_VALIDATE_URL)) {
                return [[], 'La URL del campo "' . ($field['label'] ?? $key) . '" no es válida.'];
            }
            if ($type === 'email' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                return [[], 'El email "' . ($field['label'] ?? $key) . '" no es válido.'];
            }
            $out[$key] = (string)$val;
        }
        return [$out, null];
    }

    protected function collectEvents(): array
    {
        $events = $_POST['events'] ?? [];
        if (!is_array($events)) return [];
        $valid = array_keys(IntegrationRegistry::availableEvents());
        $out = [];
        foreach ($events as $e) {
            if (in_array($e, $valid, true)) $out[] = $e;
        }
        return $out;
    }

    /**
     * Lista de proveedores permitidos para el plan del tenant según saas_settings.
     * Vacío en setting = todos.
     */
    protected function allowedProviders(\App\Core\Tenant $tenant): array
    {
        $plan = Plan::tenantPlan($tenant);
        $key = 'integrations_providers_' . $plan;
        $val = (string)$this->db->val('SELECT `value` FROM saas_settings WHERE `key`=?', [$key]);
        $allProviders = array_keys(IntegrationRegistry::all());
        if ($val === '') return $allProviders;
        return array_values(array_intersect($allProviders, array_map('trim', explode(',', $val))));
    }

    /**
     * Cantidad máxima de integraciones permitidas para el plan del tenant.
     */
    protected function maxAllowed(\App\Core\Tenant $tenant): int
    {
        $plan = Plan::tenantPlan($tenant);
        $key = 'integrations_max_' . $plan;
        $val = $this->db->val('SELECT `value` FROM saas_settings WHERE `key`=?', [$key]);
        return (int)($val ?? 0);
    }

    protected function logAudit(string $action, string $entity, int $entityId, array $meta = []): void
    {
        $tenant = $this->app->tenant;
        if (!$tenant) return;
        try {
            $this->db->insert('audit_logs', [
                'tenant_id' => $tenant->id,
                'user_id'   => $this->auth->userId(),
                'action'    => $action,
                'entity'    => $entity,
                'entity_id' => $entityId,
                'meta'      => json_encode($meta, JSON_UNESCAPED_UNICODE),
                'ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua'        => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (\Throwable $_e) {}
    }
}
