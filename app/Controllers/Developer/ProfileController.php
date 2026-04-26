<?php
namespace App\Controllers\Developer;

class ProfileController extends DeveloperController
{
    public function index(): void
    {
        $this->requireDeveloper();
        $this->render('developers/profile/index', [
            'title' => 'Perfil',
            'pageHeading' => 'Mi perfil',
        ]);
    }

    public function update(): void
    {
        $this->requireDeveloper();
        $this->validateCsrf();
        $id = $this->devAuth->id();
        $data = [
            'name' => trim((string)$this->input('name', '')),
            'company' => trim((string)$this->input('company', '')),
            'website' => trim((string)$this->input('website', '')),
            'country' => trim((string)$this->input('country', '')),
            'phone' => trim((string)$this->input('phone', '')),
            'bio' => trim((string)$this->input('bio', '')),
        ];
        if ($data['name'] === '') {
            $this->session->flash('error', 'El nombre es requerido.');
            $this->redirect('/developers/profile');
        }
        $newPass = (string)$this->input('password', '');
        if ($newPass !== '') {
            if (strlen($newPass) < 6) {
                $this->session->flash('error', 'La contraseña debe tener al menos 6 caracteres.');
                $this->redirect('/developers/profile');
            }
            $data['password'] = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
        }
        $this->db->update('developers', $data, 'id=?', [$id]);
        $this->devAuth->log('profile.update');
        $this->session->flash('success', 'Perfil actualizado.');
        $this->redirect('/developers/profile');
    }
}
