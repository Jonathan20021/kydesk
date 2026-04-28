<?php
/**
 * Módulo de Agenda de Reuniones — Calendly-style.
 *   · Meeting Types (tipos de cita configurables por tenant)
 *   · Meeting Availability (disponibilidad semanal por host)
 *   · Meeting Blocked Dates (vacaciones / feriados)
 *   · Meetings (citas confirmadas con clientes)
 *   · Meeting Settings (configuración de página pública)
 *   · Permisos meetings.* + features 'meetings' (Business / Enterprise)
 *
 * Idempotente: se puede correr varias veces sin romper nada.
 *
 * CLI:  php database/migrate_meetings.php
 * Web:  /database/migrate_meetings.php?token=KYDESK_MEETINGS
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_MEETINGS') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected · {$cfg['host']} → {$cfg['name']}\n\n";

function mtxExists(PDO $p, string $t): bool { return (bool)$p->query("SHOW TABLES LIKE " . $p->quote($t))->fetch(); }
function mcxExists(PDO $p, string $t, string $c): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$p->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}
function mtMk(PDO $p, string $t, string $sql): void {
    if (!mtxExists($p, $t)) { $p->exec($sql); echo "  + $t\n"; } else echo "  • $t ya existe\n";
}

/* ════════════ 1. MEETING TYPES ════════════ */
mtMk($pdo, 'meeting_types', "CREATE TABLE meeting_types (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    slug VARCHAR(80) NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    color VARCHAR(20) NOT NULL DEFAULT '#7c5cff',
    icon VARCHAR(40) NOT NULL DEFAULT 'video',
    location_type ENUM('virtual','phone','in_person','custom') NOT NULL DEFAULT 'virtual',
    location_value VARCHAR(255) NULL,
    buffer_before_minutes INT UNSIGNED NOT NULL DEFAULT 0,
    buffer_after_minutes INT UNSIGNED NOT NULL DEFAULT 15,
    min_notice_hours INT UNSIGNED NOT NULL DEFAULT 4,
    max_advance_days INT UNSIGNED NOT NULL DEFAULT 60,
    slot_step_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    default_host_id INT UNSIGNED NULL,
    requires_confirmation TINYINT(1) NOT NULL DEFAULT 0,
    allow_reschedule TINYINT(1) NOT NULL DEFAULT 1,
    allow_cancel TINYINT(1) NOT NULL DEFAULT 1,
    send_reminders TINYINT(1) NOT NULL DEFAULT 1,
    reminder_minutes INT UNSIGNED NOT NULL DEFAULT 60,
    custom_questions JSON NULL,
    redirect_url VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_tenant_slug (tenant_id, slug),
    KEY idx_tenant (tenant_id),
    KEY idx_host (default_host_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ════════════ 2. MEETING AVAILABILITY (semanal por host) ════════════ */
mtMk($pdo, 'meeting_availability', "CREATE TABLE meeting_availability (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    weekday TINYINT NOT NULL,
    start_time TIME NOT NULL DEFAULT '09:00:00',
    end_time TIME NOT NULL DEFAULT '17:00:00',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant_user (tenant_id, user_id),
    KEY idx_weekday (weekday)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ════════════ 3. MEETING BLOCKED DATES (días fuera de oficina) ════════════ */
mtMk($pdo, 'meeting_blocked_dates', "CREATE TABLE meeting_blocked_dates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    date_start DATE NOT NULL,
    date_end DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    reason VARCHAR(255) NULL,
    is_full_day TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    KEY idx_user (user_id),
    KEY idx_dates (date_start, date_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ════════════ 4. MEETINGS (citas) ════════════ */
mtMk($pdo, 'meetings', "CREATE TABLE meetings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    code VARCHAR(40) NOT NULL,
    meeting_type_id INT UNSIGNED NULL,
    host_user_id INT UNSIGNED NULL,
    company_id INT UNSIGNED NULL,
    contact_id INT UNSIGNED NULL,
    customer_name VARCHAR(150) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(40) NULL,
    customer_company VARCHAR(150) NULL,
    subject VARCHAR(200) NULL,
    notes TEXT NULL,
    status ENUM('scheduled','confirmed','cancelled','completed','no_show','rescheduled') NOT NULL DEFAULT 'scheduled',
    scheduled_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    timezone VARCHAR(80) NOT NULL DEFAULT 'America/Santo_Domingo',
    location_type ENUM('virtual','phone','in_person','custom') NOT NULL DEFAULT 'virtual',
    location_value VARCHAR(255) NULL,
    meeting_url VARCHAR(500) NULL,
    custom_answers JSON NULL,
    cancel_reason TEXT NULL,
    cancelled_at DATETIME NULL,
    cancelled_by VARCHAR(20) NULL,
    rescheduled_from_id INT UNSIGNED NULL,
    rescheduled_to_id INT UNSIGNED NULL,
    reminder_sent_at DATETIME NULL,
    confirmation_sent_at DATETIME NULL,
    public_token VARCHAR(64) NOT NULL,
    source ENUM('public','manual','import') NOT NULL DEFAULT 'public',
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_token (public_token),
    UNIQUE KEY uniq_tenant_code (tenant_id, code),
    KEY idx_tenant (tenant_id),
    KEY idx_type (meeting_type_id),
    KEY idx_host (host_user_id),
    KEY idx_company (company_id),
    KEY idx_scheduled (scheduled_at),
    KEY idx_status (status),
    KEY idx_email (customer_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ════════════ 5. MEETING SETTINGS (página pública) ════════════ */
mtMk($pdo, 'meeting_settings', "CREATE TABLE meeting_settings (
    tenant_id INT UNSIGNED NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    public_slug VARCHAR(80) NOT NULL,
    page_title VARCHAR(200) NULL,
    page_description TEXT NULL,
    logo_url VARCHAR(255) NULL,
    primary_color VARCHAR(20) NOT NULL DEFAULT '#7c5cff',
    welcome_message TEXT NULL,
    success_message TEXT NULL,
    timezone VARCHAR(80) NOT NULL DEFAULT 'America/Santo_Domingo',
    business_name VARCHAR(150) NULL,
    business_email VARCHAR(150) NULL,
    business_phone VARCHAR(40) NULL,
    business_address VARCHAR(255) NULL,
    notify_new_booking TINYINT(1) NOT NULL DEFAULT 1,
    notify_emails TEXT NULL,
    require_phone TINYINT(1) NOT NULL DEFAULT 0,
    require_company TINYINT(1) NOT NULL DEFAULT 0,
    show_powered_by TINYINT(1) NOT NULL DEFAULT 1,
    custom_css TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (tenant_id),
    UNIQUE KEY uniq_public_slug (public_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ════════════ 6. PERMISOS ════════════ */
$perms = [
    ['meetings.view',   'meetings', 'Ver agenda y reuniones'],
    ['meetings.create', 'meetings', 'Crear / agendar reuniones manualmente'],
    ['meetings.edit',   'meetings', 'Editar / cancelar reuniones'],
    ['meetings.delete', 'meetings', 'Eliminar reuniones'],
    ['meetings.config', 'meetings', 'Configurar tipos, disponibilidad y página pública'],
];
$insertPerm = $pdo->prepare("INSERT IGNORE INTO permissions (slug, module, label) VALUES (?,?,?)");
foreach ($perms as [$s,$m,$l]) {
    $insertPerm->execute([$s,$m,$l]);
    echo "  + perm $s\n";
}

// Otorgar a roles owner/admin
$ownerAdminRoles = $pdo->query("SELECT id FROM roles WHERE slug IN ('owner','admin')")->fetchAll(PDO::FETCH_COLUMN);
$grantStmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)");
$granted = 0;
foreach ($perms as [$s]) {
    $pid = (int)$pdo->query("SELECT id FROM permissions WHERE slug=" . $pdo->quote($s))->fetchColumn();
    if (!$pid) continue;
    foreach ($ownerAdminRoles as $rid) {
        $grantStmt->execute([(int)$rid, $pid]);
        if ($grantStmt->rowCount() > 0) $granted++;
    }
}
// Y meetings.view también a agent / manager
$agentRoles = $pdo->query("SELECT id FROM roles WHERE slug IN ('agent','manager','technician','support')")->fetchAll(PDO::FETCH_COLUMN);
$viewPerm = (int)$pdo->query("SELECT id FROM permissions WHERE slug='meetings.view'")->fetchColumn();
foreach ($agentRoles as $rid) {
    if ($viewPerm) $grantStmt->execute([(int)$rid, $viewPerm]);
}
echo "  + permisos otorgados a roles ($granted nuevos)\n";

/* ════════════ 7. SEED meeting_settings + tipos por defecto + disponibilidad ════════════ */
$tenants = $pdo->query("SELECT id, slug, name FROM tenants WHERE IFNULL(is_developer_sandbox,0)=0")->fetchAll(PDO::FETCH_ASSOC);

$insertSettings = $pdo->prepare("INSERT IGNORE INTO meeting_settings
    (tenant_id, public_slug, page_title, page_description, primary_color, welcome_message, success_message, business_name, timezone)
    VALUES (?,?,?,?,?,?,?,?,?)");

$insertType = $pdo->prepare("INSERT IGNORE INTO meeting_types
    (tenant_id, slug, name, description, duration_minutes, color, icon, location_type, location_value, buffer_after_minutes, min_notice_hours, max_advance_days, sort_order)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");

$insertAvail = $pdo->prepare("INSERT IGNORE INTO meeting_availability (tenant_id, user_id, weekday, start_time, end_time, is_active) VALUES (?,?,?,?,?,1)");

$defaultTypes = [
    ['demo-15',          'Demo rápida (15 min)',   'Una reunión breve para conocer tus necesidades y mostrarte cómo te podemos ayudar.', 15, '#22c55e', 'video',         'virtual', 'Google Meet · enlace por email', 10, 2,  60, 0],
    ['consulta-30',      'Consulta (30 min)',      'Reunión estándar para discutir un proyecto, propuesta o requerimiento.',              30, '#7c5cff', 'video',         'virtual', 'Google Meet · enlace por email', 15, 4,  60, 1],
    ['estrategia-60',    'Sesión estratégica (1h)','Sesión profunda de descubrimiento, planning o roadmap con tu equipo.',                 60, '#f59e0b', 'video',         'virtual', 'Google Meet · enlace por email', 15, 24, 60, 2],
    ['llamada-15',       'Llamada telefónica',     'Llamada rápida para resolver dudas puntuales sin necesidad de video.',                 15, '#0ea5e9', 'phone-call',    'phone',   'Te llamamos al teléfono que indiques', 10, 2, 30, 3],
    ['presencial-60',    'Reunión presencial',     'Reunión cara a cara en nuestras oficinas. Coordinamos detalles luego.',                60, '#ec4899', 'map-pin',       'in_person', 'Oficinas — coordinar dirección',     30, 48, 90, 4],
];

$seededTenants = 0;
$seededTypes = 0;
$seededAvail = 0;
foreach ($tenants as $t) {
    $tid = (int)$t['id'];

    // Settings
    $welcome = "Hola, soy " . $t['name'] . ". Elige el tipo de reunión que mejor se adapte y reserva un horario que te funcione.";
    $success = "¡Reserva confirmada! Te enviamos un email con los detalles. Si necesitás reprogramar o cancelar, podés hacerlo desde el enlace en el correo.";
    $insertSettings->execute([
        $tid,
        $t['slug'],
        'Agenda una reunión con ' . $t['name'],
        'Elige el tipo de reunión y selecciona el horario que mejor te funcione. Te enviaremos la confirmación por email.',
        '#7c5cff',
        $welcome,
        $success,
        $t['name'],
        'America/Santo_Domingo',
    ]);

    // Tipos default solo si el tenant no tiene
    $hasTypes = (int)$pdo->query("SELECT COUNT(*) FROM meeting_types WHERE tenant_id=$tid")->fetchColumn();
    if ($hasTypes === 0) {
        foreach ($defaultTypes as [$slug,$name,$desc,$dur,$color,$icon,$locType,$locVal,$buffer,$minNotice,$maxAdv,$sort]) {
            $insertType->execute([$tid, $slug, $name, $desc, $dur, $color, $icon, $locType, $locVal, $buffer, $minNotice, $maxAdv, $sort]);
            $seededTypes++;
        }
    }

    // Disponibilidad por defecto: lunes-viernes 9:00-17:00 para todos los users técnicos del tenant
    $users = $pdo->prepare("SELECT id FROM users WHERE tenant_id=? AND is_active=1");
    $users->execute([$tid]);
    foreach ($users->fetchAll(PDO::FETCH_COLUMN) as $uid) {
        $hasAvail = (int)$pdo->query("SELECT COUNT(*) FROM meeting_availability WHERE tenant_id=$tid AND user_id=$uid")->fetchColumn();
        if ($hasAvail > 0) continue;
        // 1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves, 5=Viernes
        for ($wd = 1; $wd <= 5; $wd++) {
            $insertAvail->execute([$tid, (int)$uid, $wd, '09:00:00', '17:00:00']);
            $seededAvail++;
        }
    }

    $seededTenants++;
}
echo "  + seed: $seededTenants tenants · $seededTypes tipos · $seededAvail slots de disponibilidad\n";

/* ════════════ 8. Auto-asignar default_host_id en tipos sin host ════════════ */
$typesNoHost = $pdo->query("SELECT id, tenant_id FROM meeting_types WHERE default_host_id IS NULL")->fetchAll(PDO::FETCH_ASSOC);
$pickHost = $pdo->prepare("SELECT id FROM users WHERE tenant_id=? AND is_active=1 ORDER BY id LIMIT 1");
$updateHost = $pdo->prepare("UPDATE meeting_types SET default_host_id=? WHERE id=?");
$assigned = 0;
foreach ($typesNoHost as $tp) {
    $pickHost->execute([(int)$tp['tenant_id']]);
    $hid = (int)$pickHost->fetchColumn();
    if ($hid) {
        $updateHost->execute([$hid, (int)$tp['id']]);
        $assigned++;
    }
}
echo "  + default_host_id asignado a $assigned tipos\n";

echo "\n✓ Migración módulo de Reuniones completada.\n";
