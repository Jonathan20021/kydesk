<?php
namespace App\Controllers\Admin;

use App\Core\Helpers;

class PlanController extends AdminController
{
    public function index(): void
    {
        $this->requireCan('plans.view');
        $plans = $this->db->all(
            "SELECT p.*,
                    (SELECT COUNT(*) FROM subscriptions s WHERE s.plan_id = p.id AND s.status IN ('active','trial')) AS active_subs,
                    (SELECT COUNT(*) FROM subscriptions s WHERE s.plan_id = p.id) AS total_subs
             FROM plans p ORDER BY p.sort_order ASC, p.id ASC"
        );
        $this->render('admin/plans/index', [
            'title' => 'Planes',
            'pageHeading' => 'Planes del SaaS',
            'plans' => $plans,
        ]);
    }

    public function create(): void
    {
        $this->requireCan('plans.create');
        $this->render('admin/plans/edit', [
            'title' => 'Nuevo plan',
            'pageHeading' => 'Crear plan',
            'p' => null,
        ]);
    }

    public function store(): void
    {
        $this->requireCan('plans.create');
        $this->validateCsrf();
        $data = $this->collectData();
        if (!$data['name'] || !$data['slug']) {
            $this->session->flash('error', 'Nombre y slug son requeridos.');
            $this->redirect('/admin/plans/create');
        }
        if ($this->db->val('SELECT id FROM plans WHERE slug = ?', [$data['slug']])) {
            $this->session->flash('error', 'Ya existe un plan con ese slug.');
            $this->redirect('/admin/plans/create');
        }
        $id = $this->db->insert('plans', $data);
        $this->superAuth->log('plan.create', 'plan', $id);
        $this->session->flash('success', 'Plan creado.');
        $this->redirect('/admin/plans');
    }

    public function edit(array $params): void
    {
        $this->requireCan('plans.edit');
        $id = (int)$params['id'];
        $p = $this->db->one('SELECT * FROM plans WHERE id = ?', [$id]);
        if (!$p) $this->redirect('/admin/plans');
        $this->render('admin/plans/edit', [
            'title' => $p['name'],
            'pageHeading' => 'Editar plan: ' . $p['name'],
            'p' => $p,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireCan('plans.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $data = $this->collectData();
        unset($data['slug']); // slug shouldn't change to keep referential integrity
        $this->db->update('plans', $data, 'id = :id', ['id' => $id]);
        $this->superAuth->log('plan.update', 'plan', $id);
        $this->session->flash('success', 'Plan actualizado.');
        $this->redirect('/admin/plans');
    }

    public function delete(array $params): void
    {
        $this->requireCan('plans.delete');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $count = (int)$this->db->val('SELECT COUNT(*) FROM subscriptions WHERE plan_id = ?', [$id]);
        if ($count > 0) {
            $this->session->flash('error', "No se puede eliminar: el plan tiene {$count} suscripciones asociadas.");
            $this->redirect('/admin/plans');
        }
        $this->db->delete('plans', 'id = :id', ['id' => $id]);
        $this->superAuth->log('plan.delete', 'plan', $id);
        $this->session->flash('success', 'Plan eliminado.');
        $this->redirect('/admin/plans');
    }

    public function toggle(array $params): void
    {
        $this->requireCan('plans.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $p = $this->db->one('SELECT is_active FROM plans WHERE id = ?', [$id]);
        if (!$p) $this->redirect('/admin/plans');
        $this->db->update('plans', ['is_active' => $p['is_active'] ? 0 : 1], 'id = :id', ['id' => $id]);
        $this->session->flash('success', 'Plan ' . ($p['is_active'] ? 'desactivado' : 'activado'));
        $this->redirect('/admin/plans');
    }

    protected function collectData(): array
    {
        $features = (array)($this->input('features', []));
        $features = array_values(array_filter(array_map('trim', $features)));
        return [
            'slug' => Helpers::slug((string)$this->input('slug', '')),
            'name' => trim((string)$this->input('name', '')),
            'description' => (string)$this->input('description', ''),
            'price_monthly' => (float)$this->input('price_monthly', 0),
            'price_yearly' => (float)$this->input('price_yearly', 0),
            'currency' => (string)$this->input('currency', 'USD'),
            'max_users' => (int)$this->input('max_users', 999),
            'max_tickets_month' => (int)$this->input('max_tickets_month', 999999),
            'max_kb_articles' => (int)$this->input('max_kb_articles', 999),
            'max_storage_mb' => (int)$this->input('max_storage_mb', 5120),
            'features' => json_encode($features),
            'is_active' => (int)($this->input('is_active') ? 1 : 0),
            'is_public' => (int)($this->input('is_public') ? 1 : 0),
            'is_featured' => (int)($this->input('is_featured') ? 1 : 0),
            'sort_order' => (int)$this->input('sort_order', 0),
            'trial_days' => (int)$this->input('trial_days', 0),
            'color' => (string)$this->input('color', '#7c5cff'),
            'icon' => (string)$this->input('icon', 'rocket'),
        ];
    }
}
