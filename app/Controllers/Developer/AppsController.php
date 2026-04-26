<?php
namespace App\Controllers\Developer;

use App\Core\ApiAuth;
use App\Core\Helpers;

class AppsController extends DeveloperController
{
    public function index(): void
    {
        $this->requireDeveloper();
        $devId = $this->devAuth->id();
        $apps = $this->db->all(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM dev_api_tokens t WHERE t.app_id=a.id AND t.revoked_at IS NULL) AS active_tokens,
                    (SELECT IFNULL(SUM(requests),0) FROM dev_api_usage u WHERE u.app_id=a.id AND u.period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')) AS month_requests
             FROM dev_apps a WHERE a.developer_id=? ORDER BY a.id DESC",
            [$devId]
        );
        $plan = $this->devAuth->plan();
        $this->render('developers/apps/index', [
            'title' => 'Mis Apps',
            'pageHeading' => 'Aplicaciones',
            'apps' => $apps,
            'maxApps' => $plan['max_apps'] ?? 1,
            'usedApps' => count($apps),
        ]);
    }

    public function create(): void
    {
        $this->requireDeveloper();
        $plan = $this->devAuth->plan();
        if (!$plan) {
            $this->session->flash('error', 'No tienes una suscripción activa. Suscríbete a un plan para crear apps.');
            $this->redirect('/developers/billing/plans');
        }
        $count = (int)$this->db->val('SELECT COUNT(*) FROM dev_apps WHERE developer_id=?', [$this->devAuth->id()]);
        if ($count >= (int)$plan['max_apps']) {
            $this->session->flash('error', "Límite de apps alcanzado ({$plan['max_apps']}). Mejora tu plan para crear más.");
            $this->redirect('/developers/apps');
        }
        $this->render('developers/apps/edit', [
            'title' => 'Nueva app',
            'pageHeading' => 'Crear app',
            'devApp' => null,
        ]);
    }

    public function store(): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $devId = $this->devAuth->id();
        $plan = $this->devAuth->plan();
        if (!$plan) {
            $this->session->flash('error', 'No tienes una suscripción activa.');
            $this->redirect('/developers/billing/plans');
        }
        $count = (int)$this->db->val('SELECT COUNT(*) FROM dev_apps WHERE developer_id=?', [$devId]);
        if ($count >= (int)$plan['max_apps']) {
            $this->session->flash('error', 'Límite de apps alcanzado.');
            $this->redirect('/developers/apps');
        }
        $name = trim((string)$this->input('name', ''));
        if ($name === '') {
            $this->session->flash('error', 'Nombre es requerido.');
            $this->redirect('/developers/apps/create');
        }
        $slug = $this->uniqueSlug($name);

        // Provision a hidden tenant for this app (data isolation)
        $tenantSlug = $this->uniqueTenantSlug('dev-' . $slug);
        $tenantId = $this->db->insert('tenants', [
            'name' => $name,
            'slug' => $tenantSlug,
            'plan' => 'pro',
            'is_active' => 1,
            'is_developer_sandbox' => 1,
        ]);

        $id = $this->db->insert('dev_apps', [
            'developer_id' => $devId,
            'tenant_id' => $tenantId,
            'name' => $name,
            'slug' => $slug,
            'description' => (string)$this->input('description', ''),
            'homepage_url' => (string)$this->input('homepage_url', ''),
            'callback_url' => (string)$this->input('callback_url', ''),
            'environment' => in_array((string)$this->input('environment', 'development'), ['development','staging','production'], true) ? (string)$this->input('environment') : 'development',
            'status' => 'active',
        ]);
        $this->db->update('tenants', ['dev_app_id' => $id], 'id=?', [$tenantId]);

