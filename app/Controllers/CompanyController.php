<?php
namespace App\Controllers;

use App\Core\Controller;

class CompanyController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('companies.view');
        $q = trim((string)($_GET['q'] ?? ''));
        $where = 'tenant_id=?'; $args = [$tenant->id];
        if ($q) { $where .= ' AND name LIKE ?'; $args[] = "%$q%"; }
        $companies = $this->db->all(
            "SELECT c.*, (SELECT COUNT(*) FROM tickets t WHERE t.company_id=c.id) AS tickets,
                    (SELECT COUNT(*) FROM contacts ct WHERE ct.company_id=c.id) AS contacts,
                    (SELECT COUNT(*) FROM assets a WHERE a.company_id=c.id) AS assets
             FROM companies c WHERE $where ORDER BY c.name",
            $args
        );
        $this->render('companies/index', ['title'=>'Empresas','companies'=>$companies,'q'=>$q]);
    }

    public function create(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('companies.create');
        $this->render('companies/create', ['title'=>'Nueva empresa']);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('companies.create');
        $this->validateCsrf();
        $this->db->insert('companies', [
            'tenant_id' => $tenant->id,
            'name' => trim((string)$this->input('name')),
            'industry' => (string)$this->input('industry',''),
            'size' => (string)$this->input('size',''),
            'website' => (string)$this->input('website',''),
            'phone' => (string)$this->input('phone',''),
            'address' => (string)$this->input('address',''),
            'tier' => (string)$this->input('tier','standard'),
            'notes' => (string)$this->input('notes',''),
        ]);
        $this->session->flash('success','Empresa creada.');
        $this->redirect('/t/' . $tenant->slug . '/companies');
    }

    public function show(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('companies.view');
        $id = (int)$params['id'];
        $company = $this->db->one('SELECT * FROM companies WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$company) $this->redirect('/t/' . $tenant->slug . '/companies');
        $contacts = $this->db->all('SELECT * FROM contacts WHERE company_id=? ORDER BY name', [$id]);
        $assets = $this->db->all('SELECT * FROM assets WHERE company_id=? ORDER BY created_at DESC', [$id]);
        $tickets = $this->db->all('SELECT * FROM tickets WHERE company_id=? ORDER BY created_at DESC LIMIT 20', [$id]);
        $this->render('companies/show', ['title'=>$company['name'],'company'=>$company,'contacts'=>$contacts,'assets'=>$assets,'tickets'=>$tickets]);
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('companies.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->update('companies', [
            'name' => trim((string)$this->input('name')),
            'industry' => (string)$this->input('industry',''),
            'size' => (string)$this->input('size',''),
            'website' => (string)$this->input('website',''),
            'phone' => (string)$this->input('phone',''),
            'address' => (string)$this->input('address',''),
            'tier' => (string)$this->input('tier','standard'),
            'notes' => (string)$this->input('notes',''),
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success','Empresa actualizada.');
        $this->redirect('/t/' . $tenant->slug . '/companies/' . $id);
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('companies.delete');
        $this->validateCsrf();
        $this->db->delete('companies', 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->session->flash('success','Empresa eliminada.');
        $this->redirect('/t/' . $tenant->slug . '/companies');
    }
}
