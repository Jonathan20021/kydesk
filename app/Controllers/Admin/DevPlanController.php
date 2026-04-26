<?php
namespace App\Controllers\Admin;

use App\Core\Helpers;

class DevPlanController extends AdminController
{
    public function index(): void
    {
        $this->requireSuperAuth();
        $plans = $this->db->all(
            "SELECT p.*,
                    (SELECT COUNT(*) FROM dev_subscriptions s WHERE s.plan_id=p.id AND s.status IN ('active','trial')) AS active_subs,
                    (SELECT COUNT(*) FROM dev_subscriptions s WHERE s.plan_id=p.id) AS total_subs
             FROM dev_plans p ORDER BY p.sort_order ASC, p.id ASC"
        );
        $this->render('admin/dev_plans/index', [
            'title' => 'Planes Developers',
            'pageHeading' => 'Planes API para Developers',
            'plans' => $plans,
        ]);
    }

    public function create(): void
    {
        $this->requireSuperAuth();
        $this->render('admin/dev_plans/edit', [
            'title' => 'Nuevo plan dev',
            'pageHeading' => 'Crear plan developer',
            'p' => null,
        ]);
    }

    public function store(): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $data = $this->collectData();
        if (!$data['name'] || !$data['slug']) {
            $this->session->flash('error', 'Nombre y slug son requeridos.');
            $this->redirect('/admin/dev-plans/create');
        }
        if ($this->db->val('SELECT id FROM dev_plans WHERE slug=?', [$data['slug']])) {
            $this->session->flash('error', 'Ya existe un plan con ese slug.');
            $this->redirect('/admin/dev-plans/create');
        }
        $id = $this->db->insert('dev_plans', $data);
        $this->superAuth->log('dev_plan.create', 'dev_plan', $id);
        $this->session->flash('success', 'Plan developer creado.');
        $this->redirect('/admin/dev-plans');
    }

    public function edit(array $params): void
    {
        $this->requireSuperAuth();
        $id = (int)$params['id'];
        $p = $this->db->one('SELECT * FROM dev_plans WHERE id=?', [$id]);
        if (!$p) $this->redirect('/admin/dev-plans');
        $this->render('admin/dev_plans/edit', [
            'title' => $p['name'],
            'pageHeading' => 'Editar plan: ' . $p['name'],
            'p' => $p,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $data = $this->collectData();
        unset($data['slug']);
        $this->db->update('dev_plans', $data, 'id=?', [$id]);
        $this->superAuth->log('dev_plan.update', 'dev_plan', $id);
        $this->session->flash('success', 'Plan actualizado.');
        $this->redirect('/admin/dev-plans');
    }

    public function delete(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $count = (int)$this->db->val('SELECT COUNT(*) FROM dev_subscriptions WHERE plan_id=?', [$id]);
        if ($count > 0) {
            $this->session->flash('error', "No se puede eliminar: tiene {$count} suscripciones.");
            $this->redirect('/admin/dev-plans');
        }
        $this->db->delete('dev_plans', 'id=?', [$id]);
        $this->superAuth->log('dev_plan.delete', 'dev_plan', $id);
        $this->session->flash('success', 'Plan eliminado.');
        $this->redirect('/admin/dev-plans');
    }

    public function toggle(array $params): void
    {
        $this->requireSuperAuth();
        $this->validateCsrf();
        $id = (int)$params['id'];
        $p = $this->db->one('SELECT is_active FROM dev_plans WHERE id=?', [$id]);
        if (!$p) $this->redirect('/admin/dev-plans');
        $this->db->update('dev_plans', ['is_active' => $p['is_active'] ? 0 : 1], 'id=?', [$id]);
        $this->session->flash('success', 'Plan ' . ($p['is_active'] ? 'desactivado' : 'activado'));
        $this->redirect('/admin/dev-plans');
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
            'max_apps' => (int)$this->input('max_apps', 1),
            'max_requests_month' => (int)$this->input('max_requests_month', 10000),
            'max_tokens_per_app' => (int)$this->input('max_tokens_per_app', 5),
            'rate_limit_per_min' => (int)$this->input('rate_limit_per_min', 60),
            'overage_price_per_1k' => (float)$this->input('overage_price_per_1k', 0),
            'features' => json_encode($features),
            'is_active' => (int)($this->input('is_active') ? 1 : 0),
            'is_public' => (int)($this->input('is_public') ? 1 : 0),
            'is_featured' => (int)($this->input('is_featured') ? 1 : 0),
            'sort_order' => (int)$this->input('sort_order', 0),
            'trial_days' => (int)$this->input('trial_days', 0),
            'color' => (string)$this->input('color', '#0ea5e9'),
            'icon' => (string)$this->input('icon', 'code'),
        ];
    }
}
