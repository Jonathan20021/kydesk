<?php
namespace App\Controllers\Api;

class AssetsController extends BaseApiController
{
    public function index(): void
    {
        $this->authenticate('read');
        $where = ['tenant_id = ?']; $args = [$this->tid()];
        if ($t = $_GET['type'] ?? null)         { $where[] = 'type = ?'; $args[] = $t; }
        if ($s = $_GET['status'] ?? null)       { $where[] = 'status = ?'; $args[] = $s; }
        if ($c = $_GET['company_id'] ?? null)   { $where[] = 'company_id = ?'; $args[] = (int)$c; }
        if ($u = $_GET['assigned_to'] ?? null)  { $where[] = 'assigned_to = ?'; $args[] = (int)$u; }
        if ($q = $_GET['q'] ?? null)            { $where[] = '(name LIKE ? OR serial LIKE ? OR model LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%"; }
        $whereSql = implode(' AND ', $where);
        ['limit' => $limit, 'offset' => $offset] = $this->paginate();
        $sort = $this->sortClause(['id','name','created_at','warranty_until'], 'name', 'ASC');
        $rows = $this->db->all("SELECT * FROM assets WHERE $whereSql ORDER BY $sort LIMIT $limit OFFSET $offset", $args);
        $total = (int)$this->db->val("SELECT COUNT(*) FROM assets WHERE $whereSql", $args);
        $this->json($rows, 200, ['total' => $total, 'limit' => $limit, 'offset' => $offset]);
    }

    public function show(array $params): void
    {
        $this->authenticate('read');
        $id = (int)$params['id'];
        $row = $this->db->one('SELECT * FROM assets WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$row) $this->error('Activo no encontrado', 404, 'not_found');
        $this->json($row);
    }

    public function create(): void
    {
        $this->authenticate('write');
        $b = $this->body();
        $this->require($b, ['name','type']);
        $id = $this->db->insert('assets', [
            'tenant_id' => $this->tid(),
            'name' => trim((string)$b['name']),
            'type' => trim((string)$b['type']),
            'serial' => (string)$this->in($b, 'serial', ''),
            'model' => (string)$this->in($b, 'model', ''),
            'status' => (function($v){ return in_array($v, ['active','maintenance','retired','lost'], true) ? $v : 'active'; })((string)$this->in($b, 'status', 'active')),
            'company_id' => ($c = (int)$this->in($b, 'company_id', 0)) ?: null,
            'assigned_to' => ($u = (int)$this->in($b, 'assigned_to', 0)) ?: null,
            'purchase_date' => $this->in($b, 'purchase_date'),
            'warranty_until' => $this->in($b, 'warranty_until'),
            'location' => (string)$this->in($b, 'location', ''),
            'notes' => (string)$this->in($b, 'notes', ''),
        ]);
        $this->created($this->db->one('SELECT * FROM assets WHERE id=?', [$id]));
    }

    public function update(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $exists = $this->db->one('SELECT id FROM assets WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$exists) $this->error('Activo no encontrado', 404, 'not_found');
        $b = $this->body();
        $upd = array_intersect_key($b, array_flip(['name','type','serial','model','status','company_id','assigned_to','purchase_date','warranty_until','location','notes']));
        if ($upd) $this->db->update('assets', $upd, 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json($this->db->one('SELECT * FROM assets WHERE id=?', [$id]));
    }

    public function delete(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $this->db->delete('assets', 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json(['deleted' => true, 'id' => $id]);
    }
}
