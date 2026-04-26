<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Helpers;
use App\Core\License;
use App\Core\Mailer;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if ($this->auth->check()) {
            $u = $this->auth->user();
            $t = $this->db->one('SELECT slug FROM tenants WHERE id = :i', ['i' => $u['tenant_id']]);
            if ($t) $this->redirect('/t/' . $t['slug'] . '/dashboard');
        }
        $this->render('auth/login', ['title' => 'Iniciar sesión'], 'auth');
    }

    public function login(): void
    {
        $this->validateCsrf();
        $email = trim((string)$this->input('email', ''));
        $password = (string)$this->input('password', '');

        if (!$email || !$password) {
            $this->session->flash('error', 'Email y contraseña son requeridos.');
            $this->redirect('/auth/login');
        }

        $user = $this->auth->attempt($email, $password);
        if (!$user) {
            $this->session->flash('error', 'Credenciales inválidas o cuenta inactiva.');
            $this->redirect('/auth/login');
        }
        $tenant = $this->db->one('SELECT slug FROM tenants WHERE id = :i', ['i' => $user['tenant_id']]);
        $this->session->flash('success', '¡Bienvenido de vuelta, ' . $user['name'] . '!');
        $this->redirect('/t/' . $tenant['slug'] . '/dashboard');
    }

    public function showRegister(): void
    {
        $this->render('auth/register', ['title' => 'Crear organización'], 'auth');
    }

    public function register(): void
    {
        $this->validateCsrf();
        $name      = trim((string)$this->input('name', ''));
        $email     = trim((string)$this->input('email', ''));
        $password  = (string)$this->input('password', '');
        $orgName   = trim((string)$this->input('org_name', ''));
        $orgSlug   = Helpers::slug((string)$this->input('org_slug', $orgName));

        if (!$name || !$email || !$password || !$orgName || !$orgSlug) {
            $this->session->flash('error', 'Completa todos los campos.');
            $this->redirect('/auth/register');
        }
        if (strlen($password) < 6) {
            $this->session->flash('error', 'La contraseña debe tener al menos 6 caracteres.');
            $this->redirect('/auth/register');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error', 'Email inválido.');
            $this->redirect('/auth/register');
        }

        $exists = $this->db->val('SELECT id FROM users WHERE email = :e', ['e' => $email]);
        if ($exists) {
            $this->session->flash('error', 'Ya existe una cuenta con ese email.');
            $this->redirect('/auth/register');
        }
        $slugExists = $this->db->val('SELECT id FROM tenants WHERE slug = :s', ['s' => $orgSlug]);
        if ($slugExists) {
            $orgSlug = $orgSlug . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }

        try {
            $this->db->pdo()->beginTransaction();

            $defaultPlan = License::defaultPlan();
            // Mantenemos el ENUM legacy de 'plan' (free/pro/business/enterprise);
            // la suscripción es la fuente de verdad para features.
            $legacyPlan = in_array($defaultPlan['slug'] ?? '', ['free','pro','business','enterprise'], true)
                ? $defaultPlan['slug']
                : 'free';
            $tenantId = $this->db->insert('tenants', [
                'name' => $orgName,
                'slug' => $orgSlug,
                'support_email' => $email,
                'plan' => $legacyPlan,
                'is_active' => 1,
            ]);

            // Clonar roles estándar
            $defaultRoles = [
                ['owner','Owner','Acceso total',1],
                ['admin','Administrador','Administra la operación',1],
                ['supervisor','Supervisor','Supervisa técnicos',1],
                ['technician','Técnico','Atiende tickets',1],
                ['agent','Agente','Crea tickets',1],
            ];
            $roleMap = [];
            foreach ($defaultRoles as [$s,$n,$d,$sys]) {
                $roleMap[$s] = $this->db->insert('roles', [
                    'tenant_id' => $tenantId,
                    'name' => $n, 'slug' => $s, 'description' => $d, 'is_system' => $sys,
                ]);
            }

            // Asignar permisos a roles (owner asume todos vía bypass)
            $perms = $this->db->all('SELECT id, slug FROM permissions');
            $bySlug = [];
            foreach ($perms as $p) $bySlug[$p['slug']] = $p['id'];
            $map = [
                'admin' => array_keys($bySlug),
                'supervisor' => ['tickets.view','tickets.create','tickets.edit','tickets.assign','tickets.escalate','tickets.comment','notes.view','notes.create','notes.edit','notes.delete','todos.view','todos.create','todos.edit','todos.delete','users.view','roles.view','reports.view'],
                'technician' => ['tickets.view','tickets.create','tickets.edit','tickets.comment','tickets.escalate','notes.view','notes.create','notes.edit','notes.delete','todos.view','todos.create','todos.edit','todos.delete','reports.view'],
                'agent' => ['tickets.view','tickets.create','tickets.comment','notes.view','notes.create','notes.edit','notes.delete','todos.view','todos.create','todos.edit','todos.delete'],
            ];
            foreach ($map as $rs => $slugs) {
                foreach ($slugs as $sP) {
                    if (isset($bySlug[$sP])) {
                        $this->db->run('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)', [$roleMap[$rs], $bySlug[$sP]]);
                    }
                }
            }

            // Categorías por defecto
            foreach ([
                ['Hardware','#f59e0b','cpu'],['Software','#6366f1','box'],
                ['Redes','#14b8a6','wifi'],['Accesos','#ec4899','key'],['Otros','#64748b','folder']
            ] as [$n,$c,$i]) {
                $this->db->insert('ticket_categories', ['tenant_id'=>$tenantId,'name'=>$n,'color'=>$c,'icon'=>$i]);
            }

            // Owner
            $userId = $this->db->insert('users', [
                'tenant_id' => $tenantId,
                'role_id'   => $roleMap['owner'],
                'name'      => $name,
                'email'     => $email,
                'password'  => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                'is_technician' => 0,
                'is_active' => 1,
                'title'     => 'Fundador',
            ]);

            // Lista de tareas por defecto
            $this->db->insert('todo_lists', ['tenant_id'=>$tenantId,'user_id'=>$userId,'name'=>'Bandeja','color'=>'#6366f1','icon'=>'inbox']);

            // Inicia trial controlado por super admin
            try { License::startTrialFor($tenantId, $defaultPlan); } catch (\Throwable $e) { /* tabla opcional */ }

            $this->db->pdo()->commit();

            $this->auth->login($userId);
            $trialDays = License::defaultTrialDays();

            // Email de bienvenida (no bloquear si falla)
            try {
                $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
                $loginUrl = $appUrl . '/t/' . $orgSlug . '/dashboard';
                $inner = '<p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p>'
                    . '<p>¡Tu organización <strong>' . htmlspecialchars($orgName) . '</strong> ya está lista en Kydesk Helpdesk!</p>'
                    . ($trialDays > 0 ? '<p>Tienes <strong>' . $trialDays . ' días</strong> de prueba para explorar todas las funciones.</p>' : '')
                    . '<p>Próximos pasos sugeridos:</p>'
                    . '<ul><li>Invita a tu equipo desde Usuarios</li><li>Crea tu primera categoría de tickets</li><li>Comparte el portal público con tus clientes</li></ul>';
                (new Mailer())->send(
                    ['email' => $email, 'name' => $name],
                    'Bienvenido a Kydesk Helpdesk · ' . $orgName,
                    Mailer::template('¡Bienvenido a Kydesk!', $inner, 'Ir al panel', $loginUrl)
                );
            } catch (\Throwable $e) { /* no bloquear registro */ }
            $msg = $trialDays > 0
                ? "¡Organización creada! Tienes {$trialDays} días de prueba. Tu cuenta queda lista para que el equipo de Kydesk active tu licencia."
                : '¡Organización creada con éxito! Contacta al equipo de Kydesk para activar tu licencia.';
            $this->session->flash('success', $msg);
            $this->redirect('/t/' . $orgSlug . '/dashboard');
        } catch (\Throwable $e) {
            if ($this->db->pdo()->inTransaction()) $this->db->pdo()->rollBack();
            $this->session->flash('error', 'No pudimos crear la cuenta: ' . $e->getMessage());
            $this->redirect('/auth/register');
        }
    }

    public function logout(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') $this->validateCsrf();
        $this->auth->logout();
        $this->redirect('/auth/login');
    }
}
