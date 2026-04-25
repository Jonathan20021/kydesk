<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Helpers;
use PDO;

class InstallController extends Controller
{
    public function index(): void
    {
        $status = $this->status();
        $this->render('install/index', compact('status'), 'public');
    }

    public function run(): void
    {
        $this->validateCsrf();
        $cfg = $this->app->config['db'];
        $out = [];

        try {
            $pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $out[] = "✓ Base de datos `{$cfg['name']}` lista.";

            $pdo->exec("USE `{$cfg['name']}`");
            $schema = file_get_contents(BASE_PATH . '/database/schema.sql');
            $pdo->exec($schema);
            $out[] = "✓ 22 tablas creadas.";

            $permissions = $this->permissionCatalog();
            foreach ($permissions as $p) {
                $pdo->prepare('INSERT INTO permissions (slug, module, label) VALUES (?,?,?)')
                    ->execute([$p['slug'], $p['module'], $p['label']]);
            }
            $out[] = "✓ " . count($permissions) . " permisos registrados.";

            $slug = 'demo';
            $pdo->prepare('INSERT INTO tenants (name, slug, support_email, website, plan, primary_color) VALUES (?,?,?,?,?,?)')
                ->execute(['Demo Organization', $slug, 'soporte@demo.com', 'https://demo.com', 'pro', '#0f172a']);
            $tenantId = (int)$pdo->lastInsertId();
            $out[] = "✓ Tenant demo creado (/t/demo).";

            $roles = [
                ['owner','Owner','Acceso total a la organización',1],
                ['admin','Administrador','Gestiona usuarios, roles y toda la operación',1],
                ['supervisor','Supervisor','Supervisa técnicos y reportes',1],
                ['technician','Técnico','Atiende y resuelve tickets',1],
                ['agent','Agente','Recibe y crea tickets',1],
            ];
            $rolesById = [];
            foreach ($roles as [$rslug, $rname, $rdesc, $rsys]) {
                $pdo->prepare('INSERT INTO roles (tenant_id, name, slug, description, is_system) VALUES (?,?,?,?,?)')
                    ->execute([$tenantId, $rname, $rslug, $rdesc, $rsys]);
                $rolesById[$rslug] = (int)$pdo->lastInsertId();
            }
            $out[] = "✓ " . count($roles) . " roles creados.";

            $allPerms = $pdo->query('SELECT id, slug FROM permissions')->fetchAll(PDO::FETCH_ASSOC);
            $permBySlug = [];
            foreach ($allPerms as $p) $permBySlug[$p['slug']] = $p['id'];

            $permissionMap = [
                'admin' => array_keys($permBySlug),
                'supervisor' => array_merge(
                    $this->module('tickets', ['view','create','edit','assign','escalate','comment']),
                    $this->module('notes', ['view','create','edit','delete']),
                    $this->module('todos', ['view','create','edit','delete']),
                    $this->module('kb', ['view','create','edit']),
                    $this->module('companies', ['view','create','edit']),
                    $this->module('assets', ['view','create','edit']),
                    $this->module('automations', ['view']),
                    $this->module('sla', ['view']),
                    $this->module('users', ['view']),
                    $this->module('roles', ['view']),
                    $this->module('reports', ['view']),
                    $this->module('audit', ['view'])
                ),
                'technician' => array_merge(
                    $this->module('tickets', ['view','create','edit','comment','escalate']),
                    $this->module('notes', ['view','create','edit','delete']),
                    $this->module('todos', ['view','create','edit','delete']),
                    $this->module('kb', ['view','create']),
                    $this->module('companies', ['view']),
                    $this->module('assets', ['view']),
                    $this->module('reports', ['view'])
                ),
                'agent' => array_merge(
                    $this->module('tickets', ['view','create','comment']),
                    $this->module('notes', ['view','create','edit','delete']),
                    $this->module('todos', ['view','create','edit','delete']),
                    $this->module('kb', ['view'])
                ),
            ];
            foreach ($permissionMap as $rslug => $slugs) {
                foreach ($slugs as $slugP) {
                    if (!isset($permBySlug[$slugP])) continue;
                    $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)')
                        ->execute([$rolesById[$rslug], $permBySlug[$slugP]]);
                }
            }
            $out[] = "✓ Permisos asignados a roles.";

            $passOwner = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
            $passTech = password_hash('tech123', PASSWORD_BCRYPT, ['cost' => 12]);

            $users = [
                [$rolesById['owner'], 'Admin Demo', 'admin@demo.com', $passOwner, 'Fundador & CEO', 0],
                [$rolesById['supervisor'], 'Juan Supervisor', 'supervisor@demo.com', $passTech, 'Supervisor de Soporte', 1],
                [$rolesById['technician'], 'María Técnica', 'tecnico@demo.com', $passTech, 'Soporte Nivel 2', 1],
                [$rolesById['technician'], 'Carlos Ingeniería', 'carlos@demo.com', $passTech, 'Soporte Nivel 3', 1],
                [$rolesById['agent'], 'Ana García', 'ana@demo.com', $passTech, 'Agente de soporte', 0],
            ];
            $userIds = [];
            foreach ($users as $u) {
                $pdo->prepare('INSERT INTO users (tenant_id, role_id, name, email, password, title, is_technician, is_active) VALUES (?,?,?,?,?,?,?,1)')
                    ->execute([$tenantId, ...$u]);
                $userIds[] = (int)$pdo->lastInsertId();
            }
            $out[] = "✓ 5 usuarios demo creados.";

            $companies = [
                ['Acme Corporation', 'Tecnología', '500-1000', 'https://acme.com', 'enterprise'],
                ['Globex Industries', 'Manufactura', '200-500', 'https://globex.com', 'premium'],
                ['Initech Solutions', 'Software', '50-200', 'https://initech.com', 'premium'],
                ['Stark Labs', 'I+D', '1000+', 'https://stark.io', 'enterprise'],
                ['Wayne Enterprises', 'Conglomerado', '1000+', 'https://wayne.corp', 'enterprise'],
            ];
            $companyIds = [];
            foreach ($companies as [$n, $ind, $sz, $web, $tier]) {
                $pdo->prepare('INSERT INTO companies (tenant_id, name, industry, size, website, tier) VALUES (?,?,?,?,?,?)')
                    ->execute([$tenantId, $n, $ind, $sz, $web, $tier]);
                $companyIds[] = (int)$pdo->lastInsertId();
            }
            $out[] = "✓ " . count($companies) . " empresas clientes.";

            foreach ([['Laura Martínez','laura@acme.com', 0, 'CTO'],['Pedro Ruiz','pedro@globex.com',1,'IT Manager'],['Sara Kim','sara@initech.com',2,'Head of Ops']] as [$cn,$ce,$ci,$ct]) {
                $pdo->prepare('INSERT INTO contacts (tenant_id, company_id, name, email, title) VALUES (?,?,?,?,?)')
                    ->execute([$tenantId, $companyIds[$ci], $cn, $ce, $ct]);
            }

            $cats = [
                ['Hardware', '#f59e0b', 'cpu'],
                ['Software', '#3b82f6', 'package'],
                ['Red e infraestructura', '#10b981', 'wifi'],
                ['Cuentas y accesos', '#ec4899', 'key-round'],
                ['Seguridad', '#ef4444', 'shield-alert'],
                ['Otros', '#6b7280', 'folder'],
            ];
            $catIds = [];
            foreach ($cats as [$n, $c, $i]) {
                $pdo->prepare('INSERT INTO ticket_categories (tenant_id, name, color, icon) VALUES (?,?,?,?)')
                    ->execute([$tenantId, $n, $c, $i]);
                $catIds[] = (int)$pdo->lastInsertId();
            }
            $out[] = "✓ " . count($cats) . " categorías.";

            // SLA policies
            $slas = [
                ['Urgente < 15 min', 'urgent', 15, 240],
                ['Alta < 1 h', 'high', 60, 480],
                ['Media < 4 h', 'medium', 240, 1440],
                ['Baja < 1 día', 'low', 480, 4320],
            ];
            foreach ($slas as [$n, $p, $r, $res]) {
                $pdo->prepare('INSERT INTO sla_policies (tenant_id, name, priority, response_minutes, resolve_minutes, active) VALUES (?,?,?,?,?,1)')
                    ->execute([$tenantId, $n, $p, $r, $res]);
            }
            $out[] = "✓ 4 políticas SLA.";

            // Assets
            $assets = [
                ['MacBook Pro 14" - Admin', 'laptop', 'MBP001', 'MacBook Pro M3', 'active', 0, 0],
                ['Dell Latitude 7490', 'laptop', 'DL7490A1', 'Latitude 7490', 'active', 2, 1],
                ['iPhone 15 Pro - Soporte', 'phone', 'IP15SUP', 'iPhone 15 Pro', 'active', 1, 0],
                ['Impresora HP LaserJet', 'printer', 'HPLJ901', 'LaserJet Pro', 'maintenance', null, 2],
                ['Firewall Fortinet 60F', 'network', 'FG60F001', 'FortiGate 60F', 'active', null, null],
                ['Monitor Dell 27" UHD', 'monitor', 'DLUHD27', 'U2723QE', 'active', 3, 1],
            ];
            foreach ($assets as [$n, $t, $s, $m, $st, $ui, $ci]) {
                $pdo->prepare('INSERT INTO assets (tenant_id, company_id, name, type, serial, model, status, assigned_to, purchase_date) VALUES (?,?,?,?,?,?,?,?,?)')
                    ->execute([$tenantId, $ci !== null ? $companyIds[$ci] : null, $n, $t, $s, $m, $st, $ui !== null ? $userIds[$ui] : null, date('Y-m-d', strtotime('-' . rand(100,800) . ' days'))]);
            }
            $out[] = "✓ " . count($assets) . " activos (assets).";

            // Knowledge base categories + articles
            $kbCats = [
                ['Primeros pasos', 'rocket', '#0ea5e9', 'Onboarding y configuración inicial'],
                ['Red & conectividad', 'wifi', '#10b981', 'VPN, DNS, conexiones'],
                ['Cuentas & accesos', 'lock', '#ec4899', 'Contraseñas, SSO, permisos'],
                ['Hardware', 'monitor', '#f59e0b', 'Equipos, periféricos'],
                ['Software', 'package', '#6366f1', 'Instalación, licencias, updates'],
            ];
            $kbCatIds = [];
            foreach ($kbCats as [$n, $i, $c, $d]) {
                $pdo->prepare('INSERT INTO kb_categories (tenant_id, name, icon, color, description) VALUES (?,?,?,?,?)')
                    ->execute([$tenantId, $n, $i, $c, $d]);
                $kbCatIds[] = (int)$pdo->lastInsertId();
            }

            $kbArticles = [
                [0, 'Cómo configurar tu primer ticket', 'como-configurar-tu-primer-ticket', 'Guía paso a paso para comenzar a usar el helpdesk.', "# Empezando con tickets\n\nCrea un ticket desde el portal o desde el panel.\n\n## Campos obligatorios\n- Asunto\n- Descripción\n- Prioridad\n\n## Buenas prácticas\nDescribe el problema con detalle e incluye capturas si es posible.", 'published', 'public'],
                [1, 'Solucionar desconexiones de VPN', 'solucionar-desconexiones-vpn', 'Pasos para diagnosticar caídas frecuentes de VPN.', "# VPN se desconecta\n\n1. Verifica tu red local\n2. Actualiza el cliente VPN\n3. Revisa logs del firewall\n4. Escala a N2 si persiste", 'published', 'public'],
                [2, 'Restablecer contraseña corporativa', 'restablecer-contrasena-corporativa', 'Cómo recuperar acceso a tu cuenta.', "# Reset de contraseña\n\nUsa el portal de autoservicio o solicita un ticket.", 'published', 'public'],
                [3, 'Lista de equipos estándar para nuevo ingreso', 'lista-equipos-estandar', 'Kit inicial para nuevos colaboradores.', "# Kit de onboarding\n\n- Laptop\n- Monitor externo\n- Teclado + mouse\n- Audífonos\n- Cargador USB-C", 'published', 'internal'],
                [4, 'Instalación de Office 365', 'instalacion-office-365', 'Descarga, instalación y activación.', "# Office 365\n\nAccede a office.com con tu cuenta corporativa.", 'draft', 'internal'],
            ];
            foreach ($kbArticles as $i => [$catIdx, $t, $sl, $ex, $bd, $st, $vis]) {
                $pdo->prepare('INSERT INTO kb_articles (tenant_id, category_id, author_id, title, slug, excerpt, body, status, visibility, views, helpful_yes, helpful_no, published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$tenantId, $kbCatIds[$catIdx], $userIds[0], $t, $sl, $ex, $bd, $st, $vis, rand(50,1200), rand(10,80), rand(0,5), $st==='published' ? date('Y-m-d H:i:s', strtotime('-'.rand(1,60).' days')) : null]);
            }
            $out[] = "✓ " . count($kbCats) . " categorías y " . count($kbArticles) . " artículos KB.";

            // Automations
            $automations = [
                ['Auto-asignar urgentes a N2', 'Asigna tickets urgentes directamente a María Técnica', 'ticket.created', '{"priority":"urgent"}', '{"assign_to":' . $userIds[2] . '}', 1],
                ['Escalar si SLA en riesgo', 'Cuando queda <20% del SLA, escalar a supervisor', 'ticket.sla_breach', '{"threshold":"80%"}', '{"escalate_to":' . $userIds[1] . '}', 1],
                ['Cerrar tickets resueltos >7 días', 'Cierra automáticamente tickets resueltos sin respuesta', 'ticket.resolved', '{"days_since_resolved":7}', '{"status":"closed"}', 1],
                ['Notificar tickets VIP', 'Email al supervisor para clientes enterprise', 'ticket.created', '{"company_tier":"enterprise"}', '{"notify":"supervisor@demo.com"}', 0],
            ];
            foreach ($automations as [$n,$d,$trig,$cond,$act,$active]) {
                $pdo->prepare('INSERT INTO automations (tenant_id, name, description, trigger_event, conditions, actions, active, run_count, last_run_at, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$tenantId, $n, $d, $trig, $cond, $act, $active, rand(5,120), date('Y-m-d H:i:s', strtotime('-'.rand(1,30).' hours')), $userIds[0]]);
            }
            $out[] = "✓ " . count($automations) . " automatizaciones.";

            // Tickets
            $sampleTickets = [
                ['No puedo acceder al correo corporativo', 'El sistema muestra "contraseña incorrecta" tras el cambio de política.', 'high', 'open', 'portal', 3, 0, 0],
                ['Impresora del 3er piso no responde', 'Lleva offline toda la mañana, ya reiniciamos la cola.', 'medium', 'in_progress', 'phone', 0, 1, 3],
                ['Solicitud de nueva laptop para nuevo ingreso', 'Nuevo colaborador inicia el lunes, necesita equipo estándar.', 'low', 'open', 'email', 3, 2, null],
                ['VPN se desconecta cada 10 minutos', 'Usuario reporta caídas frecuentes desde casa. Crítico.', 'urgent', 'in_progress', 'chat', 2, 0, null],
                ['Error 500 al cargar reporte mensual', 'El reporte de ventas truena al generar PDF.', 'high', 'on_hold', 'portal', 1, 3, null],
                ['Capacitación Office 365', 'Equipo quiere sesión de Teams avanzado.', 'low', 'resolved', 'email', 5, 4, null],
                ['Instalación Photoshop en 3 máquinas', 'Departamento de marketing solicita suite Adobe CC.', 'medium', 'open', 'portal', 1, 0, null],
                ['SSO falla desde dispositivos móviles', 'No se puede iniciar sesión desde iOS, Android funciona.', 'urgent', 'open', 'email', 3, 1, null],
                ['Backup no se ejecutó anoche', 'Servidor de archivos no hizo snapshot programado.', 'high', 'in_progress', 'internal', 2, null, 4],
                ['Renovar licencias antivirus', 'Se vencen este mes, necesitamos cotización y renovación.', 'medium', 'open', 'internal', 4, null, null],
                ['Acceso a carpeta compartida RRHH', 'Solicitud de acceso para nuevo colaborador.', 'low', 'resolved', 'portal', 3, 0, null],
                ['Pantalla azul en estación contable', 'Error recurrente, ya reemplazamos RAM sin éxito.', 'high', 'in_progress', 'phone', 0, 1, 1],
            ];

            foreach ($sampleTickets as $i => [$subj, $desc, $pri, $st, $ch, $catIdx, $compIdx, $assetIdx]) {
                $token = bin2hex(random_bytes(16));
                $assignedIdx = [null, 2, null, 2, 3, 2, 2, 3, 2, null, 2, 3][$i % 12] ?? null;
                $daysAgo = rand(0, 15);
                $createdAt = date('Y-m-d H:i:s', strtotime("-$daysAgo days -" . rand(0,23) . " hours"));
                $resolvedAt = in_array($st, ['resolved','closed']) ? date('Y-m-d H:i:s', strtotime($createdAt . " +" . rand(2,48) . " hours")) : null;

                $pdo->prepare('INSERT INTO tickets (tenant_id, code, subject, description, category_id, company_id, asset_id, priority, status, channel, requester_name, requester_email, assigned_to, created_by, public_token, escalation_level, sla_due_at, resolved_at, created_at, first_response_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([
                        $tenantId,
                        'TMP-' . uniqid(),
                        $subj, $desc,
                        $catIds[$catIdx] ?? null,
                        $compIdx !== null ? $companyIds[$compIdx] : null,
                        null,
                        $pri, $st, $ch,
                        'Cliente ' . ucfirst(['Acme','Globex','Initech','Stark','Wayne'][$compIdx ?? 0]),
                        'cliente' . ($i+1) . '@example.com',
                        $assignedIdx !== null ? $userIds[$assignedIdx] : null,
                        $userIds[0],
                        $token,
                        $pri === 'urgent' ? 1 : 0,
                        date('Y-m-d H:i:s', strtotime($createdAt . " +" . ($pri==='urgent'?4:($pri==='high'?24:72)) . " hours")),
                        $resolvedAt,
                        $createdAt,
                        $assignedIdx ? date('Y-m-d H:i:s', strtotime($createdAt . " +" . rand(5,120) . " minutes")) : null,
                    ]);
                $tid = (int)$pdo->lastInsertId();
                $pdo->prepare('UPDATE tickets SET code = ? WHERE id = ?')
                    ->execute([Helpers::ticketCode($tenantId, $tid), $tid]);

                if ($assignedIdx !== null) {
                    $pdo->prepare('INSERT INTO ticket_comments (tenant_id, ticket_id, user_id, author_name, author_email, body, is_internal) VALUES (?,?,?,?,?,?,0)')
                        ->execute([$tenantId, $tid, $userIds[$assignedIdx], $users[$assignedIdx][1], $users[$assignedIdx][2], "Hola, estoy revisando tu caso. Te responderé en breve.", ]);
                }
            }
            $out[] = "✓ " . count($sampleTickets) . " tickets de ejemplo con comentarios.";

            // Notas
            $notes = [
                ['Bienvenida al helpdesk', "Este es tu espacio de notas personales.\n\nUsa esto para:\n- Guardar información útil\n- Plantillas de respuesta\n- Recordatorios\n\nTodo es privado para ti.", 'yellow', 1, 'onboarding,importante'],
                ['Contactos de proveedores', "**Telecom ACME**: soporte@acme.net - +502 1234-5678\n**Proveedor VPN**: help@vpn.io - +1 800 VPN-HELP\n**Antivirus**: renew@av.com", 'blue', 1, 'contactos,proveedores'],
                ['Plantilla: cierre de ticket', "Hola {{cliente}},\n\nHemos resuelto tu caso. Si el problema persiste, no dudes en contactarnos.\n\nSaludos,\n{{tecnico}}", 'green', 0, 'plantillas'],
                ['Credenciales del servidor (referencia)', "Usa el gestor de contraseñas del equipo, NUNCA guardes contraseñas aquí.", 'red', 0, 'seguridad'],
            ];
            foreach ($notes as [$t,$b,$c,$p,$tg]) {
                $pdo->prepare('INSERT INTO notes (tenant_id, user_id, title, body, color, pinned, tags) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$tenantId, $userIds[0], $t, $b, $c, $p, $tg]);
            }
            $out[] = "✓ 4 notas iniciales.";

            // Todos
            $pdo->prepare('INSERT INTO todo_lists (tenant_id, user_id, name, color, icon) VALUES (?,?,?,?,?)')
                ->execute([$tenantId, $userIds[0], 'Bandeja', '#0f172a', 'inbox']);
            $listId = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO todo_lists (tenant_id, user_id, name, color, icon) VALUES (?,?,?,?,?)')
                ->execute([$tenantId, $userIds[0], 'Proyectos Q2', '#3b82f6', 'target']);
            $pdo->prepare('INSERT INTO todo_lists (tenant_id, user_id, name, color, icon) VALUES (?,?,?,?,?)')
                ->execute([$tenantId, $userIds[0], 'Seguimiento técnico', '#10b981', 'wrench']);

            $tasks = [
                ['Revisar tickets urgentes de la mañana', 'urgent', 0],
                ['Agendar reunión de equipo viernes', 'medium', 0],
                ['Actualizar plantillas de respuestas', 'low', 0],
                ['Revisar métricas SLA del mes', 'high', 0],
                ['Renovar licencias antivirus', 'medium', 1],
                ['Migración de base de conocimiento', 'high', 1],
                ['Documentar nuevos procesos', 'low', 1],
            ];
            foreach ($tasks as [$t, $p, $done]) {
                $pdo->prepare('INSERT INTO todos (tenant_id, user_id, list_id, title, priority, completed, completed_at) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$tenantId, $userIds[0], $listId, $t, $p, $done, $done ? date('Y-m-d H:i:s') : null]);
            }
            $out[] = "✓ 3 listas y " . count($tasks) . " tareas.";

            $this->session->flash('success', 'Instalación completada.');
            $this->render('install/done', ['out' => $out], 'public');
        } catch (\Throwable $e) {
            $out[] = '✗ ERROR: ' . $e->getMessage();
            $this->render('install/done', ['out' => $out, 'error' => $e->getMessage()], 'public');
        }
    }

    protected function status(): array
    {
        try {
            $this->db->pdo();
            $hasUsers = $this->db->val("SELECT COUNT(*) FROM users") ?? 0;
            return ['connected' => true, 'seeded' => $hasUsers > 0];
        } catch (\Throwable $e) {
            return ['connected' => false, 'seeded' => false, 'error' => $e->getMessage()];
        }
    }

    protected function module(string $mod, array $actions): array
    {
        return array_map(fn($a) => "$mod.$a", $actions);
    }

    protected function permissionCatalog(): array
    {
        $modules = [
            'dashboard' => ['view' => 'Ver dashboard'],
            'tickets' => [
                'view' => 'Ver tickets',
                'create' => 'Crear tickets',
                'edit' => 'Editar tickets',
                'delete' => 'Eliminar tickets',
                'assign' => 'Asignar tickets',
                'escalate' => 'Escalar tickets',
                'comment' => 'Comentar tickets',
            ],
            'notes' => ['view'=>'Ver notas','create'=>'Crear notas','edit'=>'Editar notas','delete'=>'Eliminar notas'],
            'todos' => ['view'=>'Ver tareas','create'=>'Crear tareas','edit'=>'Editar tareas','delete'=>'Eliminar tareas'],
            'kb' => ['view'=>'Ver base de conocimiento','create'=>'Crear artículos','edit'=>'Editar artículos','delete'=>'Eliminar artículos'],
            'companies' => ['view'=>'Ver empresas','create'=>'Crear empresas','edit'=>'Editar empresas','delete'=>'Eliminar empresas'],
            'assets' => ['view'=>'Ver activos','create'=>'Crear activos','edit'=>'Editar activos','delete'=>'Eliminar activos'],
            'automations' => ['view'=>'Ver automatizaciones','create'=>'Crear automatizaciones','edit'=>'Editar automatizaciones','delete'=>'Eliminar automatizaciones'],
            'sla' => ['view'=>'Ver SLA','edit'=>'Editar SLA'],
            'audit' => ['view'=>'Ver auditoría'],
            'users' => ['view'=>'Ver usuarios','create'=>'Crear usuarios','edit'=>'Editar usuarios','delete'=>'Eliminar usuarios'],
            'roles' => ['view'=>'Ver roles','create'=>'Crear roles','edit'=>'Editar roles','delete'=>'Eliminar roles'],
            'reports' => ['view'=>'Ver reportes'],
            'settings' => ['view'=>'Ver ajustes','edit'=>'Editar ajustes'],
        ];
        $out = [];
        foreach ($modules as $mod => $actions) {
            foreach ($actions as $act => $label) {
                $out[] = ['slug' => "$mod.$act", 'module' => $mod, 'label' => $label];
            }
        }
        return $out;
    }
}
