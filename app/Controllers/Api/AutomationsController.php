<?php
namespace App\Controllers\Api;

class AutomationsController extends BaseApiController
{
    public function index(): void
    {
        $this->authenticate('read');
        $rows = $this->db->all('SELECT id, name, description, trigger_event, conditions, actions, active, run_count, last_run_at, created_at FROM automations WHERE tenant_id=? ORDER BY id DESC', [$this->tid()]);
        foreach ($rows as &$r) {
            $r['conditions'] = json_decode((string)$r['conditions'], true);
            $r['actions'] = json_decode((string)$r['actions'], true);
        }
        $this->json($rows, 200, ['total' => count($rows)]);
    }

    public function show(array $params): void
    {
        $this->authenticate('read');
        $id = (int)$params['id'];
        $row = $this->db->one('SELECT * FROM automations WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$row) $this->error('Automatización no encontrada', 404, 'not_found');
        $row['conditions'] = json_decode((string)$row['conditions'], true);
        $row['actions'] = json_decode((string)$row['actions'], true);
        $this->json($row);
    }

    public function create(): void
    {
        $this->authenticate('write');
        $b = $this->body();
        $this->require($b, ['name','trigger_event']);
        $events = ['ticket.created','ticket.updated','ticket.sla_breach','ticket.escalated','ticket.resolved'];
        if (!in_array((string)$b['trigger_event'], $events, true)) {
            $this->error('trigger_event inválido', 422, 'validation_error', ['allowed' => $events]);
        }
        $id = $this->db->insert('automations', [
            'tenant_id' => $this->tid(),
            'name' => trim((string)$b['name']),
            'description' => (string)$this->in($b, 'description', ''),
            'trigger_event' => (string)$b['trigger_event'],
            'conditions' => json_encode($this->in($b, 'conditions', []), JSON_UNESCAPED_UNICODE),
            'actions' => json_encode($this->in($b, 'actions', []), JSON_UNESCAPED_UNICODE),
            'active' => (int)($this->in($b, 'active', 1) ? 1 : 0),
            'created_by' => $this->uid(),
        ]);
        $row = $this->db->one('SELECT * FROM automations WHERE id=?', [$id]);
        $row['conditions'] = json_decode((string)$row['conditions'], true);
        $row['actions'] = json_decode((string)$row['actions'], true);
        $this->created($row);
    }

    public function update(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $exists = $this->db->one('SELECT id FROM automations WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$exists) $this->error('Automatización no encontrada', 404, 'not_found');
        $b = $this->body();
        $upd = [];
        foreach (['name','description','trigger_event','active'] as $f) {
            if (array_key_exists($f, $b)) $upd[$f] = $b[$f];
        }
        if (array_key_exists('conditions', $b)) $upd['conditions'] = json_encode($b['conditions']);
        if (array_key_exists('actions', $b)) $upd['actions'] = json_encode($b['actions']);
        if ($upd) $this->db->update('automations', $upd, 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $row = $this->db->one('SELECT * FROM automations WHERE id=?', [$id]);
        $row['conditions'] = json_decode((string)$row['conditions'], true);
        $row['actions'] = json_decode((string)$row['actions'], true);
        $this->json($row);
    }

    public function delete(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $this->db->delete('automations', 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json(['deleted' => true, 'id' => $id]);
    }
}
