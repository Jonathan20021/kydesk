<?php
namespace App\Controllers\Api;

use App\Core\Helpers;

class KbController extends BaseApiController
{
    public function index(): void
    {
        $this->authenticate('read');
        $where = ['tenant_id = ?']; $args = [$this->tid()];
        if ($s = $_GET['status'] ?? null) { $where[] = 'status = ?'; $args[] = $s; }
        if ($v = $_GET['visibility'] ?? null) { $where[] = 'visibility = ?'; $args[] = $v; }
        if ($cat = $_GET['category_id'] ?? null) { $where[] = 'category_id = ?'; $args[] = (int)$cat; }
        if ($q = $_GET['q'] ?? null) { $where[] = '(title LIKE ? OR excerpt LIKE ? OR body LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%"; }
        $whereSql = implode(' AND ', $where);
        ['limit' => $limit, 'offset' => $offset] = $this->paginate();
        $sort = $this->sortClause(['id','title','views','created_at','published_at'], 'created_at');
        $rows = $this->db->all("SELECT id, category_id, author_id, title, slug, excerpt, status, visibility, views, helpful_yes, helpful_no, published_at, created_at, updated_at FROM kb_articles WHERE $whereSql ORDER BY $sort LIMIT $limit OFFSET $offset", $args);
        $total = (int)$this->db->val("SELECT COUNT(*) FROM kb_articles WHERE $whereSql", $args);
        $this->json($rows, 200, ['total' => $total, 'limit' => $limit, 'offset' => $offset]);
    }

    public function show(array $params): void
    {
        $this->authenticate('read');
        $id = (int)$params['id'];
        $row = $this->db->one('SELECT * FROM kb_articles WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$row) $this->error('Artículo no encontrado', 404, 'not_found');
        if ($this->shouldExpand('category') && $row['category_id']) {
            $row['category'] = $this->db->one('SELECT id, name, icon, color FROM kb_categories WHERE id=?', [$row['category_id']]);
        }
        $this->json($row);
    }

    public function create(): void
    {
        $this->authenticate('write');
        $b = $this->body();
        $this->require($b, ['title']);
        $title = trim((string)$b['title']);
        $slug = (string)$this->in($b, 'slug', Helpers::slug($title));
        $id = $this->db->insert('kb_articles', [
            'tenant_id' => $this->tid(),
            'category_id' => ($c = (int)$this->in($b, 'category_id', 0)) ?: null,
            'author_id' => $this->uid(),
            'title' => $title,
            'slug' => $slug,
            'excerpt' => (string)$this->in($b, 'excerpt', ''),
            'body' => (string)$this->in($b, 'body', ''),
            'status' => (function($v){ return in_array($v, ['draft','published'], true) ? $v : 'draft'; })((string)$this->in($b, 'status', 'draft')),
            'visibility' => (function($v){ return in_array($v, ['internal','public'], true) ? $v : 'internal'; })((string)$this->in($b, 'visibility', 'internal')),
            'published_at' => ($this->in($b, 'status') === 'published') ? date('Y-m-d H:i:s') : null,
        ]);
        $this->created($this->db->one('SELECT * FROM kb_articles WHERE id=?', [$id]));
    }

    public function update(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $exists = $this->db->one('SELECT id, status FROM kb_articles WHERE id=? AND tenant_id=?', [$id, $this->tid()]);
        if (!$exists) $this->error('Artículo no encontrado', 404, 'not_found');
        $b = $this->body();
        $upd = array_intersect_key($b, array_flip(['title','slug','category_id','excerpt','body','status','visibility']));
        if (($upd['status'] ?? null) === 'published' && $exists['status'] !== 'published') $upd['published_at'] = date('Y-m-d H:i:s');
        if ($upd) $this->db->update('kb_articles', $upd, 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json($this->db->one('SELECT * FROM kb_articles WHERE id=?', [$id]));
    }

    public function delete(array $params): void
    {
        $this->authenticate('write');
        $id = (int)$params['id'];
        $this->db->delete('kb_articles', 'id=? AND tenant_id=?', [$id, $this->tid()]);
        $this->json(['deleted' => true, 'id' => $id]);
    }

    public function categoriesIndex(): void
    {
        $this->authenticate('read');
        $rows = $this->db->all('SELECT id, name, icon, color, description FROM kb_categories WHERE tenant_id=? ORDER BY name', [$this->tid()]);
        $this->json($rows, 200, ['total' => count($rows)]);
    }
}
