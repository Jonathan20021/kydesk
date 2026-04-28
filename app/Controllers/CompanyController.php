<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Mailer;

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
        $portalUsers = $this->db->all(
            'SELECT * FROM portal_users WHERE tenant_id=? AND company_id=? ORDER BY is_company_manager DESC, name ASC',
            [$tenant->id, $id]
        );
        $this->render('companies/show', [
            'title'       => $company['name'],
            'company'     => $company,
            'contacts'    => $contacts,
            'assets'      => $assets,
            'tickets'     => $tickets,
            'portalUsers' => $portalUsers,
        ]);
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

    /* ─────────── Gestión de usuarios del portal por empresa ─────────── */

    /**
     * Crea un portal_user vinculado a la empresa.
     * Si no se ingresa contraseña, genera una temporal y la envía al usuario por email.
     */
    public function portalUserStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('companies.edit');
        $this->validateCsrf();
        $companyId = (int)$params['id'];

        $company = $this->db->one('SELECT * FROM companies WHERE id=? AND tenant_id=?', [$companyId, $tenant->id]);
        if (!$company) $this->redirect('/t/' . $tenant->slug . '/companies');

        $name = trim((string)$this->input('name', ''));
        $email = trim((string)$this->input('email', ''));
        $password = (string)$this->input('password', '');
        $phone = trim((string)$this->input('phone', ''));
        $isManager = (int)$this->input('is_company_manager', 0) === 1 ? 1 : 0;
        $sendInvite = (int)$this->input('send_invite', 1) === 1;

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error', 'Nombre y email válidos son obligatorios.');
            $this->redirect('/t/' . $tenant->slug . '/companies/' . $companyId);
        }

        if ($this->db->val('SELECT id FROM portal_users WHERE tenant_id=? AND email=?', [$tenant->id, $email])) {
            $this->session->flash('error', 'Ya existe un usuario del portal con ese email.');
            $this->redirect('/t/' . $tenant->slug . '/companies/' . $companyId);
        }

        $generatedPassword = '';
        if (strlen($password) < 6) {
            $generatedPassword = $this->generatePassword();
            $password = $generatedPassword;
        }

        $verifyToken = bin2hex(random_bytes(16));
        $this->db->insert('portal_users', [
            'tenant_id'          => $tenant->id,
            'company_id'         => $companyId,
            'name'               => $name,
            'email'              => $email,
            'password'           => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'phone'              => $phone ?: null,
            'is_active'          => 1,
            'is_company_manager' => $isManager,
            'verify_token'       => $verifyToken,
        ]);

        if ($sendInvite) {
            $this->sendPortalInvite($tenant, $company, $name, $email, $generatedPassword, $verifyToken);
        }

        $msg = 'Usuario creado correctamente.';
        if ($generatedPassword) $msg .= ' Contraseña temporal: ' . $generatedPassword;
        $this->session->flash('success', $msg);
        $this->redirect('/t/' . $tenant->slug . '/companies/' . $companyId);
    }

    public function portalUserUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('companies.edit');
        $this->validateCsrf();
        $companyId = (int)$params['id'];
        $userId = (int)$params['userId'];

        $u = $this->db->one('SELECT * FROM portal_users WHERE id=? AND tenant_id=? AND company_id=?', [$userId, $tenant->id, $companyId]);
        if (!$u) $this->redirect('/t/' . $tenant->slug . '/companies/' . $companyId);

        $update = [
            'name'               => trim((string)$this->input('name', $u['name'])),
            'phone'              => trim((string)$this->input('phone', '')) ?: null,
            'is_company_manager' => (int)$this->input('is_company_manager', 0) === 1 ? 1 : 0,
            'is_active'          => (int)$this->input('is_active', 1) === 1 ? 1 : 0,
        ];
        $newPwd = (string)$this->input('new_password', '');
        if (strlen($newPwd) >= 6) {
            $update['password'] = password_hash($newPwd, PASSWORD_BCRYPT, ['cost' => 12]);
        }
        $this->db->update('portal_users', $update, 'id=?', [$userId]);

        $this->session->flash('success', 'Usuario actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/companies/' . $companyId);
    }

    public function portalUserToggle(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('companies.edit');
        $this->validateCsrf();
        $companyId = (int)$params['id'];
        $userId = (int)$params['userId'];
        $u = $this->db->one('SELECT * FROM portal_users WHERE id=? AND tenant_id=? AND company_id=?', [$userId, $tenant->id, $companyId]);
        if ($u) $this->db->update('portal_users', ['is_active' => $u['is_active'] ? 0 : 1], 'id=?', [$userId]);
        $this->redirect('/t/' . $tenant->slug . '/companies/' . $companyId);
    }

    public function portalUserToggleManager(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('companies.edit');
        $this->validateCsrf();
        $companyId = (int)$params['id'];
        $userId = (int)$params['userId'];
        $u = $this->db->one('SELECT * FROM portal_users WHERE id=? AND tenant_id=? AND company_id=?', [$userId, $tenant->id, $companyId]);
        if ($u) {
            $this->db->update('portal_users', ['is_company_manager' => $u['is_company_manager'] ? 0 : 1], 'id=?', [$userId]);
            $this->session->flash('success', $u['is_company_manager'] ? 'Manager removido.' : 'Marcado como manager.');
        }
        $this->redirect('/t/' . $tenant->slug . '/companies/' . $companyId);
    }

    public function portalUserResend(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('companies.edit');
        $this->validateCsrf();
        $companyId = (int)$params['id'];
        $userId = (int)$params['userId'];

        $u = $this->db->one('SELECT * FROM portal_users WHERE id=? AND tenant_id=? AND company_id=?', [$userId, $tenant->id, $companyId]);
        $company = $this->db->one('SELECT * FROM companies WHERE id=? AND tenant_id=?', [$companyId, $tenant->id]);
        if (!$u || !$company) $this->redirect('/t/' . $tenant->slug . '/companies/' . $companyId);

        $newPassword = $this->generatePassword();
        $verifyToken = bin2hex(random_bytes(16));
        $this->db->update('portal_users', [
            'password'     => password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]),
            'verify_token' => $verifyToken,
            'is_active'    => 1,
        ], 'id=?', [$userId]);

        $this->sendPortalInvite($tenant, $company, $u['name'], $u['email'], $newPassword, $verifyToken);
        $this->session->flash('success', 'Invitación reenviada con contraseña nueva: ' . $newPassword);
        $this->redirect('/t/' . $tenant->slug . '/companies/' . $companyId);
    }

    public function portalUserDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('companies.edit');
        $this->validateCsrf();
        $companyId = (int)$params['id'];
        $userId = (int)$params['userId'];
        $this->db->delete('portal_users', 'id=? AND tenant_id=? AND company_id=?', [$userId, $tenant->id, $companyId]);
        $this->session->flash('success', 'Usuario eliminado.');
        $this->redirect('/t/' . $tenant->slug . '/companies/' . $companyId);
    }

    protected function generatePassword(int $len = 12): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#';
        $out = '';
        for ($i = 0; $i < $len; $i++) $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        return $out;
    }

    protected function sendPortalInvite($tenant, array $company, string $name, string $email, string $tempPassword, string $verifyToken): void
    {
        try {
            $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
            $portalUrl = $appUrl . '/portal/' . $tenant->slug . '/login';
            $verifyUrl = $appUrl . '/portal/' . $tenant->slug . '/verify/' . $verifyToken;

            $passLine = $tempPassword !== ''
                ? '<p>Tu contraseña temporal es: <strong style="font-family:monospace;background:#f3f4f6;padding:4px 8px;border-radius:4px">' . htmlspecialchars($tempPassword) . '</strong></p><p>Te recomendamos cambiarla luego del primer ingreso.</p>'
                : '';
            $inner = '<p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p>'
                . '<p>El equipo de <strong>' . htmlspecialchars($tenant->name) . '</strong> te dio acceso al portal de <strong>' . htmlspecialchars($company['name']) . '</strong>.</p>'
                . $passLine
                . '<p>Hacé click abajo para verificar tu email y entrar al portal.</p>';

            (new Mailer())->send(
                ['email' => $email, 'name' => $name],
                'Acceso al portal · ' . $tenant->name,
                Mailer::template('Bienvenido al portal de soporte', $inner, 'Verificar y entrar', $verifyUrl)
            );
        } catch (\Throwable $e) {
            // No bloquear flujo si falla el mailer
        }
    }
}
