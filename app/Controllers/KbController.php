<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Helpers;

class KbController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('kb.view');
        $q = trim((string)($_GET['q'] ?? ''));
        $category = (int)($_GET['category'] ?? 0);
        $status = (string)($_GET['status'] ?? '');

        $where = ['a.tenant_id=?']; $args = [$tenant->id];
        if ($q) { $where[] = '(a.title LIKE ? OR a.body LIKE ? OR a.excerpt LIKE ?)'; $qq = "%$q%"; array_push($args,$qq,$qq,$qq); }
        if ($category) { $where[] = 'a.category_id = ?'; $args[] = $category; }
        if ($status) { $where[] = 'a.status = ?'; $args[] = $status; }

        $articles = $this->db->all(
            "SELECT a.*, c.name cat_name, c.color cat_color, c.icon cat_icon, u.name author
             FROM kb_articles a
             LEFT JOIN kb_categories c ON c.id = a.category_id
             LEFT JOIN users u ON u.id = a.author_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY a.updated_at DESC",
            $args
        );
        $categories = $this->db->all('SELECT * FROM kb_categories WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $counts = [
            'all' => (int)$this->db->val('SELECT COUNT(*) FROM kb_articles WHERE tenant_id=?', [$tenant->id]),
            'published' => (int)$this->db->val("SELECT COUNT(*) FROM kb_articles WHERE tenant_id=? AND status='published'", [$tenant->id]),
            'draft' => (int)$this->db->val("SELECT COUNT(*) FROM kb_articles WHERE tenant_id=? AND status='draft'", [$tenant->id]),
            'views' => (int)$this->db->val('SELECT SUM(views) FROM kb_articles WHERE tenant_id=?', [$tenant->id]) ?: 0,
        ];
        $this->render('kb/index', ['title'=>'Base de conocimiento','articles'=>$articles,'categories'=>$categories,'counts'=>$counts,'q'=>$q,'categoryFilter'=>$category,'statusFilter'=>$status]);
    }

    public function create(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('kb.create');
        $categories = $this->db->all('SELECT * FROM kb_categories WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $this->render('kb/create', ['title'=>'Nuevo artículo','categories'=>$categories]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('kb.create');
        $this->validateCsrf();
        $current = (int)$this->db->val('SELECT COUNT(*) FROM kb_articles WHERE tenant_id=?', [$tenant->id]);
        $this->enforceLimit('kb_articles', $current, 'artículos KB', '/t/' . $tenant->slug . '/kb');
        $title = trim((string)$this->input('title','Sin título'));
        $slug = Helpers::slug($title) . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        $st = (string)$this->input('status','draft');
        $this->db->insert('kb_articles', [
            'tenant_id' => $tenant->id,
            'category_id' => ((int)$this->input('category_id',0)) ?: null,
            'author_id' => $this->auth->userId(),
            'title' => $title,
            'slug' => $slug,
            'excerpt' => (string)$this->input('excerpt',''),
            'body' => (string)$this->input('body',''),
            'status' => $st,
            'visibility' => (string)$this->input('visibility','internal'),
            'published_at' => $st === 'published' ? date('Y-m-d H:i:s') : null,
        ]);
        $this->session->flash('success','Artículo creado.');
        $this->redirect('/t/' . $tenant->slug . '/kb');
    }

    public function show(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('kb.view');
        $id = (int)$params['id'];
        $article = $this->db->one(
            'SELECT a.*, c.name cat_name, c.color cat_color, u.name author FROM kb_articles a
             LEFT JOIN kb_categories c ON c.id = a.category_id
             LEFT JOIN users u ON u.id = a.author_id
             WHERE a.id=? AND a.tenant_id=?',
            [$id, $tenant->id]
        );
        if (!$article) $this->redirect('/t/' . $tenant->slug . '/kb');
        $this->db->run('UPDATE kb_articles SET views = views+1 WHERE id=?', [$id]);
        $this->render('kb/show', ['title'=>$article['title'],'article'=>$article]);
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('kb.delete');
        $this->validateCsrf();
        $this->db->delete('kb_articles', 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->session->flash('success','Artículo eliminado.');
        $this->redirect('/t/' . $tenant->slug . '/kb');
    }
}
