<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Mailer;

class StatusPageController extends Controller
{
    public const COMPONENT_STATUSES = ['operational','degraded','partial_outage','major_outage','maintenance'];
    public const INCIDENT_STATUSES = ['investigating','identified','monitoring','resolved'];

    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('status_page');
        $this->requireCan('status.view');

        $components = $this->db->all('SELECT * FROM status_components WHERE tenant_id = ? ORDER BY sort_order, id', [$tenant->id]);
        $incidents = $this->db->all(
            'SELECT i.*, u.name AS author_name FROM status_incidents i LEFT JOIN users u ON u.id = i.created_by WHERE i.tenant_id = ? ORDER BY i.started_at DESC LIMIT 30',
            [$tenant->id]
        );
        $subscribers = (int)$this->db->val('SELECT COUNT(*) FROM status_subscribers WHERE tenant_id = ? AND is_confirmed = 1', [$tenant->id]);
        $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');

        $this->render('status_admin/index', [
            'title' => 'Status Page',
            'components' => $components,
            'incidents' => $incidents,
            'subscribers' => $subscribers,
            'publicUrl' => $appUrl . '/status/' . $tenant->slug,
        ]);
    }

    public function componentStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('status_page');
        $this->requireCan('status.edit');
        $this->validateCsrf();

        $name = trim((string)$this->input('name', ''));
        if ($name === '') { $this->session->flash('error','Nombre requerido.'); $this->redirect('/t/' . $tenant->slug . '/status'); }
        $slug = $this->uniqueComponentSlug($tenant->id, $name);

        $this->db->insert('status_components', [
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => $slug,
            'description' => (string)$this->input('description','') ?: null,
            'icon' => (string)$this->input('icon','server') ?: 'server',
            'status' => $this->validStatus($this->input('status','operational'), self::COMPONENT_STATUSES, 'operational'),
            'sort_order' => (int)$this->input('sort_order', 0),
            'is_active' => 1,
        ]);
        $this->session->flash('success', 'Componente creado.');
        $this->redirect('/t/' . $tenant->slug . '/status');
    }

    public function componentUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('status_page');
        $this->requireCan('status.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->update('status_components', [
            'name' => trim((string)$this->input('name','')),
            'description' => (string)$this->input('description','') ?: null,
            'icon' => (string)$this->input('icon','server'),
            'status' => $this->validStatus($this->input('status','operational'), self::COMPONENT_STATUSES, 'operational'),
            'sort_order' => (int)$this->input('sort_order', 0),
            'is_active' => (int)($this->input('is_active') ? 1 : 0),
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', 'Componente actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/status');
    }

    public function componentDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('status_page');
        $this->requireCan('status.edit');
        $this->validateCsrf();
        $this->db->delete('status_components', 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->session->flash('success', 'Componente eliminado.');
        $this->redirect('/t/' . $tenant->slug . '/status');
    }

    public function incidentStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('status_page');
        $this->requireCan('status.edit');
        $this->validateCsrf();

        $title = trim((string)$this->input('title',''));
        if ($title === '') { $this->session->flash('error','Título requerido.'); $this->redirect('/t/' . $tenant->slug . '/status'); }

        $components = (array)$this->input('components', []);
        $components = array_values(array_map('intval', array_filter($components)));
        $startedAt = (string)$this->input('started_at', '');
        $startedAt = $startedAt !== '' ? str_replace('T',' ', $startedAt) : date('Y-m-d H:i:s');

        $incidentId = $this->db->insert('status_incidents', [
            'tenant_id'           => $tenant->id,
            'title'               => $title,
            'description'         => (string)$this->input('description','') ?: null,
            'severity'            => $this->validStatus($this->input('severity','minor'), ['minor','major','critical','maintenance'], 'minor'),
            'status'              => $this->validStatus($this->input('status','investigating'), self::INCIDENT_STATUSES, 'investigating'),
            'affected_components' => $components ? json_encode($components) : null,
            'started_at'          => $startedAt,
            'is_public'           => (int)($this->input('is_public', 1) ? 1 : 0),
            'created_by'          => $this->auth->userId(),
        ]);

        $this->db->insert('status_incident_updates', [
            'incident_id' => $incidentId,
            'tenant_id' => $tenant->id,
            'status' => $this->validStatus($this->input('status','investigating'), self::INCIDENT_STATUSES, 'investigating'),
            'body' => (string)$this->input('description','Estamos investigando este incidente.'),
            'posted_by' => $this->auth->userId(),
        ]);

        // Update affected components status if specified
        if ($components && in_array($this->input('component_status'), self::COMPONENT_STATUSES, true)) {
            foreach ($components as $cid) {
                $this->db->update('status_components', ['status' => $this->input('component_status')], 'id=? AND tenant_id=?', [(int)$cid, $tenant->id]);
            }
        }

        $this->notifySubscribers($tenant->id, $incidentId, 'created');
        $this->session->flash('success', 'Incidente creado.');
        $this->redirect('/t/' . $tenant->slug . '/status');
    }

    public function incidentUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('status_page');
        $this->requireCan('status.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $status = $this->validStatus($this->input('status','investigating'), self::INCIDENT_STATUSES, 'investigating');
        $body = trim((string)$this->input('body',''));
        if ($body === '') { $this->session->flash('error','Mensaje requerido.'); $this->redirect('/t/' . $tenant->slug . '/status'); }

        $this->db->update('status_incidents', [
            'status' => $status,
            'resolved_at' => $status === 'resolved' ? date('Y-m-d H:i:s') : null,
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);

        $this->db->insert('status_incident_updates', [
            'incident_id' => $id,
            'tenant_id' => $tenant->id,
            'status' => $status,
            'body' => $body,
            'posted_by' => $this->auth->userId(),
        ]);
        $this->notifySubscribers($tenant->id, $id, 'updated');
        $this->session->flash('success', 'Update publicado.');
        $this->redirect('/t/' . $tenant->slug . '/status');
    }

    public function incidentDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('status_page');
        $this->requireCan('status.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->delete('status_incident_updates', 'incident_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->delete('status_incidents', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', 'Incidente eliminado.');
        $this->redirect('/t/' . $tenant->slug . '/status');
    }

    /** Vista pública. */
    public function publicPage(array $params): void
    {
        $slug = (string)$params['slug'];
        $tenant = \App\Core\Tenant::resolve($slug);
        if (!$tenant) { http_response_code(404); echo 'Status page no encontrada.'; return; }

        $this->app->tenant = $tenant;
        $components = $this->db->all('SELECT * FROM status_components WHERE tenant_id = ? AND is_active = 1 ORDER BY sort_order, id', [$tenant->id]);
        $activeIncidents = $this->db->all("SELECT * FROM status_incidents WHERE tenant_id = ? AND status <> 'resolved' AND is_public = 1 ORDER BY started_at DESC", [$tenant->id]);
        $resolvedIncidents = $this->db->all("SELECT * FROM status_incidents WHERE tenant_id = ? AND status = 'resolved' AND is_public = 1 ORDER BY started_at DESC LIMIT 12", [$tenant->id]);
        $updates = [];
        $allIds = array_merge(
            array_map(fn($i)=>(int)$i['id'],$activeIncidents),
            array_map(fn($i)=>(int)$i['id'],$resolvedIncidents)
        );
        if (!empty($allIds)) {
            $in = implode(',', array_map('intval',$allIds));
            $updates = $this->db->all("SELECT * FROM status_incident_updates WHERE tenant_id = ? AND incident_id IN ($in) ORDER BY posted_at DESC", [$tenant->id]);
        }
        $updatesByIncident = [];
        foreach ($updates as $u) $updatesByIncident[(int)$u['incident_id']][] = $u;

        $overall = 'operational';
        foreach ($components as $c) {
            if ($c['status'] === 'major_outage') { $overall = 'major_outage'; break; }
            if ($c['status'] === 'partial_outage') $overall = 'partial_outage';
            elseif ($c['status'] === 'degraded' && $overall !== 'partial_outage') $overall = 'degraded';
            elseif ($c['status'] === 'maintenance' && $overall === 'operational') $overall = 'maintenance';
        }

        $this->render('status_admin/public', [
            'title' => 'Status · ' . $tenant->name,
            'tenantPublic' => $tenant,
            'components' => $components,
            'activeIncidents' => $activeIncidents,
            'resolvedIncidents' => $resolvedIncidents,
            'updatesByIncident' => $updatesByIncident,
            'overall' => $overall,
        ], 'public');
    }

    public function subscribe(array $params): void
    {
        $slug = (string)$params['slug'];
        $tenant = \App\Core\Tenant::resolve($slug);
        if (!$tenant) { http_response_code(404); echo 'No encontrada.'; return; }
        $this->validateCsrf();

        $email = trim((string)$this->input('email',''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->session->flash('error','Email inválido.'); $this->redirect('/status/' . $slug); }

        $existing = $this->db->one('SELECT * FROM status_subscribers WHERE tenant_id=? AND email=?', [$tenant->id, $email]);
        if ($existing && (int)$existing['is_confirmed'] === 1) {
            $this->session->flash('info','Ya estás suscripto.');
            $this->redirect('/status/' . $slug);
        }
        $token = bin2hex(random_bytes(16));
        if ($existing) {
            $this->db->update('status_subscribers', ['confirm_token' => $token], 'id = :id', ['id' => (int)$existing['id']]);
        } else {
            $this->db->insert('status_subscribers', [
                'tenant_id' => $tenant->id,
                'email' => $email,
                'confirm_token' => $token,
                'is_confirmed' => 0,
            ]);
        }
        $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
        $confirmUrl = $appUrl . '/status/' . $slug . '/confirm/' . $token;
        try {
            (new Mailer())->send(['email' => $email, 'name' => $email], 'Confirma tu suscripción a ' . $tenant->name . ' Status',
                Mailer::template('Confirma tu suscripción', '<p>Para recibir alertas de incidentes de <strong>' . htmlspecialchars($tenant->name) . '</strong>, confirmá tu email haciendo click en el botón.</p>', 'Confirmar suscripción', $confirmUrl)
            );
        } catch (\Throwable $e) {}
        $this->session->flash('success','Te enviamos un email para confirmar la suscripción.');
        $this->redirect('/status/' . $slug);
    }

    public function confirm(array $params): void
    {
        $slug = (string)$params['slug'];
        $token = (string)$params['token'];
        $tenant = \App\Core\Tenant::resolve($slug);
        if (!$tenant) { http_response_code(404); return; }
        $sub = $this->db->one('SELECT * FROM status_subscribers WHERE tenant_id=? AND confirm_token=?', [$tenant->id, $token]);
        if (!$sub) { http_response_code(404); echo 'Token inválido.'; return; }
        $this->db->update('status_subscribers', ['is_confirmed' => 1, 'confirmed_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => (int)$sub['id']]);
        $this->session->flash('success', 'Suscripción confirmada. Te avisaremos cuando haya incidentes.');
        $this->redirect('/status/' . $slug);
    }

    protected function notifySubscribers(int $tenantId, int $incidentId, string $action): void
    {
        $incident = $this->db->one('SELECT * FROM status_incidents WHERE id=? AND tenant_id=?', [$incidentId, $tenantId]);
        if (!$incident || !$incident['is_public']) return;
        $tenant = $this->db->one('SELECT * FROM tenants WHERE id=?', [$tenantId]);
        $subs = $this->db->all('SELECT * FROM status_subscribers WHERE tenant_id=? AND is_confirmed=1', [$tenantId]);
        if (empty($subs)) return;
        $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
        $url = $appUrl . '/status/' . $tenant['slug'];
        $subj = ($action === 'created' ? '🔴 Nuevo incidente' : '🔄 Actualización') . ': ' . $incident['title'];
        $inner = '<p><strong>' . htmlspecialchars($incident['title']) . '</strong></p>'
            . '<p>Estado: ' . htmlspecialchars($incident['status']) . '</p>'
            . '<p>' . nl2br(htmlspecialchars($incident['description'] ?? '')) . '</p>';
        try {
            $mailer = new Mailer();
            foreach ($subs as $s) {
                $mailer->send(['email' => $s['email'], 'name' => $s['email']], $subj . ' · ' . $tenant['name'],
                    Mailer::template($subj, $inner, 'Ver Status Page', $url)
                );
            }
        } catch (\Throwable $e) {}
    }

    protected function validStatus(?string $val, array $allowed, string $default): string
    {
        return in_array($val, $allowed, true) ? $val : $default;
    }

    protected function uniqueComponentSlug(int $tenantId, string $name): string
    {
        $base = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
        $base = trim($base,'-') ?: 'comp';
        $slug = substr($base, 0, 70);
        $i = 1;
        while ($this->db->val('SELECT id FROM status_components WHERE tenant_id=? AND slug=?', [$tenantId, $slug])) {
            $i++;
            $slug = substr($base, 0, 70 - strlen((string)$i) - 1) . '-' . $i;
        }
        return $slug;
    }
}
