<?php
/**
 * Igualas v2 — extensión completa configurable
 *
 *  · Tabla `retainer_categories` — tipos de iguala configurables por tenant
 *      (Soporte TI, Desarrollo de software, Sistemas, Marketing, Legal, etc.)
 *  · Tabla `retainer_items` — line items de cada iguala (entregables, horas por
 *      categoría, módulos cubiertos, productos, etc.)
 *  · Tabla `retainer_templates` — plantillas reutilizables con items pre-cargados
 *  · Tabla `retainer_template_items` — items de cada plantilla
 *  · Columnas adicionales en `retainers`: category_id, tax_pct, payment_terms,
 *      response_sla_minutes, resolve_sla_minutes, custom_fields (JSON)
 *  · Seed de categorías por defecto en tenants existentes
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_RETAINERS_V2') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected · {$cfg['host']} → {$cfg['name']}\n\n";

function tableExists2(PDO $pdo, string $t): bool {
    return (bool)$pdo->query("SHOW TABLES LIKE " . $pdo->quote($t))->fetch();
}
function columnExists2(PDO $pdo, string $t, string $c): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$pdo->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}

/* ───────── 1) retainer_categories ───────── */
if (!tableExists2($pdo, 'retainer_categories')) {
    $pdo->exec("CREATE TABLE retainer_categories (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        slug VARCHAR(80) NOT NULL,
        name VARCHAR(120) NOT NULL,
        description TEXT NULL,
        icon VARCHAR(40) NOT NULL DEFAULT 'briefcase',
        color VARCHAR(20) NOT NULL DEFAULT '#10b981',
        default_unit ENUM('hour','ticket','user','license','project','month','custom') NOT NULL DEFAULT 'hour',
        default_unit_label VARCHAR(40) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_tenant_slug (tenant_id, slug),
        KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + retainer_categories\n";
} else {
    echo "  • retainer_categories ya existe\n";
}

/* ───────── 2) retainer_items ───────── */
if (!tableExists2($pdo, 'retainer_items')) {
    $pdo->exec("CREATE TABLE retainer_items (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        retainer_id INT UNSIGNED NOT NULL,
        category_id INT UNSIGNED NULL,
        title VARCHAR(180) NOT NULL,
        description TEXT NULL,
        quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
        unit ENUM('hour','ticket','user','license','project','month','custom') NOT NULL DEFAULT 'hour',
        unit_label VARCHAR(40) NULL,
        unit_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        is_recurring TINYINT(1) NOT NULL DEFAULT 1,
        is_billable TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_tenant (tenant_id),
        KEY idx_retainer (retainer_id),
        KEY idx_category (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + retainer_items\n";
} else {
    echo "  • retainer_items ya existe\n";
}

/* ───────── 3) retainer_templates ───────── */
if (!tableExists2($pdo, 'retainer_templates')) {
    $pdo->exec("CREATE TABLE retainer_templates (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        category_id INT UNSIGNED NULL,
        name VARCHAR(150) NOT NULL,
        description TEXT NULL,
        billing_cycle ENUM('monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        currency VARCHAR(8) NOT NULL DEFAULT 'USD',
        included_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
        included_tickets INT UNSIGNED NOT NULL DEFAULT 0,
        overage_hour_rate DECIMAL(10,2) NOT NULL DEFAULT 0,
        tax_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
        payment_terms VARCHAR(60) NULL,
        scope TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_tenant (tenant_id),
        KEY idx_category (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + retainer_templates\n";
} else {
    echo "  • retainer_templates ya existe\n";
}

/* ───────── 4) retainer_template_items ───────── */
if (!tableExists2($pdo, 'retainer_template_items')) {
    $pdo->exec("CREATE TABLE retainer_template_items (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        template_id INT UNSIGNED NOT NULL,
        category_id INT UNSIGNED NULL,
        title VARCHAR(180) NOT NULL,
        description TEXT NULL,
        quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
        unit ENUM('hour','ticket','user','license','project','month','custom') NOT NULL DEFAULT 'hour',
        unit_label VARCHAR(40) NULL,
        unit_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        is_recurring TINYINT(1) NOT NULL DEFAULT 1,
        is_billable TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY idx_template (template_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + retainer_template_items\n";
} else {
    echo "  • retainer_template_items ya existe\n";
}

/* ───────── 5) Columnas adicionales en `retainers` ───────── */
$alters = [
    'category_id'           => 'ALTER TABLE retainers ADD COLUMN category_id INT UNSIGNED NULL AFTER name, ADD INDEX idx_category (category_id)',
    'template_id'           => 'ALTER TABLE retainers ADD COLUMN template_id INT UNSIGNED NULL AFTER category_id',
    'tax_pct'               => 'ALTER TABLE retainers ADD COLUMN tax_pct DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER currency',
    'payment_terms'         => 'ALTER TABLE retainers ADD COLUMN payment_terms VARCHAR(60) NULL AFTER tax_pct',
    'response_sla_minutes'  => 'ALTER TABLE retainers ADD COLUMN response_sla_minutes INT UNSIGNED NULL AFTER overage_hour_rate',
    'resolve_sla_minutes'   => 'ALTER TABLE retainers ADD COLUMN resolve_sla_minutes INT UNSIGNED NULL AFTER response_sla_minutes',
    'custom_fields'         => 'ALTER TABLE retainers ADD COLUMN custom_fields JSON NULL AFTER notes',
];
foreach ($alters as $col => $sql) {
    if (!columnExists2($pdo, 'retainers', $col)) {
        $pdo->exec($sql);
        echo "  + retainers.$col\n";
    } else {
        echo "  • retainers.$col ya existe\n";
    }
}

/* ───────── 6) Permiso adicional retainers.config ───────── */
$pdo->prepare("INSERT IGNORE INTO permissions (slug, module, label) VALUES (?,?,?)")
    ->execute(['retainers.config', 'retainers', 'Configurar categorías y plantillas de igualas']);
$roles = $pdo->query("SELECT id FROM roles WHERE slug IN ('owner','admin')")->fetchAll(PDO::FETCH_COLUMN);
$permId = (int)$pdo->query("SELECT id FROM permissions WHERE slug='retainers.config'")->fetchColumn();
if ($permId) {
    $grantStmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)");
    foreach ($roles as $rid) $grantStmt->execute([(int)$rid, $permId]);
}
echo "  + permiso retainers.config\n";

/* ───────── 7) Seed de categorías por defecto por tenant ───────── */
$defaults = [
    ['soporte-ti',         'Soporte TI',           'Atención de incidencias técnicas, helpdesk, mesa de servicio.',          'life-buoy',     '#7c5cff', 'hour',    'horas',   1],
    ['desarrollo-software','Desarrollo de software','Desarrollo a medida, frontend, backend, mobile, APIs.',                  'code-2',        '#3b82f6', 'hour',    'horas',   2],
    ['sistemas',           'Sistemas / Infraestructura','Administración de servidores, redes, cloud, on-prem.',                'server',        '#0ea5e9', 'hour',    'horas',   3],
    ['ciberseguridad',     'Ciberseguridad',       'Pentesting, monitoreo, hardening, incident response.',                    'shield',        '#dc2626', 'hour',    'horas',   4],
    ['cloud-devops',       'Cloud & DevOps',       'AWS, Azure, GCP, CI/CD, automatización, IaC.',                            'cloud',         '#0284c7', 'hour',    'horas',   5],
    ['consultoria',        'Consultoría',          'Asesoría estratégica, arquitectura, transformación digital.',             'briefcase',     '#7c2d12', 'hour',    'horas',   6],
    ['marketing-digital',  'Marketing digital',    'SEO, ads, contenido, analítica, campañas.',                               'megaphone',     '#ec4899', 'hour',    'horas',   7],
    ['legal',              'Legal',                'Asesoría legal corporativa, contratos, cumplimiento.',                    'scale',         '#6b7280', 'hour',    'horas',   8],
    ['contable',           'Contable / Fiscal',    'Contabilidad mensual, declaraciones, nómina.',                            'calculator',    '#16a34a', 'month',   'meses',   9],
    ['mantenimiento-web',  'Mantenimiento web',    'WordPress, e-commerce, hosting, actualizaciones.',                        'globe',         '#06b6d4', 'month',   'sitios',  10],
    ['licencias-saas',     'Licencias SaaS',       'Licencias de software, seats, suscripciones gestionadas.',                'key',           '#f59e0b', 'license', 'licencias', 11],
];
$tenants = $pdo->query("SELECT id FROM tenants WHERE IFNULL(is_developer_sandbox,0)=0")->fetchAll(PDO::FETCH_COLUMN);
$insertCat = $pdo->prepare("INSERT IGNORE INTO retainer_categories (tenant_id, slug, name, description, icon, color, default_unit, default_unit_label, sort_order) VALUES (?,?,?,?,?,?,?,?,?)");
$seeded = 0;
foreach ($tenants as $tid) {
    foreach ($defaults as [$slug, $name, $desc, $icon, $color, $unit, $unitLabel, $sort]) {
        $insertCat->execute([(int)$tid, $slug, $name, $desc, $icon, $color, $unit, $unitLabel, $sort]);
        if ($insertCat->rowCount() > 0) $seeded++;
    }
}
echo "  + categorías seed: $seeded\n";

/* ───────── 8) Seed de plantillas por defecto ───────── */
$templates = [
    ['Soporte TI Básico',           'soporte-ti',         'monthly', 299,   20, 5,  35, 'Soporte help-desk básico para PyMEs · 20h/mes incluidas, hasta 5 usuarios'],
    ['Soporte TI Premium',          'soporte-ti',         'monthly', 899,   80, 20, 30, 'Soporte 24/7 con SLA de 1h · 80h/mes · técnicos dedicados'],
    ['Dev Software · Sprint',       'desarrollo-software','monthly', 4500, 100, 0,  55, '100h de desarrollo (front + back) · 1 sprint mensual · code reviews + QA'],
    ['Dev Software · Maintenance',  'desarrollo-software','monthly', 1500,  40, 0,  45, 'Mantenimiento evolutivo · 40h/mes · bugfixes + features menores'],
    ['Cloud Ops 24/7',              'cloud-devops',       'monthly', 1200,  30, 0,  60, 'Operación de infraestructura cloud · monitoreo + on-call · SRE'],
    ['Pentest mensual',             'ciberseguridad',     'monthly', 1800,  16, 0, 120, 'Pentest enfocado mensual + retest · reporte ejecutivo'],
    ['Consultoría Senior',          'consultoria',        'monthly',  900,  10, 0,  90, '10h de consultoría con arquitecto senior · workshops y advisory'],
    ['Marketing 360',               'marketing-digital',  'monthly', 1500,   0, 0,   0, 'Estrategia + contenido + ads · entregables mensuales'],
    ['Asesoría Legal Corporativa',  'legal',              'monthly',  600,   8, 0,  85, '8h/mes de asesoría legal · revisión de contratos'],
    ['Contabilidad Mensual',        'contable',           'monthly',  250,   0, 0,   0, 'Llevado de libros + declaraciones · nómina hasta 10 empleados'],
];
$catIdByTenantSlug = [];
$catRows = $pdo->query("SELECT id, tenant_id, slug FROM retainer_categories")->fetchAll(PDO::FETCH_ASSOC);
foreach ($catRows as $c) $catIdByTenantSlug[(int)$c['tenant_id']][$c['slug']] = (int)$c['id'];

$insertTpl = $pdo->prepare("INSERT INTO retainer_templates (tenant_id, category_id, name, description, billing_cycle, amount, currency, included_hours, included_tickets, overage_hour_rate, scope, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
$tplCount = 0;
foreach ($tenants as $tid) {
    $tid = (int)$tid;
    // Saltar si el tenant ya tiene plantillas (idempotente sin UNIQUE estricta)
    $existing = (int)$pdo->query("SELECT COUNT(*) FROM retainer_templates WHERE tenant_id=$tid")->fetchColumn();
    if ($existing > 0) continue;
    $i = 0;
    foreach ($templates as [$name, $catSlug, $cycle, $amount, $hours, $tickets, $overage, $desc]) {
        $catId = $catIdByTenantSlug[$tid][$catSlug] ?? null;
        $insertTpl->execute([$tid, $catId, $name, $desc, $cycle, $amount, 'USD', $hours, $tickets, $overage, $desc, $i++]);
        $tplCount++;
    }
}
echo "  + plantillas seed: $tplCount\n";

echo "\n✓ Migración v2 de igualas (configurable) completa.\n";
