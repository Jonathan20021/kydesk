<?php
namespace App\Core;

use PDO;

/**
 * Crea tenants demo on-demand para cada plan.
 * Los datos se eliminan automáticamente tras 24h vía DemoSeeder::cleanup().
 */
class DemoSeeder
{
    public const TTL_HOURS = 24;

    public const PLANS = [
        'starter' => [
            'label' => 'Starter',
            'tagline' => 'Para equipos que recién arrancan',
            'limits' => [
                'users' => 3,
                'tickets_per_month' => 100,
                'channels' => ['portal','email'],
                'automations' => 0,
                'sla' => 0,
                'audit' => 0,
                'audit_retention' => '7 días',
                'kb_articles' => 10,
                'api' => 0,
                'sso' => 0,
                'branding' => 0,
                'support' => 'Comunidad',
                'sla_guarantee' => '—',
                'success_manager' => 0,
                'data_residency' => 'US estándar',
            ],
            'sample_tickets' => 5,
            'sample_companies' => 2,
        ],
        'pro' => [
            'label' => 'Pro',
            'tagline' => 'Para equipos que escalan',
            'limits' => [
                'users' => 999,
                'tickets_per_month' => 99999,
                'channels' => ['portal','email','phone','chat','internal'],
                'automations' => 999,
                'sla' => 999,
                'audit' => 1,
                'audit_retention' => '30 días',
                'kb_articles' => 999,
                'api' => 1,
                'sso' => 0,
                'branding' => 0,
                'support' => 'Email · Lun-Vie',
                'sla_guarantee' => '99.9%',
                'success_manager' => 0,
                'data_residency' => 'US o EU',
            ],
            'sample_tickets' => 12,
            'sample_companies' => 5,
        ],
        'enterprise' => [
            'label' => 'Enterprise',
            'tagline' => 'Operación crítica · Compliance · Escala global',
            'limits' => [
                'users' => 9999,
                'tickets_per_month' => 999999,
                'channels' => ['portal','email','phone','chat','internal'],
                'automations' => 9999,
                'sla' => 9999,
                'audit' => 1,
                'audit_retention' => 'Indefinido',
                'kb_articles' => 9999,
                'api' => 1,
                'sso' => 1,
                'branding' => 1,
                'support' => '24/7 · Slack dedicado',
                'sla_guarantee' => '99.99%',
                'success_manager' => 1,
                'data_residency' => 'US · EU · LATAM',
            ],
            'sample_tickets' => 18,
            'sample_companies' => 8,
        ],
    ];

    public function __construct(protected Database $db) {}

    /**
     * Asegura que las columnas demo existen (auto-migración).
     */
    public function ensureSchema(): void
    {
        $row = $this->db->one("SHOW COLUMNS FROM tenants LIKE 'is_demo'");
        if (!$row) {
            $this->db->run("ALTER TABLE tenants ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0");
            $this->db->run("ALTER TABLE tenants ADD COLUMN demo_expires_at DATETIME NULL");
            $this->db->run("ALTER TABLE tenants ADD COLUMN demo_plan VARCHAR(20) NULL");
        }
    }

    /**
     * Crea un tenant demo del plan elegido y devuelve [tenant_id, user_id, slug, password].
     */
    public function create(string $plan): array
    {
        $this->ensureSchema();
        if (!isset(self::PLANS[$plan])) $plan = 'pro';
        $cfg = self::PLANS[$plan];

        $rand = substr(bin2hex(random_bytes(4)), 0, 6);
        $slug = 'demo-' . $plan . '-' . $rand;
        $name = 'Demo ' . $cfg['label'];
        $expires = date('Y-m-d H:i:s', strtotime('+' . self::TTL_HOURS . ' hours'));
        $passPlain = 'demo' . random_int(1000, 9999);

        $tenantPlan = match ($plan) { 'starter' => 'free', 'enterprise' => 'enterprise', default => 'pro' };

        $this->db->run(
            "INSERT INTO tenants (name, slug, support_email, plan, primary_color, is_demo, demo_expires_at, demo_plan)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?)",
            [$name, $slug, 'soporte@' . $slug . '.demo', $tenantPlan, '#7c5cff', $expires, $plan]
        );
        $tenantId = (int)$this->db->pdo()->lastInsertId();

        $rolesById = $this->seedRoles($tenantId);
        $userIds = $this->seedUsers($tenantId, $rolesById, $passPlain, $cfg, $slug);
        $catIds = $this->seedCategories($tenantId);
        $companyIds = $this->seedCompanies($tenantId, $cfg['sample_companies']);
        $this->seedSlas($tenantId, $cfg);
        $this->seedKb($tenantId, $userIds[0]);
        $this->seedAutomations($tenantId, $userIds, $cfg);
        $this->seedTickets($tenantId, $userIds, $catIds, $companyIds, $cfg['sample_tickets']);

        return [
            'tenant_id' => $tenantId,
            'user_id' => $userIds[0],
            'slug' => $slug,
            'email' => 'admin@' . $slug . '.demo',
            'password' => $passPlain,
            'plan' => $plan,
            'expires_at' => $expires,
        ];
    }

