<?php
namespace App\Controllers;

use App\Core\Controller;

class MacroController extends Controller
{
    protected function ensureSchema(): void
    {
        $row = $this->db->one("SHOW TABLES LIKE 'macros'");
        if (!$row) {
            $this->db->run("CREATE TABLE macros (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id INT UNSIGNED NOT NULL,
                name VARCHAR(120) NOT NULL,
                body TEXT NOT NULL,
                category VARCHAR(40) DEFAULT 'general',
                shortcut VARCHAR(20) NULL,
                is_internal TINYINT(1) DEFAULT 0,
                use_count INT UNSIGNED DEFAULT 0,
                created_by INT UNSIGNED NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_macros_tenant (tenant_id),
                CONSTRAINT fk_macros_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->ensureSchema();
        $q = trim((string)($_GET['q'] ?? ''));
        $cat = (string)($_GET['category'] ?? '');

        $where = ['m.tenant_id = ?']; $args = [$tenant->id];
        if ($q !== '') { $where[] = '(m.name LIKE ? OR m.body LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; }
        if ($cat !== '') { $where[] = 'm.category = ?'; $args[] = $cat; }

        $macros = $this->db->all(
            "SELECT m.*, u.name author_name FROM macros m LEFT JOIN users u ON u.id = m.created_by
             WHERE " . implode(' AND ', $where) . " ORDER BY m.use_count DESC, m.id DESC",
            $args
        );
        $stats = [
            'total' => (int)$this->db->val('SELECT COUNT(*) FROM macros WHERE tenant_id=?', [$tenant->id]),
            'uses' => (int)($this->db->val('SELECT SUM(use_count) FROM macros WHERE tenant_id=?', [$tenant->id]) ?? 0),
        ];
        $this->render('macros/index', ['title'=>'Plantillas','macros'=>$macros,'stats'=>$stats,'q'=>$q,'cat'=>$cat]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->ensureSchema();
        $this->validateCsrf();
        $name = trim((string)$this->input('name'));
        $body = trim((string)$this->input('body'));
        if (!$name || !$body) {
            $this->session->flash('error','Nombre y contenido son obligatorios.');
            $this->redirect('/t/' . $tenant->slug . '/macros');
        }
        $this->db->insert('macros', [
            'tenant_id' => $tenant->id,
            'name' => $name,
            'body' => $body,
            'category' => (string)($this->input('category','general')),
            'shortcut' => trim((string)$this->input('shortcut','')) ?: null,
            'is_internal' => (int)($this->input('is_internal',0) ? 1 : 0),
            'created_by' => $this->auth->userId(),
        ]);
        $this->session->flash('success','Plantilla creada.');
        $this->redirect('/t/' . $tenant->slug . '/macros');
    }

    public function edit(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->ensureSchema();
        $macro = $this->db->one('SELECT * FROM macros WHERE id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        if (!$macro) $this->redirect('/t/' . $tenant->slug . '/macros');
        $this->render('macros/edit', ['title'=>'Editar plantilla','macro'=>$macro]);
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->ensureSchema();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->update('macros', [
            'name' => trim((string)$this->input('name')),
            'body' => trim((string)$this->input('body')),
            'category' => (string)$this->input('category','general'),
            'shortcut' => trim((string)$this->input('shortcut','')) ?: null,
            'is_internal' => (int)($this->input('is_internal',0) ? 1 : 0),
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success','Plantilla actualizada.');
        $this->redirect('/t/' . $tenant->slug . '/macros');
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->ensureSchema();
        $this->validateCsrf();
        $this->db->delete('macros', 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->session->flash('success','Plantilla eliminada.');
        $this->redirect('/t/' . $tenant->slug . '/macros');
    }

    public function listJson(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->ensureSchema();
        $rows = $this->db->all("SELECT id, name, body, category, shortcut, is_internal FROM macros WHERE tenant_id=? ORDER BY use_count DESC, name ASC", [$tenant->id]);
        $this->json(['macros' => $rows]);
    }
}
