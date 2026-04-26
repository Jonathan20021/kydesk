<?php
namespace App\Controllers\Admin;

use App\Core\Helpers;
use App\Core\License;
use App\Core\Mailer;
use App\Core\Tenant as TenantModel;

class TenantController extends AdminController
{
    public function index(): void
    {
        $this->requireCan('tenants.view');
        $q = trim((string)$this->input('q', ''));
        $status = (string)$this->input('status', '');
        $plan = (string)$this->input('plan', '');

        $where = ['1=1', 'IFNULL(t.is_developer_sandbox,0) = 0'];
        $params = [];
        if ($q !== '') {
            $where[] = '(t.name LIKE ? OR t.slug LIKE ? OR t.support_email LIKE ?)';
            $like = "%$q%";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if ($status === 'active') $where[] = 't.is_active = 1 AND t.suspended_at IS NULL';
        if ($status === 'suspended') $where[] = '(t.suspended_at IS NOT NULL OR t.is_active = 0)';
        if ($status === 'demo') $where[] = 't.is_demo = 1';
        if ($plan !== '') { $where[] = 't.plan = ?'; $params[] = $plan; }

        $tenants = $this->db->all(
            "SELECT t.*,
                    (SELECT COUNT(*) FROM users u WHERE u.tenant_id = t.id) AS users_count,
                    (SELECT COUNT(*) FROM tickets ti WHERE ti.tenant_id = t.id) AS tickets_count,
                    s.status AS sub_status, s.amount AS sub_amount, s.billing_cycle, s.current_period_end,
                    p.name AS plan_name, p.color AS plan_color
             FROM tenants t
             LEFT JOIN subscriptions s ON s.id = (SELECT MAX(id) FROM subscriptions WHERE tenant_id = t.id)
             LEFT JOIN plans p ON p.id = s.plan_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY t.created_at DESC LIMIT 200",
            $params
        );

        $totalActive = (int)$this->db->val("SELECT COUNT(*) FROM tenants WHERE is_active=1 AND suspended_at IS NULL AND IFNULL(is_developer_sandbox,0)=0");
        $totalSuspended = (int)$this->db->val("SELECT COUNT(*) FROM tenants WHERE (suspended_at IS NOT NULL OR is_active=0) AND IFNULL(is_developer_sandbox,0)=0");
        $totalDemo = (int)$this->db->val("SELECT COUNT(*) FROM tenants WHERE is_demo = 1 AND IFNULL(is_developer_sandbox,0)=0");

        $this->render('admin/tenants/index', [
            'title' => 'Empresas',
            'pageHeading' => 'Empresas (Tenants)',
            'tenants' => $tenants,
            'q' => $q, 'status' => $status, 'plan' => $plan,
            'totalActive' => $totalActive, 'totalSuspended' => $totalSuspended, 'totalDemo' => $totalDemo,
            'plans' => $this->db->all('SELECT * FROM plans WHERE is_active=1 ORDER BY sort_order ASC'),
        ]);
    }

    public function create(): void
    {
        $this->requireCan('tenants.create');
        $plans = $this->db->all('SELECT * FROM plans WHERE is_active=1 ORDER BY sort_order ASC');
        $this->render('admin/tenants/create', [
            'title' => 'Nueva empresa',
            'pageHeading' => 'Crear empresa',
            'plans' => $plans,
        ]);
    }

    public function store(): void
    {
        $this->requireCan('tenants.create');
        $this->validateCsrf();

        $name = trim((string)$this->input('name', ''));
        $slug = Helpers::slug((string)($this->input('slug') ?: $name));
        $planId = (int)$this->input('plan_id', 0);
        $ownerName = trim((string)$this->input('owner_name', ''));
        $ownerEmail = trim((string)$this->input('owner_email', ''));
        $ownerPassword = (string)$this->input('owner_password', '');

        if (!$name || !$slug || !$ownerName || !$ownerEmail || !$ownerPassword) {
            $this->session->flash('error', 'Completa todos los campos obligatorios.');
            $this->redirect('/admin/tenants/create');
        }
        if (strlen($ownerPassword) < 6) {
            $this->session->flash('error', 'La contraseña del owner debe tener al menos 6 caracteres.');
            $this->redirect('/admin/tenants/create');
        }
        if (!filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error', 'Email del owner inválido.');
            $this->redirect('/admin/tenants/create');
        }
        if ($this->db->val('SELECT id FROM users WHERE email = ?', [$ownerEmail])) {
            $this->session->flash('error', 'Ya existe un usuario con ese email.');
            $this->redirect('/admin/tenants/create');
        }
        if ($this->db->val('SELECT id FROM tenants WHERE slug = ?', [$slug])) {
            $slug = $slug . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }
        $plan = $this->db->one('SELECT * FROM plans WHERE id = ?', [$planId]);
        if (!$plan) {
            $this->session->flash('error', 'Plan inválido.');
            $this->redirect('/admin/tenants/create');
        }

        try {
            $this->db->pdo()->beginTransaction();

            $tenantId = $this->db->insert('tenants', [
                'name' => $name,
                'slug' => $slug,
                'plan' => $plan['slug'],
                'support_email' => $ownerEmail,
                'billing_email' => $this->input('billing_email') ?: $ownerEmail,
                'country' => (string)$this->input('country', ''),
                'website' => (string)$this->input('website', ''),
                'is_active' => 1,
                'notes' => (string)$this->input('notes', ''),
            ]);

            // Roles estándar
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

            // Permisos a roles
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
            ] as [$cn,$cc,$ci]) {
                $this->db->insert('ticket_categories', ['tenant_id'=>$tenantId,'name'=>$cn,'color'=>$cc,'icon'=>$ci]);
            }

            $userId = $this->db->insert('users', [
                'tenant_id' => $tenantId,
                'role_id' => $roleMap['owner'],
                'name' => $ownerName,
                'email' => $ownerEmail,
                'password' => password_hash($ownerPassword, PASSWORD_BCRYPT, ['cost' => 12]),
                'is_active' => 1,
                'title' => 'Owner',
            ]);

            $this->db->insert('todo_lists', ['tenant_id'=>$tenantId,'user_id'=>$userId,'name'=>'Bandeja','color'=>'#6366f1','icon'=>'inbox']);

            // Suscripción
            $cycle = (string)$this->input('billing_cycle', 'monthly');
            $amount = $cycle === 'yearly' ? (float)$plan['price_yearly'] : (float)$plan['price_monthly'];
            $trialDays = (int)$plan['trial_days'];
            $status = $trialDays > 0 ? 'trial' : 'active';
            $now = date('Y-m-d H:i:s');
            $periodEnd = $cycle === 'yearly' ? date('Y-m-d H:i:s', strtotime('+1 year')) : date('Y-m-d H:i:s', strtotime('+1 month'));
            $trialEnds = $trialDays > 0 ? date('Y-m-d H:i:s', strtotime("+{$trialDays} days")) : null;

            $this->db->insert('subscriptions', [
                'tenant_id' => $tenantId,
                'plan_id' => $plan['id'],
                'status' => $status,
                'billing_cycle' => $cycle,
                'amount' => $amount,
                'currency' => $plan['currency'],
                'started_at' => $now,
                'trial_ends_at' => $trialEnds,
                'current_period_start' => $now,
                'current_period_end' => $periodEnd,
                'auto_renew' => 1,
            ]);

            $this->db->pdo()->commit();
            $this->superAuth->log('tenant.create', 'tenant', $tenantId, ['name' => $name, 'plan' => $plan['slug']]);

            try {
                $appUrl = rtrim($this->app->config['app']['url'] ?? '', '/');
                $loginUrl = $appUrl . '/auth/login';
                $inner = '<p>Hola <strong>' . htmlspecialchars($ownerName) . '</strong>,</p>'
                    . '<p>Tu organización <strong>' . htmlspecialchars($name) . '</strong> ha sido creada en Kydesk Helpdesk.</p>'
                    . '<p><strong>Plan:</strong> ' . htmlspecialchars($plan['name']) . ($trialDays > 0 ? ' (' . $trialDays . ' días de prueba)' : '') . '</p>'
                    . '<p><strong>Acceso:</strong> ' . htmlspecialchars($ownerEmail) . '</p>'
                    . '<p><strong>Contraseña temporal:</strong> <code>' . htmlspecialchars($ownerPassword) . '</code></p>'
                    . '<p style="color:#dc2626;font-size:13px;">Por seguridad, cámbiala en tu primer ingreso.</p>';
                (new Mailer())->send(
                    ['email' => $ownerEmail, 'name' => $ownerName],
                    'Tu cuenta en Kydesk Helpdesk · ' . $name,
                    Mailer::template('Tu cuenta está lista', $inner, 'Iniciar sesión', $loginUrl)
                );
            } catch (\Throwable $e) { /* ignore */ }

            $this->session->flash('success', "Empresa '{$name}' creada con éxito. Owner: {$ownerEmail}");
            $this->redirect('/admin/tenants/' . $tenantId);
        } catch (\Throwable $e) {
            if ($this->db->pdo()->inTransaction()) $this->db->pdo()->rollBack();
            $this->session->flash('error', 'Error al crear la empresa: ' . $e->getMessage());
            $this->redirect('/admin/tenants/create');
        }
    }

    public function show(array $params): void
    {
        $this->requireCan('tenants.view');
        $id = (int)$params['id'];
        $tenant = $this->db->one('SELECT * FROM tenants WHERE id = ?', [$id]);
        if (!$tenant) { $this->session->flash('error','Empresa no encontrada.'); $this->redirect('/admin/tenants'); }

        $users = $this->db->all(
            "SELECT u.*, r.name AS role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.tenant_id = ? ORDER BY u.created_at DESC",
            [$id]
        );
        $subscription = $this->db->one(
            "SELECT s.*, p.name AS plan_name, p.slug AS plan_slug FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.tenant_id = ? ORDER BY s.id DESC LIMIT 1",
            [$id]
        );
        $invoices = $this->db->all(
            "SELECT * FROM invoices WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 20",
            [$id]
        );
        $payments = $this->db->all(
            "SELECT * FROM payments WHERE tenant_id = ? ORDER BY paid_at DESC LIMIT 20",
            [$id]
        );
        $stats = [
            'tickets'  => (int)$this->db->val('SELECT COUNT(*) FROM tickets WHERE tenant_id = ?', [$id]),
            'open'     => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id = ? AND status IN ('open','in_progress')", [$id]),
            'resolved' => (int)$this->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id = ? AND status IN ('resolved','closed')", [$id]),
            'companies'=> (int)$this->db->val('SELECT COUNT(*) FROM companies WHERE tenant_id = ?', [$id]),
            'kb'       => (int)$this->db->val('SELECT COUNT(*) FROM kb_articles WHERE tenant_id = ?', [$id]),
            'assets'   => (int)$this->db->val('SELECT COUNT(*) FROM assets WHERE tenant_id = ?', [$id]),
            'paid'     => (float)$this->db->val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE tenant_id = ? AND status='completed'", [$id]),
        ];
        $plans = $this->db->all('SELECT * FROM plans WHERE is_active=1 ORDER BY sort_order ASC');
        $license = License::status(new TenantModel($tenant));

        $this->render('admin/tenants/show', [
            'title' => $tenant['name'],
            'pageHeading' => $tenant['name'],
            't' => $tenant,
            'users' => $users,
            'subscription' => $subscription,
            'invoices' => $invoices,
            'payments' => $payments,
            'stats' => $stats,
            'plans' => $plans,
            'license' => $license,
        ]);
    }

    public function licenseActivate(array $params): void
    {
        $this->requireCan('subscriptions.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $tenant = $this->db->one('SELECT * FROM tenants WHERE id = ?', [$id]);
        if (!$tenant) $this->redirect('/admin/tenants');

        $cycle = (string)$this->input('cycle', 'monthly');
        $planId = (int)$this->input('plan_id', 0);
        $amount = $this->input('amount');
        $amount = $amount === null || $amount === '' ? null : (float)$amount;

        $sub = $this->db->one('SELECT * FROM subscriptions WHERE tenant_id = ? ORDER BY id DESC LIMIT 1', [$id]);
        $plan = $planId ? $this->db->one('SELECT * FROM plans WHERE id = ?', [$planId]) : null;
        if (!$plan && $sub) $plan = $this->db->one('SELECT * FROM plans WHERE id = ?', [$sub['plan_id']]);
        if (!$plan) $plan = License::defaultPlan();

        if (!$sub) {
            License::startTrialFor($id, $plan, 0);
            $sub = $this->db->one('SELECT * FROM subscriptions WHERE tenant_id = ? ORDER BY id DESC LIMIT 1', [$id]);
        } else if ($planId && $planId !== (int)$sub['plan_id']) {
            $this->db->update('subscriptions', ['plan_id' => $planId], 'id = :id', ['id' => $sub['id']]);
        }

        License::activate((int)$sub['id'], $cycle, $amount);
        $this->db->update('tenants', [
            'plan'         => $plan['slug'] ?? $tenant['plan'],
            'is_active'    => 1,
            'suspended_at' => null,
            'suspended_reason' => null,
        ], 'id = :id', ['id' => $id]);

        $this->superAuth->log('license.activate', 'tenant', $id, ['cycle' => $cycle, 'plan' => $plan['slug'] ?? null]);
        $this->session->flash('success', 'Licencia activada para ' . $tenant['name'] . '.');
        $this->redirect('/admin/tenants/' . $id);
    }

    public function licenseExtendTrial(array $params): void
    {
        $this->requireCan('subscriptions.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $days = max(1, (int)$this->input('days', 14));
        $tenant = $this->db->one('SELECT * FROM tenants WHERE id = ?', [$id]);
        if (!$tenant) $this->redirect('/admin/tenants');

        $sub = $this->db->one('SELECT * FROM subscriptions WHERE tenant_id = ? ORDER BY id DESC LIMIT 1', [$id]);
        if (!$sub) {
            License::startTrialFor($id, License::defaultPlan(), $days);
        } else {
            License::extendTrial((int)$sub['id'], $days);
        }
        $this->db->update('tenants', ['is_active' => 1, 'suspended_at' => null, 'suspended_reason' => null], 'id = :id', ['id' => $id]);

        $this->superAuth->log('license.extend_trial', 'tenant', $id, ['days' => $days]);
        $this->session->flash('success', "Prueba extendida {$days} días.");
        $this->redirect('/admin/tenants/' . $id);
    }

    public function licenseRevoke(array $params): void
    {
        $this->requireCan('subscriptions.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $sub = $this->db->one('SELECT * FROM subscriptions WHERE tenant_id = ? ORDER BY id DESC LIMIT 1', [$id]);
        if ($sub) {
            License::expire((int)$sub['id']);
        }
        $this->superAuth->log('license.revoke', 'tenant', $id);
        $this->session->flash('success', 'Licencia revocada. La organización quedará bloqueada hasta su reactivación.');
        $this->redirect('/admin/tenants/' . $id);
    }

    public function update(array $params): void
    {
        $this->requireCan('tenants.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $tenant = $this->db->one('SELECT * FROM tenants WHERE id = ?', [$id]);
        if (!$tenant) $this->redirect('/admin/tenants');

        $data = [
            'name' => trim((string)$this->input('name', $tenant['name'])),
            'support_email' => (string)$this->input('support_email', ''),
            'billing_email' => (string)$this->input('billing_email', ''),
            'website' => (string)$this->input('website', ''),
            'country' => (string)$this->input('country', ''),
            'timezone' => (string)$this->input('timezone', $tenant['timezone']),
            'plan' => (string)$this->input('plan', $tenant['plan']),
            'notes' => (string)$this->input('notes', ''),
        ];
        $this->db->update('tenants', $data, 'id = :id', ['id' => $id]);
        $this->superAuth->log('tenant.update', 'tenant', $id);
        $this->session->flash('success', 'Empresa actualizada.');
        $this->redirect('/admin/tenants/' . $id);
    }

    public function suspend(array $params): void
    {
        $this->requireCan('tenants.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $reason = trim((string)$this->input('reason', ''));
        $this->db->update('tenants', [
            'is_active' => 0,
            'suspended_at' => date('Y-m-d H:i:s'),
            'suspended_reason' => $reason,
        ], 'id = :id', ['id' => $id]);
        $this->db->update('subscriptions', ['status' => 'suspended'], 'tenant_id = :tid', ['tid' => $id]);
        $this->superAuth->log('tenant.suspend', 'tenant', $id, ['reason' => $reason]);
        $this->session->flash('success', 'Empresa suspendida.');
        $this->redirect('/admin/tenants/' . $id);
    }

    public function activate(array $params): void
    {
        $this->requireCan('tenants.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->update('tenants', [
            'is_active' => 1,
            'suspended_at' => null,
            'suspended_reason' => null,
        ], 'id = :id', ['id' => $id]);
        $this->db->update('subscriptions', ['status' => 'active'], 'tenant_id = :tid AND status = :s', ['tid' => $id, 's' => 'suspended']);
        $this->superAuth->log('tenant.activate', 'tenant', $id);
        $this->session->flash('success', 'Empresa reactivada.');
        $this->redirect('/admin/tenants/' . $id);
    }

    public function delete(array $params): void
    {
        $this->requireCan('tenants.delete');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $tenant = $this->db->one('SELECT * FROM tenants WHERE id = ?', [$id]);
        if ($tenant) {
            $this->db->delete('tenants', 'id = :id', ['id' => $id]);
            $this->superAuth->log('tenant.delete', 'tenant', $id, ['name' => $tenant['name']]);
            $this->session->flash('success', 'Empresa eliminada definitivamente.');
        }
        $this->redirect('/admin/tenants');
    }

    public function impersonate(array $params): void
    {
        $this->requireCan('tenants.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $tenant = $this->db->one('SELECT * FROM tenants WHERE id = ?', [$id]);
        if (!$tenant) {
            $this->session->flash('error', 'Empresa no encontrada.');
            $this->redirect('/admin/tenants');
        }
        $owner = $this->db->one(
            "SELECT u.* FROM users u JOIN roles r ON r.id = u.role_id WHERE u.tenant_id = ? AND r.slug = 'owner' AND u.is_active = 1 LIMIT 1",
            [$id]
        );
        if (!$owner) {
            $owner = $this->db->one('SELECT * FROM users WHERE tenant_id = ? AND is_active = 1 ORDER BY id ASC LIMIT 1', [$id]);
        }
        if (!$owner) {
            $this->session->flash('error', 'No hay usuario activo en esta empresa.');
            $this->redirect('/admin/tenants/' . $id);
        }
        $this->session->put('impersonating_from_super', $this->superAuth->id());
        $this->auth->login((int)$owner['id']);
        $this->superAuth->log('tenant.impersonate', 'tenant', $id, ['as_user' => $owner['email']]);
        $this->session->flash('success', "Accediendo como {$owner['name']} ({$tenant['name']})");
        $this->redirect('/t/' . $tenant['slug'] . '/dashboard');
    }
}
