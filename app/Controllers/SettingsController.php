<?php
namespace App\Controllers;

use App\Core\Controller;

class SettingsController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('settings.view');
        $categories = $this->db->all('SELECT * FROM ticket_categories WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $this->render('settings/index', ['title' => 'Ajustes', 'categories' => $categories]);
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('settings.edit');
        $this->validateCsrf();
        $data = [
            'name' => trim((string)$this->input('name', $tenant->name)),
            'support_email' => (string)$this->input('support_email',''),
            'website' => (string)$this->input('website',''),
            'primary_color' => (string)$this->input('primary_color','#6366f1'),
            'timezone' => (string)$this->input('timezone','America/Guatemala'),
        ];
        $this->db->update('tenants', $data, 'id=?', ['id' => $tenant->id]);
        $this->session->flash('success','Ajustes actualizados.');
        $this->redirect('/t/' . $tenant->slug . '/settings');
    }
}
