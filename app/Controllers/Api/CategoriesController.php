<?php
namespace App\Controllers\Api;

class CategoriesController extends BaseApiController
{
    public function index(): void
    {
        $this->authenticate('read');
        $rows = $this->db->all('SELECT id, name, color, icon, created_at FROM ticket_categories WHERE tenant_id=? ORDER BY name', [$this->tid()]);
        $this->json($rows, 200, ['total' => count($rows)]);
    }

    public function show(array $params): void
    {
        $this->authenticate('read');
        $id = (int)$params['id'];
        $row = $this->db->one('SELECT * FROM ticket_categories WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$row) $this->error('Categoría no encontrada', 404, 'not_found');
        $this->json($row);
    }

    public function create(): void
    {
        $this->authenticate('write');
        $b = $this->body();
        $this->require($b, ['name']);
        $id = $this->db->insert('ticket_categories', [
            'tenant_id' => $this->tid(),
            'name' => trim((string)$b['name']),
            'color' => (string)$this->in($b, 'color', '#7c5cff'),
            'icon' => (string)$this->in($b, 'icon', 'tag'),
        ]);
        $this->created($this->db->one('SELECT * FROM ticket_categories WHERE id=?', [$id]));
    }

    public function update(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $exists = $this->db->one('SELECT id FROM ticket_categories WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$exists) $this->error('Categoría no encontrada', 404, 'not_found');
        $b = $this->body();
        $upd = array_intersect_key($b, array_flip(['name','color','icon']));
        if ($upd) $this->db->update('ticket_categories', $upd, 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json($this->db->one('SELECT * FROM ticket_categories WHERE id=?', [$id]));
    }

    public function delete(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $this->db->delete('ticket_categories', 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json(['deleted' => true, 'id' => $id]);
    }
}
