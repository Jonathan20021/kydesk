<?php
namespace App\Controllers\Developer;

class WebhooksController extends DeveloperController
{
    public function index(): void
    {
        $this->requireDeveloper();
        $devId = $this->devAuth->id();
        $hooks = $this->db->all(
            "SELECT w.*, a.name AS app_name FROM dev_webhooks w
             LEFT JOIN dev_apps a ON a.id = w.app_id
             WHERE w.developer_id = ? ORDER BY w.id DESC",
            [$devId]
        );
        $apps = $this->db->all('SELECT id, name FROM dev_apps WHERE developer_id=? ORDER BY name', [$devId]);
        $deliveries = $this->db->all(
            "SELECT d.*, w.name AS webhook_name FROM webhook_deliveries d
             JOIN dev_webhooks w ON w.id = d.webhook_id
             WHERE w.developer_id = ? ORDER BY d.id DESC LIMIT 25",
            [$devId]
        );
        $newSecret = $this->session->get('new_webhook_secret');
        if ($newSecret) $this->session->put('new_webhook_secret', null);

        $this->render('developers/webhooks/index', [
            'title' => 'Webhooks',
            'pageHeading' => 'Webhooks',
            'hooks' => $hooks,
            'apps' => $apps,
            'deliveries' => $deliveries,
            'newSecret' => $newSecret,
        ]);
    }

    public function create(): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $devId = $this->devAuth->id();
        $appId = (int)$this->input('app_id', 0);
        $app = $this->db->one('SELECT id FROM dev_apps WHERE id=? AND developer_id=?', [$appId, $devId]);
        if (!$app) {
            $this->session->flash('error', 'App inválida.');
            $this->redirect('/developers/webhooks');
        }

        $name = trim((string)$this->input('name', 'Webhook'));
        $rawUrl = trim((string)$this->input('url', ''));
        if (!filter_var($rawUrl, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $rawUrl)) {
            $this->session->flash('error', 'URL del webhook inválida (debe ser http o https).');
            $this->redirect('/developers/webhooks');
        }
        $events = $this->input('events', ['*']);
        if (!is_array($events)) $events = [(string)$events];
        $events = array_filter(array_map('trim', $events));
        if (!$events) $events = ['*'];

        $secret = bin2hex(random_bytes(32));
        $id = $this->db->insert('dev_webhooks', [
            'developer_id' => $devId,
            'app_id' => $appId,
            'name' => $name,
            'url' => $rawUrl,
            'secret' => $secret,
            'events' => implode(',', $events),
            'is_active' => 1,
        ]);
        $this->session->put('new_webhook_secret', ['id' => $id, 'secret' => $secret]);
        $this->devAuth->log('webhook.create', 'webhook', $id);
        $this->session->flash('success', 'Webhook creado. Guarda el secret ahora — no se mostrará otra vez.');
        $this->redirect('/developers/webhooks');
    }

    public function toggle(array $params): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $devId = $this->devAuth->id();
        $h = $this->db->one('SELECT is_active FROM dev_webhooks WHERE id=? AND developer_id=?', [$id, $devId]);
        if (!$h) $this->redirect('/developers/webhooks');
        $this->db->update('dev_webhooks', ['is_active' => $h['is_active'] ? 0 : 1], 'id=? AND developer_id=?', [$id, $devId]);
        $this->session->flash('success', 'Webhook ' . ($h['is_active'] ? 'desactivado' : 'activado'));
        $this->redirect('/developers/webhooks');
    }

    public function delete(array $params): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $devId = $this->devAuth->id();
        $this->db->delete('dev_webhooks', 'id=? AND developer_id=?', [$id, $devId]);
        $this->devAuth->log('webhook.delete', 'webhook', $id);
        $this->session->flash('success', 'Webhook eliminado.');
        $this->redirect('/developers/webhooks');
    }

    public function rotateSecret(array $params): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $devId = $this->devAuth->id();
        $h = $this->db->one('SELECT id FROM dev_webhooks WHERE id=? AND developer_id=?', [$id, $devId]);
        if (!$h) $this->redirect('/developers/webhooks');
        $secret = bin2hex(random_bytes(32));
        $this->db->update('dev_webhooks', ['secret' => $secret], 'id=?', [$id]);
        $this->session->put('new_webhook_secret', ['id' => $id, 'secret' => $secret]);
        $this->devAuth->log('webhook.rotate_secret', 'webhook', $id);
        $this->session->flash('success', 'Secret rotado. Cópialo ahora.');
        $this->redirect('/developers/webhooks');
    }

    public function test(array $params): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $devId = $this->devAuth->id();
        $h = $this->db->one('SELECT * FROM dev_webhooks WHERE id=? AND developer_id=?', [$id, $devId]);
        if (!$h) $this->redirect('/developers/webhooks');

        $payload = [
            'event' => 'webhook.test',
            'data' => ['ping' => 'pong', 'timestamp' => date('c'), 'webhook_id' => (int)$h['id']],
        ];
        $body = json_encode($payload);
        $sig = hash_hmac('sha256', $body, (string)$h['secret']);

        $ch = curl_init($h['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Kydesk-Signature: ' . $sig,
                'X-Kydesk-Event: webhook.test',
                'User-Agent: Kydesk-Webhook/1.0',
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $resp = curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $this->db->insert('webhook_deliveries', [
            'webhook_id' => $id,
            'event' => 'webhook.test',
            'payload_json' => $body,
            'status_code' => $statusCode,
            'response_excerpt' => substr((string)$resp ?: $err, 0, 500),
            'attempt' => 1,
            'delivered_at' => $statusCode >= 200 && $statusCode < 300 ? date('Y-m-d H:i:s') : null,
        ]);
        $this->db->update('dev_webhooks', [
            'last_triggered_at' => date('Y-m-d H:i:s'),
            'last_status_code' => $statusCode,
            'failure_count' => $statusCode >= 200 && $statusCode < 300 ? 0 : ((int)$h['failure_count'] + 1),
        ], 'id=?', [$id]);

        if ($statusCode >= 200 && $statusCode < 300) {
            $this->session->flash('success', "Test enviado: $statusCode OK");
        } else {
            $this->session->flash('error', "Test falló: $statusCode " . ($err ? "($err)" : ''));
        }
        $this->redirect('/developers/webhooks');
    }
}
