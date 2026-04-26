<?php
namespace App\Controllers;

use App\Core\Controller;

class HelpController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->render('help/index', ['title' => 'Centro de ayuda']);
    }

    public function apiDocs(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);

        // Tokens de este tenant
        $tokens = $this->db->all(
            'SELECT id, name, token_preview, scopes, last_used_at, last_ip, expires_at, revoked_at, created_at
             FROM api_tokens WHERE tenant_id=? ORDER BY id DESC',
            [$tenant->id]
        );

        $newToken = $this->session->get('new_api_token');
        if ($newToken) $this->session->put('new_api_token', null);

        $this->render('help/api', [
            'title' => 'Documentación API',
            'tokens' => $tokens,
            'newToken' => $newToken,
        ]);
    }

    public function tokenCreate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireAuth();
        $this->validateCsrf();
        $u = $this->auth->user();

        $name = trim((string)$this->input('name', 'Token sin nombre'));
        if ($name === '') $name = 'Token sin nombre';
        $scopes = (string)$this->input('scopes', 'read,write');
        $allowed = ['read','write','*'];
        $scopesArr = array_filter(array_map('trim', explode(',', $scopes)), fn($s) => in_array($s, $allowed, true));
        if (!$scopesArr) $scopesArr = ['read'];

        $gen = \App\Core\ApiAuth::generate();
        $this->db->insert('api_tokens', [
            'tenant_id' => $tenant->id,
            'user_id' => $u['id'],
            'name' => $name,
            'token_hash' => $gen['hash'],
            'token_preview' => $gen['preview'],
            'scopes' => implode(',', $scopesArr),
        ]);

        $this->session->put('new_api_token', $gen['token']);
        $this->session->flash('success', 'Token creado. Cópialo ahora — no se mostrará de nuevo.');
        $this->redirect('/t/' . $tenant->slug . '/api-docs');
    }

    public function tokenRevoke(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->update('api_tokens', ['revoked_at' => date('Y-m-d H:i:s')], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', 'Token revocado.');
        $this->redirect('/t/' . $tenant->slug . '/api-docs');
    }
}
