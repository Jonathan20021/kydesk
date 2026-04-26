<?php
namespace App\Controllers\Admin;

class DevTokenController extends AdminController
{
    public function index(): void
    {
        $this->requireSuperAuth();
        $q = trim((string)$this->input('q', ''));
        $where = '1=1'; $args = [];
        if ($q !== '') {
            $where .= ' AND (t.name LIKE ? OR t.token_preview LIKE ? OR d.email LIKE ? OR a.name LIKE ?)';
            $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%";
        }
        $rows = $this->db->all(
            "SELECT t.*, d.name AS dev_name, d.email AS dev_email, a.name AS app_name, a.slug AS app_slug
             FROM dev_api_tokens t
             JOIN developers d ON d.id = t.developer_id
             LEFT JOIN dev_apps a ON a.id = t.app_id
             WHERE $where ORDER BY t.id DESC LIMIT 200",
            $args
        );
        $this->render('admin/dev_tokens/index', [
            'title' => 'Tokens API Developers',
            'pageHeading' => 'Tokens API de developers',
            'tokens' => $rows,
            'q' => $q,
        ]);
    }

    public function revoke(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->update('dev_api_tokens', ['revoked_at' => date('Y-m-d H:i:s')], 'id=?', [$id]);
        $this->superAuth->log('dev_token.revoke', 'dev_api_token', $id);
        $this->session->flash('success', 'Token revocado por super admin.');
        $this->back();
    }
}
