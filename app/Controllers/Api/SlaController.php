<?php
namespace App\Controllers\Api;

class SlaController extends BaseApiController
{
    public function index(): void
    {
        $this->authenticate('read');
        $rows = $this->db->all('SELECT id, name, priority, response_minutes, resolve_minutes, active, description FROM sla_policies WHERE tenant_id=? ORDER BY FIELD(priority,"urgent","high","medium","low")', [$this->tid()]);
        $this->json($rows, 200, ['total' => count($rows)]);
    }

    public function show(array $params): void
    {
        $this->authenticate('read');
        $id = (int)$params['id'];
        $row = $this->db->one('SELECT * FROM sla_policies WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$row) $this->error('Política SLA no encontrada', 404, 'not_found');
        $this->json($row);
    }

    public function create(): void
    {
        $this->authenticate('write');
        $b = $this->body();
        $this->require($b, ['name','priority']);
        $id = $this->db->insert('sla_policies', [
            'tenant_id' => $this->tid(),
            'name' => trim((string)$b['name']),
            'priority' => in_array((string)$b['priority'], ['low','medium','high','urgent'], true) ? (string)$b['priority'] : 'medium',
            'response_minutes' => (int)$this->in($b, 'response_minutes', 60),
            'resolve_minutes' => (int)$this->in($b, 'resolve_minutes', 1440),
            'active' => (int)($this->in($b, 'active', 1) ? 1 : 0),
            'description' => (string)$this->in($b, 'description', ''),
        ]);
        $this->created($this->db->one('SELECT * FROM sla_policies WHERE id=?', [$id]));
    }

    public function update(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $exists = $this->db->one('SELECT id FROM sla_policies WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$exists) $this->error('Política SLA no encontrada', 404, 'not_found');
        $b = $this->body();
        $upd = array_intersect_key($b, array_flip(['name','priority','response_minutes','resolve_minutes','active','description']));
        if ($upd) $this->db->update('sla_policies', $upd, 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json($this->db->one('SELECT * FROM sla_policies WHERE id=?', [$id]));
    }

    public function delete(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $this->db->delete('sla_policies', 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json(['deleted' => true, 'id' => $id]);
    }
}