    /**
     * Borra todos los tenants demo expirados (datos en cascada + archivos subidos).
     */
    public function cleanup(): int
    {
        $this->ensureSchema();
        $expired = $this->db->all(
            "SELECT id, slug FROM tenants WHERE is_demo = 1 AND demo_expires_at IS NOT NULL AND demo_expires_at < NOW()"
        );
        foreach ($expired as $t) {
            $this->db->run("DELETE FROM tenants WHERE id = ?", [$t['id']]);
            $this->wipeUploads($t['slug'], (int)$t['id']);
        }
        return count($expired);
    }

    protected function wipeUploads(string $slug, int $tenantId): void
    {
        $candidates = [
            BASE_PATH . '/public/uploads/' . $slug,
            BASE_PATH . '/public/uploads/' . $tenantId,
            BASE_PATH . '/public/uploads/tenants/' . $slug,
        ];
        foreach ($candidates as $dir) {
            if (is_dir($dir)) $this->rmdirRecursive($dir);
        }
    }

    protected function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->rmdirRecursive($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    /* -------- Seeders internos -------- */

    protected function seedRoles(int $tenantId): array
    {
        $defs = [
            ['owner', 'Owner', 'Acceso total', 1],
            ['admin', 'Administrador', 'Gestión completa', 1],
            ['supervisor', 'Supervisor', 'Supervisa técnicos', 1],
            ['technician', 'Técnico', 'Atiende tickets', 1],
            ['agent', 'Agente', 'Crea tickets', 1],
        ];
        $byId = [];
        foreach ($defs as [$slug, $name, $desc, $sys]) {
            $this->db->run(
                "INSERT INTO roles (tenant_id, name, slug, description, is_system) VALUES (?,?,?,?,?)",
                [$tenantId, $name, $slug, $desc, $sys]
            );
            $byId[$slug] = (int)$this->db->pdo()->lastInsertId();
        }
        $perms = $this->db->all("SELECT id, slug FROM permissions");
        $bySlug = array_column($perms, 'id', 'slug');
        $assign = function (array $roleSlugs, int $roleId) use ($bySlug) {
            foreach ($roleSlugs as $ps) {
                if (!isset($bySlug[$ps])) continue;
                $this->db->run("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)", [$roleId, $bySlug[$ps]]);
            }
        };
        $assign(array_keys($bySlug), $byId['admin']);
        $assign(array_keys($bySlug), $byId['supervisor']);
        $assign(['tickets.view','tickets.create','tickets.edit','tickets.comment','notes.view','notes.create','notes.edit','todos.view','todos.create','todos.edit','kb.view','companies.view','assets.view','reports.view'], $byId['technician']);
        $assign(['tickets.view','tickets.create','tickets.comment','notes.view','notes.create','todos.view','todos.create','kb.view'], $byId['agent']);
        return $byId;
    }

    protected function seedUsers(int $tenantId, array $rolesById, string $pass, array $cfg, string $slug): array
    {
        $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
        $domain = '@' . $slug . '.demo';
        $base = [
            [$rolesById['owner'], 'Admin Demo', 'admin' . $domain, 'Owner del workspace', 0],
            [$rolesById['supervisor'], 'Sofía Supervisor', 'sofia' . $domain, 'Supervisor de Soporte', 1],
            [$rolesById['technician'], 'Marco Técnico', 'marco' . $domain, 'Soporte Nivel 2', 1],
            [$rolesById['technician'], 'Ana Ingeniería', 'ana' . $domain, 'Soporte Nivel 3', 1],
            [$rolesById['agent'], 'Luis Agente', 'luis' . $domain, 'Agente', 0],
        ];
        $cap = (int)($cfg['limits']['users'] ?? 5);
        $base = array_slice($base, 0, max($cap, 1));
        $ids = [];
        foreach ($base as [$rid, $name, $email, $title, $tech]) {
            $this->db->run(
                "INSERT INTO users (tenant_id, role_id, name, email, password, title, is_technician, is_active) VALUES (?,?,?,?,?,?,?,1)",
                [$tenantId, $rid, $name, $email, $hash, $title, $tech]
            );
            $ids[] = (int)$this->db->pdo()->lastInsertId();
        }
        return $ids;
    }

    protected function seedCategories(int $tenantId): array
    {
        $cats = [
            ['Hardware', '#f59e0b', 'cpu'],
            ['Software', '#3b82f6', 'package'],
            ['Red e infraestructura', '#10b981', 'wifi'],
            ['Cuentas y accesos', '#ec4899', 'key-round'],
            ['Otros', '#6b7280', 'folder'],
        ];
        $ids = [];
        foreach ($cats as [$n, $c, $i]) {
            $this->db->run("INSERT INTO ticket_categories (tenant_id, name, color, icon) VALUES (?,?,?,?)", [$tenantId, $n, $c, $i]);
            $ids[] = (int)$this->db->pdo()->lastInsertId();
        }
        return $ids;
    }

    protected function seedCompanies(int $tenantId, int $count): array
    {
        $set = [
            ['Acme Corp', 'Tecnología', '500-1000', 'enterprise'],
            ['Globex Inc', 'Manufactura', '200-500', 'premium'],
            ['Initech', 'Software', '50-200', 'premium'],
            ['Stark Labs', 'I+D', '1000+', 'enterprise'],
            ['Wayne Ent', 'Conglomerado', '1000+', 'enterprise'],
            ['Massive Dyn', 'Investigación', '500-1000', 'premium'],
            ['Tyrell Co', 'Biotech', '200-500', 'premium'],
            ['Cyberdyne', 'Robótica', '500-1000', 'enterprise'],
        ];
        $ids = [];
        foreach (array_slice($set, 0, $count) as [$n, $ind, $sz, $tier]) {
            $this->db->run("INSERT INTO companies (tenant_id, name, industry, size, website, tier) VALUES (?,?,?,?,?,?)", [$tenantId, $n, $ind, $sz, 'https://' . strtolower(str_replace(' ', '', $n)) . '.demo', $tier]);
            $ids[] = (int)$this->db->pdo()->lastInsertId();
        }
        return $ids;
    }

    protected function seedSlas(int $tenantId, array $cfg): void
    {
        if (($cfg['limits']['sla'] ?? 0) <= 0) return;
        foreach ([['Urgente < 15 min', 'urgent', 15, 240], ['Alta < 1 h', 'high', 60, 480], ['Media < 4 h', 'medium', 240, 1440], ['Baja < 1 día', 'low', 480, 4320]] as [$n, $p, $r, $res]) {
            $this->db->run("INSERT INTO sla_policies (tenant_id, name, priority, response_minutes, resolve_minutes, active) VALUES (?,?,?,?,?,1)", [$tenantId, $n, $p, $r, $res]);
        }
    }

    protected function seedKb(int $tenantId, int $authorId): void
    {
        $cats = [['Primeros pasos', 'rocket', '#0ea5e9', ''], ['Red', 'wifi', '#10b981', ''], ['Cuentas', 'lock', '#ec4899', '']];
        $catIds = [];
        foreach ($cats as [$n, $i, $c, $d]) {
            $this->db->run("INSERT INTO kb_categories (tenant_id, name, icon, color, description) VALUES (?,?,?,?,?)", [$tenantId, $n, $i, $c, $d]);
            $catIds[] = (int)$this->db->pdo()->lastInsertId();
        }
        $articles = [
            [0, 'Cómo abrir tu primer ticket', 'primer-ticket', 'Guía rápida para empezar', "# Empezando\n\nCrea un ticket desde el panel.", 'published', 'public'],
            [1, 'Solucionar VPN intermitente', 'vpn-intermitente', 'Diagnóstico VPN', "# VPN\n\n1. Verifica red\n2. Actualiza cliente", 'published', 'public'],
            [2, 'Restablecer contraseña', 'reset-password', 'Recuperar acceso', "# Reset\n\nUsa el portal.", 'published', 'public'],
        ];
        foreach ($articles as [$ci, $t, $s, $ex, $bd, $st, $vis]) {
            $this->db->run(
                "INSERT INTO kb_articles (tenant_id, category_id, author_id, title, slug, excerpt, body, status, visibility, views, helpful_yes, helpful_no, published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$tenantId, $catIds[$ci], $authorId, $t, $s, $ex, $bd, $st, $vis, random_int(50, 600), random_int(5, 40), 0, date('Y-m-d H:i:s', strtotime('-' . random_int(1, 30) . ' days'))]
            );
        }
    }

    protected function seedAutomations(int $tenantId, array $userIds, array $cfg): void
    {
        if (($cfg['limits']['automations'] ?? 0) <= 0) return;
        $auto = [
            ['Auto-asignar urgentes', 'Urgentes a Marco', 'ticket.created', '{"priority":"urgent"}', '{"assign_to":' . $userIds[2] . '}', 1],
            ['Cerrar resueltos +7d', 'Cierra tickets viejos', 'ticket.resolved', '{"days":7}', '{"status":"closed"}', 1],
        ];
        foreach ($auto as [$n, $d, $tr, $cn, $ac, $ai]) {
            $this->db->run(
                "INSERT INTO automations (tenant_id, name, description, trigger_event, conditions, actions, active, run_count, last_run_at, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)",
                [$tenantId, $n, $d, $tr, $cn, $ac, $ai, random_int(5, 50), date('Y-m-d H:i:s', strtotime('-' . random_int(1, 24) . ' hours')), $userIds[0]]
            );
        }
    }

    protected function seedTickets(int $tenantId, array $userIds, array $catIds, array $companyIds, int $count): void
    {
        $samples = [
            ['No puedo acceder al correo corporativo', 'high', 'open', 'portal', 3],
            ['Impresora del 3er piso offline', 'medium', 'in_progress', 'phone', 0],
            ['Solicitud de laptop para nuevo ingreso', 'low', 'open', 'email', 3],
            ['VPN se desconecta cada 10 minutos', 'urgent', 'in_progress', 'chat', 2],
            ['Error 500 al cargar reporte mensual', 'high', 'on_hold', 'portal', 1],
            ['Capacitación Office 365', 'low', 'resolved', 'email', 4],
            ['Instalación Photoshop en 3 máquinas', 'medium', 'open', 'portal', 1],
            ['SSO falla desde móviles', 'urgent', 'open', 'email', 3],
            ['Backup no se ejecutó', 'high', 'in_progress', 'internal', 2],
            ['Renovar licencias antivirus', 'medium', 'open', 'internal', 4],
            ['Acceso a carpeta RRHH', 'low', 'resolved', 'portal', 3],
            ['Pantalla azul en contable', 'high', 'in_progress', 'phone', 0],
            ['Configurar nuevo correo grupal', 'low', 'open', 'email', 3],
            ['DNS interno caído', 'urgent', 'resolved', 'phone', 2],
            ['Migración de servidor de archivos', 'medium', 'open', 'internal', 1],
            ['Crear cuenta para proveedor', 'low', 'open', 'portal', 3],
            ['Mouse defectuoso', 'low', 'resolved', 'email', 0],
            ['Bloqueo de Excel macros', 'medium', 'on_hold', 'portal', 1],
        ];
        $assignedRotation = [null, 2, null, 2, 3, 2, 2, 3, 2, null, 2, 3, 1, 2, null, 3, 0, 1];
        foreach (array_slice($samples, 0, $count) as $i => [$subj, $pri, $st, $ch, $catIdx]) {
            $token = bin2hex(random_bytes(16));
            $assignedIdx = $assignedRotation[$i % count($assignedRotation)] ?? null;
            $assignedTo = $assignedIdx !== null && isset($userIds[$assignedIdx]) ? $userIds[$assignedIdx] : null;
            $companyId = $companyIds ? $companyIds[$i % count($companyIds)] : null;
            $daysAgo = random_int(0, 14);
            $createdAt = date('Y-m-d H:i:s', strtotime("-$daysAgo days -" . random_int(0, 23) . " hours"));
            $resolvedAt = in_array($st, ['resolved', 'closed']) ? date('Y-m-d H:i:s', strtotime($createdAt . ' +' . random_int(2, 48) . ' hours')) : null;
            $slaHours = $pri === 'urgent' ? 4 : ($pri === 'high' ? 24 : 72);
            $code = 'TK-' . str_pad((string)$tenantId, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string)($i + 1), 5, '0', STR_PAD_LEFT);
            $this->db->run(
                "INSERT INTO tickets (tenant_id, code, subject, description, category_id, company_id, priority, status, channel, requester_name, requester_email, assigned_to, created_by, public_token, escalation_level, sla_due_at, resolved_at, created_at, first_response_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $tenantId, $code, $subj, "Descripción de ejemplo del ticket «$subj».",
                    $catIds[$catIdx] ?? null, $companyId, $pri, $st, $ch,
                    'Cliente Demo', 'demo' . ($i + 1) . '@example.com',
                    $assignedTo, $userIds[0], $token,
                    $pri === 'urgent' ? 1 : 0,
                    date('Y-m-d H:i:s', strtotime($createdAt . " +$slaHours hours")),
                    $resolvedAt, $createdAt,
                    $assignedTo ? date('Y-m-d H:i:s', strtotime($createdAt . ' +' . random_int(5, 120) . ' minutes')) : null,
                ]
            );
        }
    }
}
