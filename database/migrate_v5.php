<?php
/**
 * Migración v5 — 10 módulos pro:
 *   1.  Custom fields por categoría
 *   2.  CSAT / NPS encuestas
 *   3.  Status page pública (componentes, incidentes, suscriptores)
 *   4.  Customer portal con login
 *   5.  Time tracking + Igualas
 *   6.  Email-to-ticket inbound (IMAP / forward)
 *   7.  Live chat / messenger (widget + conversaciones)
 *   8.  AI asistente (settings + log de completions)
 *   9.  ITSM (service catalog, change requests, approvals, problems)
 *   10. Reports builder (custom reports + widgets)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_V5') { http_response_code(403); exit('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "✓ Connected · {$cfg['host']} → {$cfg['name']}\n\n";

function tex(PDO $p, string $t): bool { return (bool)$p->query("SHOW TABLES LIKE " . $p->quote($t))->fetch(); }
function cex(PDO $p, string $t, string $c): bool {
    $st = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
    $sc = preg_replace('/[^a-zA-Z0-9_]/', '', $c);
    return (bool)$p->query("SHOW COLUMNS FROM `$st` LIKE '$sc'")->fetch();
}
function mk(PDO $p, string $t, string $sql): void {
    if (!tex($p, $t)) { $p->exec($sql); echo "  + $t\n"; } else echo "  • $t ya existe\n";
}
function add(PDO $p, string $t, string $c, string $sql): void {
    if (!cex($p, $t, $c)) { $p->exec($sql); echo "  + $t.$c\n"; } else echo "  • $t.$c ya existe\n";
}

/* ════════════ 1. CUSTOM FIELDS ════════════ */
mk($pdo, 'custom_fields', "CREATE TABLE custom_fields (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NULL,
    field_key VARCHAR(80) NOT NULL,
    label VARCHAR(150) NOT NULL,
    type ENUM('text','textarea','number','date','select','multiselect','checkbox','url','email','phone') NOT NULL DEFAULT 'text',
    options JSON NULL,
    placeholder VARCHAR(255) NULL,
    help_text VARCHAR(255) NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    is_visible_portal TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_tenant_key (tenant_id, field_key),
    KEY idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

mk($pdo, 'ticket_field_values', "CREATE TABLE ticket_field_values (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    ticket_id INT UNSIGNED NOT NULL,
    field_id INT UNSIGNED NOT NULL,
    value TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_ticket_field (ticket_id, field_id),
    KEY idx_tenant (tenant_id),
    KEY idx_field (field_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ════════════ 2. CSAT / NPS ════════════ */
mk($pdo, 'csat_surveys', "CREATE TABLE csat_surveys (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    ticket_id INT UNSIGNED NOT NULL,
    type ENUM('csat','nps') NOT NULL DEFAULT 'csat',
    token VARCHAR(64) NOT NULL,
    score TINYINT NULL,
    comment TEXT NULL,
    sent_at DATETIME NULL,
    responded_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_token (token),
    KEY idx_tenant (tenant_id),
    KEY idx_ticket (ticket_id),
    KEY idx_score (score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

mk($pdo, 'csat_settings', "CREATE TABLE csat_settings (
    tenant_id INT UNSIGNED NOT NULL,
    type ENUM('csat','nps') NOT NULL DEFAULT 'csat',
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    delay_minutes INT NOT NULL DEFAULT 60,
    subject VARCHAR(200) NOT NULL DEFAULT '¿Cómo fue tu experiencia?',
    intro TEXT NULL,
    thanks_message TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (tenant_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ════════════ 3. STATUS PAGE ════════════ */
mk($pdo, 'status_components', "CREATE TABLE status_components (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    description VARCHAR(255) NULL,
    icon VARCHAR(40) DEFAULT 'server',
    status ENUM('operational','degraded','partial_outage','major_outage','maintenance') NOT NULL DEFAULT 'operational',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_tenant_slug (tenant_id, slug),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

mk($pdo, 'status_incidents', "CREATE TABLE status_incidents (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    severity ENUM('minor','major','critical','maintenance') NOT NULL DEFAULT 'minor',
    status ENUM('investigating','identified','monitoring','resolved') NOT NULL DEFAULT 'investigating',
    affected_components JSON NULL,
    started_at DATETIME NOT NULL,
    resolved_at DATETIME NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

mk($pdo, 'status_incident_updates', "CREATE TABLE status_incident_updates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    incident_id INT UNSIGNED NOT NULL,
    tenant_id INT UNSIGNED NOT NULL,
    status ENUM('investigating','identified','monitoring','resolved') NOT NULL,
    body TEXT NOT NULL,
    posted_by INT UNSIGNED NULL,
    posted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_incident (incident_id),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

mk($pdo, 'status_subscribers', "CREATE TABLE status_subscribers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    email VARCHAR(180) NOT NULL,
    confirm_token VARCHAR(64) NOT NULL,
    is_confirmed TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    confirmed_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_tenant_email (tenant_id, email),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ════════════ 4. CUSTOMER PORTAL LOGIN ════════════ */
mk($pdo, 'portal_users', "CREATE TABLE portal_users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NULL,
    contact_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(180) NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(40) NULL,
    avatar VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    reset_token VARCHAR(64) NULL,
    reset_expires_at DATETIME NULL,
    verify_token VARCHAR(64) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_tenant_email (tenant_id, email),
    KEY idx_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

add($pdo, 'tickets', 'portal_user_id', 'ALTER TABLE tickets ADD COLUMN portal_user_id INT UNSIGNED NULL AFTER requester_user_id, ADD INDEX idx_portal_user (portal_user_id)');

/* ════════════ 5. TIME TRACKING ════════════ */
mk($pdo, 'time_entries', "CREATE TABLE time_entries (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    ticket_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NOT NULL,
    retainer_id INT UNSIGNED NULL,
    period_id INT UNSIGNED NULL,
    started_at DATETIME NOT NULL,
    ended_at DATETIME NULL,
    duration_seconds INT UNSIGNED NULL,
    hours DECIMAL(8,2) NULL,
    description TEXT NULL,
    billable TINYINT(1) NOT NULL DEFAULT 1,
    rate DECIMAL(10,2) NOT NULL DEFAULT 0,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    is_running TINYINT(1) NOT NULL DEFAULT 0,
    consumption_id INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    KEY idx_ticket (ticket_id),
    KEY idx_user (user_id),
    KEY idx_retainer (retainer_id),
    KEY idx_running (is_running)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ════════════ 6. EMAIL-TO-TICKET ════════════ */
mk($pdo, 'email_accounts', "CREATE TABLE email_accounts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL,
    fetch_method ENUM('imap','forward') NOT NULL DEFAULT 'imap',
    imap_host VARCHAR(180) NULL,
    imap_port INT UNSIGNED NULL DEFAULT 993,
    imap_user VARCHAR(180) NULL,
    imap_pass VARCHAR(255) NULL,
    imap_encryption ENUM('ssl','tls','none') NOT NULL DEFAULT 'ssl',
    imap_folder VARCHAR(80) NOT NULL DEFAULT 'INBOX',
    forward_token VARCHAR(64) NULL,
    default_category_id INT UNSIGNED NULL,
    default_priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    auto_assign_to INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_fetched_at DATETIME NULL,
    last_error VARCHAR(500) NULL,
    fetch_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_tenant_email (tenant_id, email),
    UNIQUE KEY uniq_forward_token (forward_token),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

mk($pdo, 'email_messages', "CREATE TABLE email_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    account_id INT UNSIGNED NOT NULL,
    ticket_id INT UNSIGNED NULL,
    comment_id INT UNSIGNED NULL,
    message_id VARCHAR(255) NULL,
    in_reply_to VARCHAR(255) NULL,
    `references` TEXT NULL,
    direction ENUM('inbound','outbound') NOT NULL DEFAULT 'inbound',
    from_email VARCHAR(180) NULL,
    from_name VARCHAR(180) NULL,
    to_email VARCHAR(180) NULL,
    subject VARCHAR(255) NULL,
    body_text MEDIUMTEXT NULL,
    body_html MEDIUMTEXT NULL,
    received_at DATETIME NULL,
    raw_size INT UNSIGNED DEFAULT 0,
    status ENUM('new','processed','threaded','failed','duplicate') DEFAULT 'new',
    error VARCHAR(500) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    KEY idx_account (account_id),
    KEY idx_ticket (ticket_id),
    KEY idx_msgid (message_id),
    KEY idx_inreply (in_reply_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ════════════ 7. LIVE CHAT ════════════ */
mk($pdo, 'chat_widgets', "CREATE TABLE chat_widgets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    public_key VARCHAR(64) NOT NULL,
    primary_color VARCHAR(20) DEFAULT '#7c5cff',
    welcome_message VARCHAR(255) DEFAULT '¡Hola! ¿En qué podemos ayudarte?',
    away_message VARCHAR(255) DEFAULT 'Estamos fuera de horario. Dejanos tu mensaje y te respondemos pronto.',
    require_email TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    allowed_origins TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_public_key (public_key),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

mk($pdo, 'chat_conversations', "CREATE TABLE chat_conversations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    widget_id INT UNSIGNED NOT NULL,
    visitor_token VARCHAR(64) NOT NULL,
    visitor_name VARCHAR(150) NULL,
    visitor_email VARCHAR(180) NULL,
    page_url VARCHAR(500) NULL,
    user_agent VARCHAR(255) NULL,
    assigned_to INT UNSIGNED NULL,
    status ENUM('open','assigned','closed') NOT NULL DEFAULT 'open',
    ticket_id INT UNSIGNED NULL,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    last_message_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_visitor_token (visitor_token),
    KEY idx_tenant (tenant_id),
    KEY idx_widget (widget_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

mk($pdo, 'chat_messages', "CREATE TABLE chat_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    conversation_id INT UNSIGNED NOT NULL,
    sender_type ENUM('visitor','agent','system') NOT NULL,
    user_id INT UNSIGNED NULL,
    body TEXT NOT NULL,
    is_seen TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    KEY idx_conv (conversation_id),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ════════════ 8. AI ASSISTANT ════════════ */
mk($pdo, 'ai_settings', "CREATE TABLE ai_settings (
    tenant_id INT UNSIGNED NOT NULL,
    provider ENUM('anthropic','openai','disabled') NOT NULL DEFAULT 'anthropic',
    api_key VARCHAR(255) NULL,
    model VARCHAR(120) DEFAULT 'claude-haiku-4-5',
    auto_categorize TINYINT(1) NOT NULL DEFAULT 0,
    auto_summarize TINYINT(1) NOT NULL DEFAULT 0,
    suggest_replies TINYINT(1) NOT NULL DEFAULT 1,
    detect_sentiment TINYINT(1) NOT NULL DEFAULT 0,
    auto_translate TINYINT(1) NOT NULL DEFAULT 0,
    target_language VARCHAR(10) DEFAULT 'es',
    monthly_quota INT UNSIGNED DEFAULT 1000,
    used_this_month INT UNSIGNED DEFAULT 0,
    quota_reset_at DATE NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

mk($pdo, 'ai_completions', "CREATE TABLE ai_completions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    ticket_id INT UNSIGNED NULL,
    action VARCHAR(40) NOT NULL,
    input_text TEXT NULL,
    output_text MEDIUMTEXT NULL,
    tokens_in INT UNSIGNED DEFAULT 0,
    tokens_out INT UNSIGNED DEFAULT 0,
    duration_ms INT UNSIGNED DEFAULT 0,
    status ENUM('ok','error') NOT NULL DEFAULT 'ok',
    error VARCHAR(500) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    KEY idx_ticket (ticket_id),
    KEY idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

add($pdo, 'tickets', 'ai_summary', 'ALTER TABLE tickets ADD COLUMN ai_summary TEXT NULL AFTER tags');
add($pdo, 'tickets', 'ai_sentiment', "ALTER TABLE tickets ADD COLUMN ai_sentiment ENUM('positive','neutral','negative','urgent') NULL AFTER ai_summary");

/* ════════════ 9. ITSM ════════════ */
mk($pdo, 'service_catalog_items', "CREATE TABLE service_catalog_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NULL,
    department_id INT UNSIGNED NULL,
    name VARCHAR(180) NOT NULL,
    description TEXT NULL,
    icon VARCHAR(40) DEFAULT 'package',
    color VARCHAR(20) DEFAULT '#7c5cff',
    sla_minutes INT UNSIGNED NULL,
    fields JSON NULL,
    requires_approval TINYINT(1) NOT NULL DEFAULT 0,
    approver_user_id INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

mk($pdo, 'change_requests', "CREATE TABLE change_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    type ENUM('standard','normal','emergency') NOT NULL DEFAULT 'normal',
    risk ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    impact ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    status ENUM('draft','pending_approval','approved','rejected','scheduled','in_progress','completed','cancelled','failed') NOT NULL DEFAULT 'draft',
    requester_id INT UNSIGNED NULL,
    assignee_id INT UNSIGNED NULL,
    planned_start DATETIME NULL,
    planned_end DATETIME NULL,
    actual_start DATETIME NULL,
    actual_end DATETIME NULL,
    rollback_plan TEXT NULL,
    test_plan TEXT NULL,
    affected_services VARCHAR(500) NULL,
    related_ticket_id INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_tenant_code (tenant_id, code),
    KEY idx_tenant (tenant_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

mk($pdo, 'change_approvals', "CREATE TABLE change_approvals (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    change_id INT UNSIGNED NOT NULL,
    approver_id INT UNSIGNED NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    comment TEXT NULL,
    decided_at DATETIME NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_change (change_id),
    KEY idx_approver (approver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

mk($pdo, 'problems', "CREATE TABLE problems (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    root_cause TEXT NULL,
    workaround TEXT NULL,
    status ENUM('new','investigating','known_error','resolved','closed') NOT NULL DEFAULT 'new',
    priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    assignee_id INT UNSIGNED NULL,
    related_tickets TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_tenant_code (tenant_id, code),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

add($pdo, 'tickets', 'service_item_id', 'ALTER TABLE tickets ADD COLUMN service_item_id INT UNSIGNED NULL AFTER category_id');
add($pdo, 'tickets', 'change_id', 'ALTER TABLE tickets ADD COLUMN change_id INT UNSIGNED NULL AFTER service_item_id');
add($pdo, 'tickets', 'problem_id', 'ALTER TABLE tickets ADD COLUMN problem_id INT UNSIGNED NULL AFTER change_id');

/* ════════════ 10. REPORTS BUILDER ════════════ */
mk($pdo, 'custom_reports', "CREATE TABLE custom_reports (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    layout JSON NULL,
    filters JSON NULL,
    is_shared TINYINT(1) NOT NULL DEFAULT 0,
    is_favorite TINYINT(1) NOT NULL DEFAULT 0,
    schedule_cron VARCHAR(40) NULL,
    schedule_emails TEXT NULL,
    last_run_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ════════════ PERMISOS ════════════ */
$perms = [
    ['custom_fields.view', 'custom_fields', 'Ver custom fields'],
    ['custom_fields.edit', 'custom_fields', 'Editar custom fields'],
    ['csat.view', 'csat', 'Ver encuestas CSAT/NPS'],
    ['csat.config', 'csat', 'Configurar encuestas'],
    ['status.view', 'status_page', 'Ver status page interno'],
    ['status.edit', 'status_page', 'Gestionar status page'],
    ['portal.manage', 'portal', 'Gestionar usuarios del portal'],
    ['time.view', 'time', 'Ver registro de tiempo'],
    ['time.track', 'time', 'Iniciar/detener timers'],
    ['email.view', 'email', 'Ver buzones de email'],
    ['email.config', 'email', 'Configurar buzones IMAP/forward'],
    ['chat.view', 'chat', 'Ver conversaciones de chat'],
    ['chat.reply', 'chat', 'Responder en chat'],
    ['chat.config', 'chat', 'Configurar widgets'],
    ['ai.use', 'ai', 'Usar funciones IA'],
    ['ai.config', 'ai', 'Configurar IA'],
    ['itsm.view', 'itsm', 'Ver módulo ITSM'],
    ['itsm.create', 'itsm', 'Crear changes/problems/items'],
    ['itsm.approve', 'itsm', 'Aprobar/rechazar changes'],
    ['reports.builder', 'reports', 'Crear reportes personalizados'],
];
$st = $pdo->prepare("INSERT IGNORE INTO permissions (slug, module, label) VALUES (?,?,?)");
$count = 0;
foreach ($perms as [$s, $m, $l]) {
    $st->execute([$s, $m, $l]);
    if ($st->rowCount() > 0) $count++;
}
echo "  + permisos: $count nuevos\n";

$ownerAdmin = $pdo->query("SELECT id FROM roles WHERE slug IN ('owner','admin')")->fetchAll(PDO::FETCH_COLUMN);
$pIds = $pdo->query("SELECT id FROM permissions WHERE module IN ('custom_fields','csat','status_page','portal','time','email','chat','ai','itsm','reports')")->fetchAll(PDO::FETCH_COLUMN);
$gst = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)");
foreach ($ownerAdmin as $rid) foreach ($pIds as $pid) $gst->execute([(int)$rid, (int)$pid]);
echo "  + permisos asignados a owner+admin de cada tenant\n";

/* Seed widget de chat por tenant */
$widgetExists = $pdo->prepare("SELECT id FROM chat_widgets WHERE tenant_id = ?");
$widgetIns = $pdo->prepare("INSERT INTO chat_widgets (tenant_id, name, public_key) VALUES (?,?,?)");
$tenants = $pdo->query("SELECT id, name FROM tenants WHERE IFNULL(is_developer_sandbox,0)=0")->fetchAll(PDO::FETCH_ASSOC);
$wCount = 0;
foreach ($tenants as $t) {
    $widgetExists->execute([(int)$t['id']]);
    if ($widgetExists->fetch()) continue;
    $widgetIns->execute([(int)$t['id'], 'Widget principal', bin2hex(random_bytes(16))]);
    $wCount++;
}
echo "  + chat_widgets seedeados: $wCount\n";

echo "\n✓ Migración v5 (10 módulos pro) completa.\n";
