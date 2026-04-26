<?php
namespace App\Controllers\Developer;

class ConsoleController extends DeveloperController
{
    public function index(): void
    {
        $this->requireDeveloper();
        $devId = $this->devAuth->id();
        $apps = $this->db->all('SELECT id, name, slug FROM dev_apps WHERE developer_id=? ORDER BY id DESC', [$devId]);
        $tokens = $this->db->all(
            "SELECT t.id, t.name, t.token_preview, t.app_id, a.name AS app_name
             FROM dev_api_tokens t LEFT JOIN dev_apps a ON a.id=t.app_id
             WHERE t.developer_id=? AND t.revoked_at IS NULL ORDER BY t.id DESC",
            [$devId]
        );
        $this->render('developers/console/index', [
            'title' => 'API Console',
            'pageHeading' => 'API Console · Try-it',
            'apps' => $apps,
            'tokens' => $tokens,
        ]);
    }
}
