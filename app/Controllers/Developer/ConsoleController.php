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
        $saved = $this->db->all('SELECT * FROM dev_console_saved WHERE developer_id=? ORDER BY id DESC LIMIT 30', [$devId]);
        $this->render('developers/console/index', [
            'title' => 'API Console',
            'pageHeading' => 'API Console · Try-it',
            'apps' => $apps,
            'tokens' => $tokens,
            'saved' => $saved,
        ]);
    }

    public function save(): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $devId = $this->devAuth->id();
        $name = trim((string)$this->input('name', 'Untitled'));
        $method = strtoupper((string)$this->input('method', 'GET'));
        $path = (string)$this->input('path', '/');
        $body = (string)$this->input('body', '');
        if (!in_array($method, ['GET','POST','PATCH','PUT','DELETE'], true)) $method = 'GET';
        $this->db->insert('dev_console_saved', [
            'developer_id' => $devId, 'name' => $name, 'method' => $method, 'path' => $path, 'body' => $body,
        ]);
        $this->json(['ok' => true]);
    }

    public function deleteSaved(array $params): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $devId = $this->devAuth->id();
        $this->db->delete('dev_console_saved', 'id=? AND developer_id=?', [(int)$params['id'], $devId]);
        $this->json(['ok' => true]);
    }

    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
