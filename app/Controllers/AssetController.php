<?php
namespace App\Controllers;

use App\Core\Controller;

class AssetController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('assets.view');
        $q = trim((string)($_GET['q'] ?? ''));
        $status = (string)($_GET['status'] ?? '');
        $where = ['a.tenant_id=?']; $args = [$tenant->id];
        if ($q) { $where[] = '(a.name LIKE ? OR a.serial LIKE ? OR a.model LIKE ?)'; $qq = "%$q%"; array_push($args,$qq,$qq,$qq); }
        if ($status) { $where[] = 'a.status = ?'; $args[] = $status; }
        $assets = $this->db->all(
            "SELECT a.*, c.name company_name, u.name user_name, u.email user_email
             FROM assets a
             LEFT JOIN companies c ON c.id = a.company_id
             LEFT JOIN users u ON u.id = a.assigned_to
             WHERE " . implode(' AND ', $where) . " ORDER BY a.created_at DESC",
            $args
        );
        $companies = $this->db->all('SELECT id, name FROM companies WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $users = $this->db->all('SELECT id, name FROM users WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $stats = [
            'total' => count($assets),
            'active' => (int)$this->db->val("SELECT COUNT(*) FROM assets WHERE tenant_id=? AND status='active'", [$tenant->id]),
            'maintenance' => (int)$this->db->val("SELECT COUNT(*) FROM assets WHERE tenant_id=? AND status='maintenance'", [$tenant->id]),
            'retired' => (int)$this->db->val("SELECT COUNT(*) FROM assets WHERE tenant_id=? AND status='retired'", [$tenant->id]),
        ];
        $this->render('assets/index', ['title'=>'Activos','assets'=>$assets,'companies'=>$companies,'users'=>$users,'q'=>$q,'status'=>$status,'stats'=>$stats]);
    }

    public function create(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('assets.create');
        $companies = $this->db->all('SELECT id, name FROM companies WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $users = $this->db->all('SELECT id, name FROM users WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $this->render('assets/create', ['title'=>'Nuevo activo','companies'=>$companies,'users'=>$users]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('assets.create');
        $this->validateCsrf();
        $this->db->insert('assets', [
            'tenant_id' => $tenant->id,
            'company_id' => ((int)$this->input('company_id',0)) ?: null,
            'name' => trim((string)$this->input('name')),
            'type' => (string)$this->input('type','other'),
            'serial' => (string)$this->input('serial',''),
            'model' => (string)$this->input('model',''),
            'status' => (string)$this->input('status','active'),
            'assigned_to' => ((int)$this->input('assigned_to',0)) ?: null,
            'purchase_date' => (string)$this->input('purchase_date','') ?: null,
            'warranty_until' => (string)$this->input('warranty_until','') ?: null,
            'location' => (string)$this->input('location',''),
            'notes' => (string)$this->input('notes',''),
        ]);
        $this->session->flash('success','Activo registrado.');
        $this->redirect('/t/' . $tenant->slug . '/assets');
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('assets.delete');
        $this->validateCsrf();
        $this->db->delete('assets', 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->session->flash('success','Activo eliminado.');
        $this->redirect('/t/' . $tenant->slug . '/assets');
    }
}