        $this->devAuth->log('app.create', 'app', $id, ['tenant_id' => $tenantId]);
        $this->session->flash('success', 'App creada correctamente con tenant aislado.');
        $this->redirect('/developers/apps/' . $id);
    }

    public function show(array $params): void
    {
        $this->requireDeveloper();
        $devId = $this->devAuth->id();
        $id = (int)$params['id'];
        $app = $this->db->one('SELECT * FROM dev_apps WHERE id=? AND developer_id=?', [$id, $devId]);
        if (!$app) $this->redirect('/developers/apps');

        $tokens = $this->db->all('SELECT * FROM dev_api_tokens WHERE app_id=? ORDER BY id DESC', [$id]);
        $usage = $this->db->all(
            "SELECT period_date, SUM(requests) AS requests, SUM(errors) AS errors
             FROM dev_api_usage WHERE app_id=? AND period_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY period_date ORDER BY period_date ASC",
            [$id]
        );
        $monthRequests = (int)$this->db->val(
            "SELECT IFNULL(SUM(requests),0) FROM dev_api_usage WHERE app_id=? AND period_date >= DATE_FORMAT(NOW(),'%Y-%m-01')",
            [$id]
        );
        $newToken = $this->session->get('new_dev_api_token');
        if ($newToken) $this->session->put('new_dev_api_token', null);

        $this->render('developers/apps/show', [
            'title' => $app['name'],
            'pageHeading' => $app['name'],
            'devApp' => $app,
            'tokens' => $tokens,
            'usage' => $usage,
            'monthRequests' => $monthRequests,
            'newToken' => $newToken,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $devId = $this->devAuth->id();
        $id = (int)$params['id'];
        $app = $this->db->one('SELECT * FROM dev_apps WHERE id=? AND developer_id=?', [$id, $devId]);
        if (!$app) $this->redirect('/developers/apps');

        $this->db->update('dev_apps', [
            'name' => trim((string)$this->input('name', $app['name'])),
            'description' => (string)$this->input('description', ''),
            'homepage_url' => (string)$this->input('homepage_url', ''),
            'callback_url' => (string)$this->input('callback_url', ''),
            'environment' => in_array((string)$this->input('environment', $app['environment']), ['development','staging','production'], true) ? (string)$this->input('environment') : $app['environment'],
        ], 'id=? AND developer_id=?', [$id, $devId]);

        $this->devAuth->log('app.update', 'app', $id);
        $this->session->flash('success', 'App actualizada.');
        $this->redirect('/developers/apps/' . $id);
    }

    public function delete(array $params): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $devId = $this->devAuth->id();
        $id = (int)$params['id'];
        $this->db->delete('dev_apps', 'id=? AND developer_id=?', [$id, $devId]);
        $this->devAuth->log('app.delete', 'app', $id);
        $this->session->flash('success', 'App eliminada.');
        $this->redirect('/developers/apps');
    }

    // ==================== Tokens (per app) ====================

    public function tokenCreate(array $params): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $devId = $this->devAuth->id();
        $appId = (int)$params['id'];
        $app = $this->db->one('SELECT * FROM dev_apps WHERE id=? AND developer_id=?', [$appId, $devId]);
        if (!$app) $this->redirect('/developers/apps');

        $plan = $this->devAuth->plan();
        $count = (int)$this->db->val('SELECT COUNT(*) FROM dev_api_tokens WHERE app_id=? AND revoked_at IS NULL', [$appId]);
        if ($plan && $count >= (int)$plan['max_tokens_per_app']) {
            $this->session->flash('error', "Límite de tokens alcanzado ({$plan['max_tokens_per_app']}). Revoca uno o mejora tu plan.");
            $this->redirect('/developers/apps/' . $appId);
        }

        $name = trim((string)$this->input('name', 'Token sin nombre'));
        if ($name === '') $name = 'Token sin nombre';
        $scopes = (string)$this->input('scopes', 'read,write');
        $allowed = ['read','write','*'];
        $scopesArr = array_filter(array_map('trim', explode(',', $scopes)), fn($s) => in_array($s, $allowed, true));
        if (!$scopesArr) $scopesArr = ['read'];

        $gen = ApiAuth::generate();
        $tokenId = $this->db->insert('dev_api_tokens', [
            'developer_id' => $devId,
            'app_id' => $appId,
            'name' => $name,
            'token_hash' => $gen['hash'],
            'token_preview' => $gen['preview'],
            'scopes' => implode(',', $scopesArr),
        ]);

        $this->session->put('new_dev_api_token', $gen['token']);
        $this->session->flash('success', 'Token creado. Cópialo ahora — no se mostrará de nuevo.');
        $this->devAuth->log('token.create', 'dev_api_token', $tokenId, ['app_id' => $appId]);
        $this->redirect('/developers/apps/' . $appId);
    }

    public function tokenRevoke(array $params): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $devId = $this->devAuth->id();
        $appId = (int)$params['id'];
        $tokenId = (int)$params['tokenId'];
        $this->db->update('dev_api_tokens', ['revoked_at' => date('Y-m-d H:i:s')], 'id=? AND app_id=? AND developer_id=?', [$tokenId, $appId, $devId]);
        $this->devAuth->log('token.revoke', 'dev_api_token', $tokenId);
        $this->session->flash('success', 'Token revocado.');
        $this->redirect('/developers/apps/' . $appId);
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Helpers::slug($name) ?: 'app';
        $slug = $base; $i = 1;
        while ($this->db->val('SELECT id FROM dev_apps WHERE slug=?', [$slug])) {
            $slug = $base . '-' . ++$i;
            if ($i > 100) { $slug = $base . '-' . substr(bin2hex(random_bytes(4)), 0, 6); break; }
        }
        return $slug;
    }

    protected function uniqueTenantSlug(string $base): string
    {
        $base = Helpers::slug($base) ?: 'devapp';
        $slug = $base; $i = 1;
        while ($this->db->val('SELECT id FROM tenants WHERE slug=?', [$slug])) {
            $slug = $base . '-' . ++$i;
            if ($i > 100) { $slug = $base . '-' . substr(bin2hex(random_bytes(4)), 0, 6); break; }
        }
        return $slug;
    }
}
