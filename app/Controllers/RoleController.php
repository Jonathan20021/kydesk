<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Helpers;

class RoleController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('roles.view');
        $roles = $this->db->all(
            "SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS users_count,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS perm_count
             FROM roles r WHERE r.tenant_id = ? ORDER BY r.id",
            [$tenant->id]
        );
        $this->render('roles/index', ['title' => 'Roles', 'roles' => $roles]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('roles.create');
        $this->validateCsrf();
        $name = trim((string)$this->input('name'));
        if ($name === '') { $this->back(); return; }
        $this->db->insert('roles', [
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => Helpers::slug($name) . '-' . substr(bin2hex(random_bytes(2)),0,4),
            'description' => (string)$this->input('description',''),
            'is_system' => 0,
        ]);
        $this->session->flash('success','Rol creado.');
        $this->redirect('/t/' . $tenant->slug . '/roles');
    }

    public function edit(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('roles.view');
        $id = (int)$params['id'];
        $role = $this->db->one('SELECT * FROM roles WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$role) $this->redirect('/t/' . $tenant->slug . '/roles');

        $permissions = $this->db->all('SELECT * FROM permissions ORDER BY module, slug');
        $assignedIds = array_column(
            $this->db->all('SELECT permission_id FROM role_permissions WHERE role_id=?', [$id]),
            'permission_id'
        );
        $byModule = [];
        foreach ($permissions as $p) $byModule[$p['module']][] = $p;

        $this->render('roles/edit', [
            'title' => 'Editar rol: ' . $role['name'],
            'role' => $role,
            'byModule' => $byModule,
            'assignedIds' => array_map('intval', $assignedIds),
        ]);
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('roles.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $role = $this->db->one('SELECT * FROM roles WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$role) $this->redirect('/t/' . $tenant->slug . '/roles');

        $this->db->update('roles', [
            'name' => trim((string)$this->input('name', $role['name'])),
            'description' => (string)$this->input('description',''),
        ], 'id=?', ['id' => $id]);

        // Sincronizar permisos
        $ids = (array)($this->input('permissions', []));
        $ids = array_filter(array_map('intval', $ids));
        $this->db->delete('role_permissions', 'role_id=?', [$id]);
        if ($role['slug'] !== 'owner') {
            foreach ($ids as $pid) {
                $this->db->run('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)', [$id, $pid]);
            }
        }
        $this->session->flash('success','Rol actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/roles');
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('roles.delete');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $role = $this->db->one('SELECT * FROM roles WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$role) $this->back();
        if ($role['is_system']) {
            $this->session->flash('error','No puedes eliminar un rol del sistema.');
            $this->redirect('/t/' . $tenant->slug . '/roles');
        }
        $this->db->delete('roles', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success','Rol eliminado.');
        $this->redirect('/t/' . $tenant->slug . '/roles');
    }
}
