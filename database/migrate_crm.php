<?php
/**
 * CRM — Gestión avanzada de Leads / Clientes (Business / Enterprise)
 *
 *  · `crm_pipelines`        — pipelines configurables (Ventas, Onboarding, Renovaciones)
 *  · `crm_stages`           — etapas de un pipeline (con probabilidad y color)
 *  · `crm_sources`          — orígenes de leads (Web, Referido, Ads, Cold call)
 *  · `crm_leads`            — leads/clientes con score, estado, owner, conexión a empresa
 *  · `crm_deals`            — oportunidades por lead (monto, etapa, fecha estimada de cierre)
 *  · `crm_activities`       — llamadas, emails, reuniones, tareas asignadas
 *  · `crm_notes`            — notas internas por lead
 *  · `crm_tags`             — tags por tenant
 *  · `crm_lead_tags`        — pivot tag↔lead
 *  · permisos crm.* + seed de pipelines/stages/sources por tenant
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_CRM') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO(
    "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}",
    $cfg['user'],
    $cfg['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
echo "✓ Connected · {$cfg['host']} → {$cfg['name']}\n\n";

function tableExistsCrm(PDO $pdo, string $t): bool {
    return (bool)$pdo->query("SHOW TABLES LIKE " . $pdo->quote($t))->fetch();
}
function columnExistsCrm(PDO $pdo, string $t, string $c): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$pdo->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}

/* ───────── 1) crm_pipelines ───────── */
if (!tableExistsCrm($pdo, 'crm_pipelines')) {
    $pdo->exec("CREATE TABLE crm_pipelines (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        slug VARCHAR(80) NOT NULL,
        name VARCHAR(120) NOT NULL,
        description VARCHAR(255) NULL,
        icon VARCHAR(40) NOT NULL DEFAULT 'target',
        color VARCHAR(20) NOT NULL DEFAULT '#7c5cff',
        is_default TINYINT(1) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_tenant_slug (tenant_id, slug),
        KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + crm_pipelines\n";
} else echo "  • crm_pipelines ya existe\n";

/* ───────── 2) crm_stages ───────── */
if (!tableExistsCrm($pdo, 'crm_stages')) {
    $pdo->exec("CREATE TABLE crm_stages (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        pipeline_id INT UNSIGNED NOT NULL,
        slug VARCHAR(80) NOT NULL,
        name VARCHAR(120) NOT NULL,
        probability DECIMAL(5,2) NOT NULL DEFAULT 0,
        color VARCHAR(20) NOT NULL DEFAULT '#94a3b8',
        is_won TINYINT(1) NOT NULL DEFAULT 0,
        is_lost TINYINT(1) NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_pipeline_slug (pipeline_id, slug),
        KEY idx_tenant (tenant_id),
        KEY idx_pipeline (pipeline_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + crm_stages\n";
} else echo "  • crm_stages ya existe\n";

/* ───────── 3) crm_sources ───────── */
if (!tableExistsCrm($pdo, 'crm_sources')) {
    $pdo->exec("CREATE TABLE crm_sources (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        slug VARCHAR(80) NOT NULL,
        name VARCHAR(120) NOT NULL,
        icon VARCHAR(40) NOT NULL DEFAULT 'globe',
        color VARCHAR(20) NOT NULL DEFAULT '#6366f1',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_tenant_slug (tenant_id, slug),
        KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + crm_sources\n";
} else echo "  • crm_sources ya existe\n";

/* ───────── 4) crm_leads ───────── */
if (!tableExistsCrm($pdo, 'crm_leads')) {
    $pdo->exec("CREATE TABLE crm_leads (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        code VARCHAR(40) NOT NULL,
        first_name VARCHAR(120) NOT NULL,
        last_name VARCHAR(120) NULL,
        email VARCHAR(180) NULL,
        phone VARCHAR(40) NULL,
        whatsapp VARCHAR(40) NULL,
        job_title VARCHAR(160) NULL,
        company_id INT UNSIGNED NULL,
        company_name VARCHAR(180) NULL,
        website VARCHAR(180) NULL,
        industry VARCHAR(120) NULL,
        country VARCHAR(80) NULL,
        city VARCHAR(120) NULL,
        address VARCHAR(255) NULL,
        source_id INT UNSIGNED NULL,
        source_detail VARCHAR(180) NULL,
        owner_id INT UNSIGNED NULL,
        status ENUM('new','contacted','qualified','proposal','negotiation','customer','lost','archived')
            NOT NULL DEFAULT 'new',
        rating ENUM('cold','warm','hot') NOT NULL DEFAULT 'warm',
        score INT NOT NULL DEFAULT 0,
        estimated_value DECIMAL(12,2) NOT NULL DEFAULT 0,
        currency VARCHAR(8) NOT NULL DEFAULT 'USD',
        expected_close_on DATE NULL,
        last_contacted_at DATETIME NULL,
        next_followup_at DATETIME NULL,
        consent_marketing TINYINT(1) NOT NULL DEFAULT 0,
        portal_user_id INT UNSIGNED NULL,
        converted_at DATETIME NULL,
        converted_by INT UNSIGNED NULL,
        notes TEXT NULL,
        custom_fields JSON NULL,
        created_by INT UNSIGNED NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_tenant_code (tenant_id, code),
        KEY idx_tenant (tenant_id),
        KEY idx_owner (owner_id),
        KEY idx_status (status),
        KEY idx_company (company_id),
        KEY idx_source (source_id),
        KEY idx_followup (next_followup_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + crm_leads\n";
} else echo "  • crm_leads ya existe\n";

/* ───────── 5) crm_deals ───────── */
if (!tableExistsCrm($pdo, 'crm_deals')) {
    $pdo->exec("CREATE TABLE crm_deals (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        lead_id INT UNSIGNED NOT NULL,
        pipeline_id INT UNSIGNED NOT NULL,
        stage_id INT UNSIGNED NOT NULL,
        owner_id INT UNSIGNED NULL,
        title VARCHAR(180) NOT NULL,
        description TEXT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        currency VARCHAR(8) NOT NULL DEFAULT 'USD',
        probability DECIMAL(5,2) NOT NULL DEFAULT 0,
        expected_close_on DATE NULL,
        actual_close_on DATE NULL,
        retainer_id INT UNSIGNED NULL,
        retainer_template_id INT UNSIGNED NULL,
        meeting_id INT UNSIGNED NULL,
        won_at DATETIME NULL,
        lost_at DATETIME NULL,
        lost_reason VARCHAR(255) NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_tenant (tenant_id),
        KEY idx_lead (lead_id),
        KEY idx_pipeline (pipeline_id),
        KEY idx_stage (stage_id),
        KEY idx_owner (owner_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + crm_deals\n";
} else echo "  • crm_deals ya existe\n";

/* ───────── 6) crm_activities ───────── */
if (!tableExistsCrm($pdo, 'crm_activities')) {
    $pdo->exec("CREATE TABLE crm_activities (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        lead_id INT UNSIGNED NULL,
        deal_id INT UNSIGNED NULL,
        owner_id INT UNSIGNED NULL,
        type ENUM('call','email','meeting','task','whatsapp','sms','note_event') NOT NULL DEFAULT 'task',
        subject VARCHAR(255) NOT NULL,
        body TEXT NULL,
        scheduled_at DATETIME NULL,
        duration_min INT UNSIGNED NULL,
        location VARCHAR(255) NULL,
        outcome ENUM('pending','completed','no_answer','rescheduled','cancelled') NOT NULL DEFAULT 'pending',
        completed_at DATETIME NULL,
        meeting_id INT UNSIGNED NULL,
        ticket_id INT UNSIGNED NULL,
        created_by INT UNSIGNED NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_tenant (tenant_id),
        KEY idx_lead (lead_id),
        KEY idx_deal (deal_id),
        KEY idx_owner (owner_id),
        KEY idx_scheduled (scheduled_at),
        KEY idx_outcome (outcome)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + crm_activities\n";
} else echo "  • crm_activities ya existe\n";

/* ───────── 7) crm_notes ───────── */
if (!tableExistsCrm($pdo, 'crm_notes')) {
    $pdo->exec("CREATE TABLE crm_notes (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        lead_id INT UNSIGNED NOT NULL,
        author_id INT UNSIGNED NULL,
        body TEXT NOT NULL,
        is_pinned TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_tenant (tenant_id),
        KEY idx_lead (lead_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + crm_notes\n";
} else echo "  • crm_notes ya existe\n";

/* ───────── 8) crm_tags ───────── */
if (!tableExistsCrm($pdo, 'crm_tags')) {
    $pdo->exec("CREATE TABLE crm_tags (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        slug VARCHAR(80) NOT NULL,
        name VARCHAR(80) NOT NULL,
        color VARCHAR(20) NOT NULL DEFAULT '#7c5cff',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_tenant_slug (tenant_id, slug),
        KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + crm_tags\n";
} else echo "  • crm_tags ya existe\n";

/* ───────── 9) crm_lead_tags ───────── */
if (!tableExistsCrm($pdo, 'crm_lead_tags')) {
    $pdo->exec("CREATE TABLE crm_lead_tags (
        lead_id INT UNSIGNED NOT NULL,
        tag_id INT UNSIGNED NOT NULL,
        PRIMARY KEY (lead_id, tag_id),
        KEY idx_tag (tag_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + crm_lead_tags\n";
} else echo "  • crm_lead_tags ya existe\n";

/* ───────── 10) Permisos ───────── */
$permsToAdd = [
    'crm.view'      => ['crm', 'Ver leads y oportunidades'],
    'crm.create'    => ['crm', 'Crear leads / oportunidades'],
    'crm.edit'      => ['crm', 'Editar leads / oportunidades'],
    'crm.delete'    => ['crm', 'Eliminar leads / oportunidades'],
    'crm.config'    => ['crm', 'Configurar pipelines / etapas / fuentes'],
    'crm.assign'    => ['crm', 'Reasignar owner de leads'],
    'crm.convert'   => ['crm', 'Convertir lead en cliente'],
];
$stmt = $pdo->prepare("INSERT IGNORE INTO permissions (slug, module, label) VALUES (?,?,?)");
$added = 0;
foreach ($permsToAdd as $slug => [$mod, $label]) {
    $stmt->execute([$slug, $mod, $label]);
    if ($stmt->rowCount() > 0) $added++;
}
echo "  + permisos sembrados ($added nuevos)\n";

$roles = $pdo->query("SELECT id FROM roles WHERE slug IN ('owner','admin')")->fetchAll(PDO::FETCH_COLUMN);
$permIds = $pdo->query("SELECT id FROM permissions WHERE module='crm'")->fetchAll(PDO::FETCH_COLUMN);
$grantStmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)");
$grants = 0;
foreach ($roles as $rid) {
    foreach ($permIds as $pid) {
        $grantStmt->execute([(int)$rid, (int)$pid]);
        if ($grantStmt->rowCount() > 0) $grants++;
    }
}
// Solo lectura para agentes
$agentRole = (int)$pdo->query("SELECT id FROM roles WHERE slug='agent' LIMIT 1")->fetchColumn();
if ($agentRole) {
    $viewPerm = (int)$pdo->query("SELECT id FROM permissions WHERE slug='crm.view'")->fetchColumn();
    $editPerm = (int)$pdo->query("SELECT id FROM permissions WHERE slug='crm.edit'")->fetchColumn();
    $createPerm = (int)$pdo->query("SELECT id FROM permissions WHERE slug='crm.create'")->fetchColumn();
    foreach ([$viewPerm, $editPerm, $createPerm] as $p) {
        if ($p) $grantStmt->execute([$agentRole, $p]);
    }
}
echo "  + role_permissions otorgadas: $grants\n";

/* ───────── 11) Seed de pipelines / stages / sources / tags por tenant ───────── */
$tenants = $pdo->query("SELECT id FROM tenants WHERE IFNULL(is_developer_sandbox,0)=0")->fetchAll(PDO::FETCH_COLUMN);

$insertPipeline = $pdo->prepare("INSERT IGNORE INTO crm_pipelines (tenant_id, slug, name, description, icon, color, is_default, sort_order) VALUES (?,?,?,?,?,?,?,?)");
$insertStage = $pdo->prepare("INSERT IGNORE INTO crm_stages (tenant_id, pipeline_id, slug, name, probability, color, is_won, is_lost, sort_order) VALUES (?,?,?,?,?,?,?,?,?)");
$insertSource = $pdo->prepare("INSERT IGNORE INTO crm_sources (tenant_id, slug, name, icon, color, sort_order) VALUES (?,?,?,?,?,?)");
$insertTag = $pdo->prepare("INSERT IGNORE INTO crm_tags (tenant_id, slug, name, color) VALUES (?,?,?,?)");

$defaultSources = [
    ['web',          'Sitio web',            'globe',           '#0ea5e9'],
    ['referral',     'Referido',             'users',           '#10b981'],
    ['ads',          'Ads / SEM',            'megaphone',       '#ec4899'],
    ['linkedin',     'LinkedIn',             'briefcase',       '#0a66c2'],
    ['cold-call',    'Cold call',            'phone-call',      '#f59e0b'],
    ['email',        'Cold email',           'mail',            '#7c5cff'],
    ['event',        'Evento / Feria',       'calendar-heart',  '#ef4444'],
    ['partner',      'Partner / Aliado',     'handshake',       '#16a34a'],
    ['inbound-form', 'Formulario web',       'file-text',       '#6366f1'],
    ['whatsapp',     'WhatsApp',             'message-circle',  '#25d366'],
];

$defaultTags = [
    ['enterprise',  'Enterprise',     '#7c2d12'],
    ['smb',         'SMB',            '#0ea5e9'],
    ['vip',         'VIP',            '#f59e0b'],
    ['churn-risk',  'Riesgo de fuga', '#ef4444'],
    ['upsell',      'Upsell',         '#10b981'],
    ['hot-lead',    'Hot lead',       '#dc2626'],
];

$pipelines = [
    [
        'slug' => 'sales',
        'name' => 'Pipeline de Ventas',
        'description' => 'Funnel comercial estándar para nuevos clientes',
        'icon' => 'target',
        'color' => '#7c5cff',
        'is_default' => 1,
        'stages' => [
            ['nuevo',         'Nuevo',         10,   '#94a3b8', 0, 0],
            ['contactado',    'Contactado',    20,   '#3b82f6', 0, 0],
            ['calificado',    'Calificado',    35,   '#0ea5e9', 0, 0],
            ['propuesta',     'Propuesta',     55,   '#a78bfa', 0, 0],
            ['negociacion',   'Negociación',   75,   '#f59e0b', 0, 0],
            ['ganada',        'Ganada',       100,   '#16a34a', 1, 0],
            ['perdida',       'Perdida',        0,   '#ef4444', 0, 1],
        ],
    ],
    [
        'slug' => 'onboarding',
        'name' => 'Onboarding de Clientes',
        'description' => 'Implementación post-venta hasta activación',
        'icon' => 'rocket',
        'color' => '#10b981',
        'is_default' => 0,
        'stages' => [
            ['kickoff',       'Kickoff',         15, '#3b82f6', 0, 0],
            ['discovery',     'Discovery',       30, '#0ea5e9', 0, 0],
            ['setup',         'Setup técnico',   50, '#a78bfa', 0, 0],
            ['training',      'Capacitación',    75, '#f59e0b', 0, 0],
            ['live',          'En vivo',        100, '#16a34a', 1, 0],
        ],
    ],
    [
        'slug' => 'renewals',
        'name' => 'Renovaciones',
        'description' => 'Pipeline de renovaciones y upsell',
        'icon' => 'refresh-cw',
        'color' => '#f59e0b',
        'is_default' => 0,
        'stages' => [
            ['proxima',       'Próxima a vencer', 30, '#94a3b8', 0, 0],
            ['propuesta-ren', 'Propuesta',        55, '#a78bfa', 0, 0],
            ['negociacion-ren','Negociación',     75, '#f59e0b', 0, 0],
            ['renovada',      'Renovada',        100, '#16a34a', 1, 0],
            ['churn',         'Churn',             0, '#ef4444', 0, 1],
        ],
    ],
];

$seededP = $seededS = $seededSrc = $seededT = 0;
foreach ($tenants as $tid) {
    $tid = (int)$tid;
    foreach ($defaultSources as $i => [$slug, $name, $icon, $color]) {
        $insertSource->execute([$tid, $slug, $name, $icon, $color, $i]);
        if ($insertSource->rowCount() > 0) $seededSrc++;
    }
    foreach ($defaultTags as [$slug, $name, $color]) {
        $insertTag->execute([$tid, $slug, $name, $color]);
        if ($insertTag->rowCount() > 0) $seededT++;
    }
    foreach ($pipelines as $i => $p) {
        $insertPipeline->execute([
            $tid, $p['slug'], $p['name'], $p['description'],
            $p['icon'], $p['color'], $p['is_default'], $i
        ]);
        $pipelineId = (int)$pdo->query("SELECT id FROM crm_pipelines WHERE tenant_id=$tid AND slug=" . $pdo->quote($p['slug']))->fetchColumn();
        if ($pipelineId) {
            if ($insertPipeline->rowCount() > 0) $seededP++;
            foreach ($p['stages'] as $j => [$stageSlug, $stageName, $prob, $color, $won, $lost]) {
                $insertStage->execute([
                    $tid, $pipelineId, $stageSlug, $stageName,
                    $prob, $color, $won, $lost, $j
                ]);
                if ($insertStage->rowCount() > 0) $seededS++;
            }
        }
    }
}
echo "  + pipelines seed: $seededP · stages seed: $seededS · sources seed: $seededSrc · tags seed: $seededT\n";

echo "\n✓ Migración CRM completa.\n";
echo "  · El módulo 'crm' está gateado por plan Business / Enterprise (Plan::FEATURES).\n";
echo "  · El super admin puede habilitar/deshabilitar 'crm' por tenant en /admin/tenants/{id}/modules.\n";
