<?php
namespace App\Controllers;

use App\Core\Controller;

class ProfileController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->render('profile/index', ['title' => 'Mi perfil']);
    }
    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->validateCsrf();
        $u = $this->auth->user();
        $data = [
            'name' => trim((string)$this->input('name', $u['name'])),
            'title' => (string)$this->input('title', ''),
            'phone' => (string)$this->input('phone', ''),
        ];
        $password = (string)$this->input('password','');
        if ($password !== '') {
            if (strlen($password) < 6) {
                $this->session->flash('error','La nueva contraseña debe tener al menos 6 caracteres.');
                $this->redirect('/t/' . $tenant->slug . '/profile');
            }
            $data['password'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        }
        $this->db->update('users', $data, 'id=?', ['id' => $u['id']]);
        $this->session->flash('success','Perfil actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/profile');
    }
}
