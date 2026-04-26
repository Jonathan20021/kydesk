<?php
namespace App\Controllers\Api;

class CompaniesController extends BaseApiController
{
    public function index(): void
    {
        $this->authenticate('read');
        $tid = $this->tid();
        $where = ['tenant_id = ?']; $args = [$tid];
        if ($q = $_GET['q'] ?? null) { $where[] = '(name LIKE ? OR website LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; }
        if ($t = $_GET['tier'] ?? null) { $where[] = 'tier = ?'; $args[] = $t; }
        $sort = $this->sortClause(['id','name','created_at'], 'name', 'ASC');
        ['limit' => $limit, 'offset' => $offset] = $this->paginate();
        $whereSql = implode(' AND ', $where);
        $rows = $this->db->all("SELECT * FROM companies WHERE $whereSql ORDER BY $sort LIMIT $limit OFFSET $offset", $args);
        $total = (int)$this->db->val("SELECT COUNT(*) FROM companies WHERE $whereSql", $args);
        $this->json($rows, 200, ['total' => $total, 'limit' => $limit, 'offset' => $offset]);
    }

    public function show(array $params): void
    {
        $this->authenticate('read');
        $id = (int)$params['id'];
        $row = $this->db->one('SELECT * FROM companies WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$row) $this->error('Empresa no encontrada', 404, 'not_found');
        if ($this->shouldExpand('contacts')) {
            $row['contacts'] = $this->db->all('SELECT id, name, email, phone, title FROM contacts WHERE company_id=? AND tenant_id=?', [$id, $this->tid()]);
        }
        if ($this->shouldExpand('tickets')) {
            $row['tickets_count'] = (int)$this->db->val('SELECT COUNT(*) FROM tickets WHERE company_id=? AND tenant_id=?', [$id, $this->tid()]);
        }
        $this->json($row);
    }

    public function create(): void
    {
        $this->authenticate('write');
        $b = $this->body();
        $this->require($b, ['name']);
        $id = $this->db->insert('companies', [
            'tenant_id' => $this->tid(),
            'name' => trim((string)$b['name']),
            'industry' => (string)$this->in($b, 'industry', ''),
            'size' => (string)$this->in($b, 'size', ''),
            'website' => (string)$this->in($b, 'website', ''),
            'phone' => (string)$this->in($b, 'phone', ''),
            'address' => (string)$this->in($b, 'address', ''),
            'notes' => (string)$this->in($b, 'notes', ''),
            'tier' => (function($v){ return in_array($v, ['standard','premium','enterprise'], true) ? $v : 'standard'; })((string)$this->in($b, 'tier', 'standard')),
        ]);
        $this->created($this->db->one('SELECT * FROM companies WHERE id=?', [$id]));
    }

    public function update(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $exists = $this->db->one('SELECT id FROM companies WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$exists) $this->error('Empresa no encontrada', 404, 'not_found');
        $b = $this->body();
        $allowed = ['name','industry','size','website','phone','address','notes','tier'];
        $upd = array_intersect_key($b, array_flip($allowed));
        if ($upd) $this->db->update('companies', $upd, 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json($this->db->one('SELECT * FROM companies WHERE id=?', [$id]));
    }

    public function delete(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $this->db->delete('companies', 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json(['deleted' => true, 'id' => $id]);
    }
}
