<?php
namespace App\Controllers;

use App\Core\Controller;

class CategoryController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('tickets.view');
        $categories = $this->db->all(
            "SELECT c.*, (SELECT COUNT(*) FROM tickets WHERE category_id=c.id) AS ticket_count
             FROM ticket_categories c WHERE tenant_id=? ORDER BY name",
            [$tenant->id]
        );
        $this->render('categories/index', [
            'title' => 'Categorías',
            'categories' => $categories,
        ]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('tickets.edit');
        $this->validateCsrf();
        $name = trim((string)$this->input('name', ''));
        if ($name === '') {
            $this->session->flash('error', 'El nombre es obligatorio.');
            $this->redirect('/t/' . $tenant->slug . '/categories');
        }
        $color = (string)$this->input('color', '#7c5cff');
        $icon  = (string)$this->input('icon', 'tag');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#7c5cff';
        $this->db->insert('ticket_categories', [
            'tenant_id' => $tenant->id,
            'name' => $name,
            'color' => $color,
            'icon' => $icon,
        ]);
        $this->session->flash('success', 'Categoría creada.');
        $this->redirect('/t/' . $tenant->slug . '/categories');
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('tickets.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $name = trim((string)$this->input('name', ''));
        if ($name === '') {
            $this->session->flash('error', 'El nombre es obligatorio.');
            $this->redirect('/t/' . $tenant->slug . '/categories');
        }
        $color = (string)$this->input('color', '#7c5cff');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#7c5cff';
        $this->db->update('ticket_categories', [
            'name' => $name,
            'color' => $color,
            'icon' => (string)$this->input('icon', 'tag'),
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', 'Categoría actualizada.');
        $this->redirect('/t/' . $tenant->slug . '/categories');
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('tickets.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        // Desvincular tickets que la usaban
        $this->db->run('UPDATE tickets SET category_id=NULL WHERE category_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->delete('ticket_categories', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', 'Categoría eliminada.');
        $this->redirect('/t/' . $tenant->slug . '/categories');
    }
}
