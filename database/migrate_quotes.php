<?php
/**
 * COTIZACIONES — módulo Business / Enterprise
 *
 *  · `quotes`              — cabecera de cotización (cliente, totales, validez, estado)
 *  · `quote_items`         — líneas de items con cantidad, precio, descuento, ITBIS
 *  · `quote_settings`      — configuración por tenant (logo, header, footer, taxes, prefix)
 *  · `quote_taxes`         — impuestos configurables (ITBIS 18%, IVA 21%, etc.)
 *  · `quote_templates`     — plantillas reutilizables con items prearmados
 *  · `quote_template_items`
 *  · `quote_events`        — historial: enviada, vista, aceptada, rechazada
 *  · permisos quotes.*
 *  · seed de impuestos por defecto + settings iniciales por tenant
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_QUOTES') { http_response_code(403); exit('forbidden'); }
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

function tableExistsQuotes(PDO $pdo, string $t): bool {
    return (bool)$pdo->query("SHOW TABLES LIKE " . $pdo->quote($t))->fetch();
}

/* ───────── 1) quote_settings ───────── */
if (!tableExistsQuotes($pdo, 'quote_settings')) {
    $pdo->exec("CREATE TABLE quote_settings (
        tenant_id INT UNSIGNED NOT NULL,
        business_name VARCHAR(180) NULL,
        business_doc VARCHAR(60) NULL,
        business_address VARCHAR(255) NULL,
        business_phone VARCHAR(60) NULL,
        business_email VARCHAR(180) NULL,
        business_website VARCHAR(180) NULL,
        logo_url VARCHAR(500) NULL,
        primary_color VARCHAR(20) NOT NULL DEFAULT '#7c5cff',
        accent_color VARCHAR(20) NOT NULL DEFAULT '#16a34a',
        currency VARCHAR(8) NOT NULL DEFAULT 'DOP',
        currency_symbol VARCHAR(10) NOT NULL DEFAULT 'RD\$',
        decimals TINYINT UNSIGNED NOT NULL DEFAULT 2,
        prefix VARCHAR(20) NOT NULL DEFAULT 'COT-',
        next_number INT UNSIGNED NOT NULL DEFAULT 1,
        validity_days INT UNSIGNED NOT NULL DEFAULT 15,
        default_tax_id INT UNSIGNED NULL,
        default_discount_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
        show_signature TINYINT(1) NOT NULL DEFAULT 1,
        signature_name VARCHAR(180) NULL,
        signature_role VARCHAR(120) NULL,
        intro_text TEXT NULL,
        terms_text TEXT NULL,
        footer_text VARCHAR(255) NULL,
        bank_info TEXT NULL,
        notify_on_accept TINYINT(1) NOT NULL DEFAULT 1,
        notify_email VARCHAR(180) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + quote_settings\n";
} else echo "  • quote_settings ya existe\n";

/* ───────── 2) quote_taxes ───────── */
if (!tableExistsQuotes($pdo, 'quote_taxes')) {
    $pdo->exec("CREATE TABLE quote_taxes (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        slug VARCHAR(40) NOT NULL,
        name VARCHAR(80) NOT NULL,
        rate DECIMAL(6,3) NOT NULL DEFAULT 0,
        is_inclusive TINYINT(1) NOT NULL DEFAULT 0,
        is_default TINYINT(1) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_tenant_slug (tenant_id, slug),
        KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + quote_taxes\n";
} else echo "  • quote_taxes ya existe\n";

/* ───────── 3) quotes ───────── */
if (!tableExistsQuotes($pdo, 'quotes')) {
    $pdo->exec("CREATE TABLE quotes (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        code VARCHAR(40) NOT NULL,
        title VARCHAR(200) NULL,

        client_type ENUM('company','individual','lead') NOT NULL DEFAULT 'company',
        company_id INT UNSIGNED NULL,
        lead_id INT UNSIGNED NULL,
        deal_id INT UNSIGNED NULL,
        client_name VARCHAR(180) NOT NULL,
        client_doc VARCHAR(60) NULL,
        client_email VARCHAR(180) NULL,
        client_phone VARCHAR(60) NULL,
        client_address VARCHAR(255) NULL,
        client_contact VARCHAR(180) NULL,

        currency VARCHAR(8) NOT NULL DEFAULT 'DOP',
        currency_symbol VARCHAR(10) NOT NULL DEFAULT 'RD\$',
        exchange_rate DECIMAL(12,4) NOT NULL DEFAULT 1,

        subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
        discount_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
        discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        taxable_subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
        tax_rate DECIMAL(6,3) NOT NULL DEFAULT 0,
        tax_label VARCHAR(80) NOT NULL DEFAULT 'ITBIS',
        tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        shipping_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        other_charges_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        other_charges_label VARCHAR(80) NULL,
        total DECIMAL(14,2) NOT NULL DEFAULT 0,

        intro TEXT NULL,
        terms TEXT NULL,
        notes TEXT NULL,

        status ENUM('draft','sent','viewed','accepted','rejected','expired','revised','converted')
            NOT NULL DEFAULT 'draft',
        valid_until DATE NULL,
        issued_at DATE NULL,
        sent_at DATETIME NULL,
        viewed_at DATETIME NULL,
        accepted_at DATETIME NULL,
        rejected_at DATETIME NULL,
        rejected_reason VARCHAR(255) NULL,
        accepted_by_name VARCHAR(180) NULL,
        accepted_by_email VARCHAR(180) NULL,

        public_token VARCHAR(64) NOT NULL,
        retainer_id INT UNSIGNED NULL,
        ticket_id INT UNSIGNED NULL,

        owner_id INT UNSIGNED NULL,
        created_by INT UNSIGNED NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        UNIQUE KEY uq_tenant_code (tenant_id, code),
        UNIQUE KEY uq_public_token (public_token),
        KEY idx_tenant (tenant_id),
        KEY idx_status (status),
        KEY idx_company (company_id),
        KEY idx_lead (lead_id),
        KEY idx_owner (owner_id),
        KEY idx_valid_until (valid_until)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + quotes\n";
} else echo "  • quotes ya existe\n";

/* ───────── 4) quote_items ───────── */
if (!tableExistsQuotes($pdo, 'quote_items')) {
    $pdo->exec("CREATE TABLE quote_items (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        quote_id INT UNSIGNED NOT NULL,
        catalog_item_id INT UNSIGNED NULL,
        title VARCHAR(220) NOT NULL,
        description TEXT NULL,
        quantity DECIMAL(12,3) NOT NULL DEFAULT 1,
        unit ENUM('hour','unit','license','service','project','month','custom') NOT NULL DEFAULT 'unit',
        unit_label VARCHAR(40) NULL,
        unit_price DECIMAL(14,2) NOT NULL DEFAULT 0,
        discount_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
        discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        line_subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
        is_taxable TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_tenant (tenant_id),
        KEY idx_quote (quote_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + quote_items\n";
} else echo "  • quote_items ya existe\n";

/* ───────── 5) quote_templates ───────── */
if (!tableExistsQuotes($pdo, 'quote_templates')) {
    $pdo->exec("CREATE TABLE quote_templates (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        name VARCHAR(180) NOT NULL,
        description VARCHAR(255) NULL,
        intro TEXT NULL,
        terms TEXT NULL,
        currency VARCHAR(8) NOT NULL DEFAULT 'DOP',
        validity_days INT UNSIGNED NOT NULL DEFAULT 15,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + quote_templates\n";
} else echo "  • quote_templates ya existe\n";

/* ───────── 6) quote_template_items ───────── */
if (!tableExistsQuotes($pdo, 'quote_template_items')) {
    $pdo->exec("CREATE TABLE quote_template_items (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        template_id INT UNSIGNED NOT NULL,
        title VARCHAR(220) NOT NULL,
        description TEXT NULL,
        quantity DECIMAL(12,3) NOT NULL DEFAULT 1,
        unit ENUM('hour','unit','license','service','project','month','custom') NOT NULL DEFAULT 'unit',
        unit_label VARCHAR(40) NULL,
        unit_price DECIMAL(14,2) NOT NULL DEFAULT 0,
        discount_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
        is_taxable TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY idx_template (template_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + quote_template_items\n";
} else echo "  • quote_template_items ya existe\n";

/* ───────── 7) quote_events ───────── */
if (!tableExistsQuotes($pdo, 'quote_events')) {
    $pdo->exec("CREATE TABLE quote_events (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED NOT NULL,
        quote_id INT UNSIGNED NOT NULL,
        event_type ENUM('created','updated','sent','viewed','accepted','rejected','expired','revised','pdf_downloaded','converted') NOT NULL,
        actor_type ENUM('agent','client','system') NOT NULL DEFAULT 'system',
        actor_name VARCHAR(180) NULL,
        actor_email VARCHAR(180) NULL,
        meta JSON NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_tenant (tenant_id),
        KEY idx_quote (quote_id),
        KEY idx_type (event_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + quote_events\n";
} else echo "  • quote_events ya existe\n";

/* ───────── 8) Permisos ───────── */
$permsToAdd = [
    'quotes.view'    => ['quotes', 'Ver cotizaciones'],
    'quotes.create'  => ['quotes', 'Crear cotizaciones'],
    'quotes.edit'    => ['quotes', 'Editar cotizaciones'],
    'quotes.delete'  => ['quotes', 'Eliminar cotizaciones'],
    'quotes.send'    => ['quotes', 'Enviar cotizaciones al cliente'],
    'quotes.config'  => ['quotes', 'Configurar plantillas, taxes y branding'],
];
$stmt = $pdo->prepare("INSERT IGNORE INTO permissions (slug, module, label) VALUES (?,?,?)");
$added = 0;
foreach ($permsToAdd as $slug => [$mod, $label]) {
    $stmt->execute([$slug, $mod, $label]);
    if ($stmt->rowCount() > 0) $added++;
}
echo "  + permisos sembrados ($added nuevos)\n";

$roles = $pdo->query("SELECT id FROM roles WHERE slug IN ('owner','admin')")->fetchAll(PDO::FETCH_COLUMN);
$permIds = $pdo->query("SELECT id FROM permissions WHERE module='quotes'")->fetchAll(PDO::FETCH_COLUMN);
$grantStmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)");
$grants = 0;
foreach ($roles as $rid) {
    foreach ($permIds as $pid) {
        $grantStmt->execute([(int)$rid, (int)$pid]);
        if ($grantStmt->rowCount() > 0) $grants++;
    }
}
$agentRole = (int)$pdo->query("SELECT id FROM roles WHERE slug='agent' LIMIT 1")->fetchColumn();
if ($agentRole) {
    foreach (['quotes.view','quotes.create','quotes.edit','quotes.send'] as $slug) {
        $pid = (int)$pdo->query("SELECT id FROM permissions WHERE slug=" . $pdo->quote($slug))->fetchColumn();
        if ($pid) $grantStmt->execute([$agentRole, $pid]);
    }
}
echo "  + role_permissions otorgadas: $grants\n";

/* ───────── 9) Seed: impuestos por defecto + settings por tenant ───────── */
$tenants = $pdo->query("SELECT id, name FROM tenants WHERE IFNULL(is_developer_sandbox,0)=0")->fetchAll(PDO::FETCH_ASSOC);

$defaultTaxes = [
    ['itbis-18',  'ITBIS 18% (RD)',     18.000, 0, 1],
    ['iva-21',    'IVA 21% (ES/AR)',    21.000, 0, 0],
    ['iva-16',    'IVA 16% (MX)',       16.000, 0, 0],
    ['vat-7',     'VAT 7%',              7.000, 0, 0],
    ['exento',    'Exento (0%)',         0.000, 0, 0],
];

$insertTax = $pdo->prepare("INSERT IGNORE INTO quote_taxes (tenant_id, slug, name, rate, is_inclusive, is_default, sort_order) VALUES (?,?,?,?,?,?,?)");
$insertSettings = $pdo->prepare("INSERT IGNORE INTO quote_settings
    (tenant_id, business_name, primary_color, currency, currency_symbol, prefix, next_number, validity_days, intro_text, terms_text, footer_text)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)");
$updateDefaultTax = $pdo->prepare("UPDATE quote_settings SET default_tax_id=? WHERE tenant_id=? AND default_tax_id IS NULL");

$seededTaxes = 0; $seededSettings = 0;
foreach ($tenants as $t) {
    $tid = (int)$t['id'];
    foreach ($defaultTaxes as $i => [$slug, $name, $rate, $incl, $def]) {
        $insertTax->execute([$tid, $slug, $name, $rate, $incl, $def, $i]);
        if ($insertTax->rowCount() > 0) $seededTaxes++;
    }
    $insertSettings->execute([
        $tid,
        $t['name'],
        '#7c5cff',
        'DOP',
        'RD$',
        'COT-',
        1,
        15,
        'Estimado cliente, agradecemos la oportunidad de presentar la siguiente cotización para los servicios y/o productos solicitados. Quedamos a disposición para cualquier ajuste necesario.',
        "1. Esta cotización tiene una validez de 15 días desde la fecha de emisión.\n2. Los precios están expresados en la moneda indicada y no incluyen otros impuestos no detallados.\n3. La aceptación de esta cotización implica la conformidad con los términos descritos.\n4. Los plazos de entrega comienzan a contar desde la confirmación del pago inicial cuando aplique.\n5. Cualquier modificación al alcance puede generar ajustes en el monto final.",
        'Gracias por considerarnos.'
    ]);
    if ($insertSettings->rowCount() > 0) $seededSettings++;

    $defaultTaxId = (int)$pdo->query("SELECT id FROM quote_taxes WHERE tenant_id=$tid AND is_default=1 LIMIT 1")->fetchColumn();
    if ($defaultTaxId) $updateDefaultTax->execute([$defaultTaxId, $tid]);
}
echo "  + taxes seed: $seededTaxes · settings seed: $seededSettings\n";

echo "\n✓ Migración de Cotizaciones completa.\n";
echo "  · El módulo 'quotes' está gateado por plan Business / Enterprise (Plan::FEATURES).\n";
echo "  · El super admin puede activar/desactivar 'quotes' por tenant en /admin/tenants/{id}/modules.\n";
