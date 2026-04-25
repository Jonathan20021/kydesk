-- Kydesk Helpdesk — Esquema Multi-Tenant v3
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS automations;
DROP TABLE IF EXISTS sla_policies;
DROP TABLE IF EXISTS kb_articles;
DROP TABLE IF EXISTS kb_categories;
DROP TABLE IF EXISTS assets;
DROP TABLE IF EXISTS contacts;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS ticket_escalations;
DROP TABLE IF EXISTS ticket_attachments;
DROP TABLE IF EXISTS ticket_comments;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS ticket_categories;
DROP TABLE IF EXISTS todos;
DROP TABLE IF EXISTS todo_lists;
DROP TABLE IF EXISTS notes;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS tenants;

CREATE TABLE tenants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    logo VARCHAR(255) NULL,
    primary_color VARCHAR(20) DEFAULT '#0f172a',
    timezone VARCHAR(80) DEFAULT 'America/Santo_Domingo',
    plan ENUM('free','pro','business','enterprise') DEFAULT 'pro',
    is_active TINYINT(1) DEFAULT 1,
    support_email VARCHAR(150) NULL,
    website VARCHAR(200) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    description VARCHAR(255) NULL,
    is_system TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_role_slug_tenant (tenant_id, slug),
    KEY (tenant_id),
    CONSTRAINT fk_roles_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    module VARCHAR(50) NOT NULL,
    label VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(40) NULL,
    avatar VARCHAR(255) NULL,
    title VARCHAR(100) NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_technician TINYINT(1) DEFAULT 0,
    last_login_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY (tenant_id), KEY (role_id),
    CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE companies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    industry VARCHAR(100) NULL,
    size VARCHAR(50) NULL,
    website VARCHAR(200) NULL,
    phone VARCHAR(40) NULL,
    address VARCHAR(255) NULL,
    notes TEXT NULL,
    tier ENUM('standard','premium','enterprise') DEFAULT 'standard',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY (tenant_id),
    CONSTRAINT fk_companies_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE contacts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(40) NULL,
    title VARCHAR(100) NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (tenant_id), KEY (company_id),
    CONSTRAINT fk_contacts_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_contacts_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE assets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    type VARCHAR(60) NOT NULL,
    serial VARCHAR(100) NULL,
    model VARCHAR(100) NULL,
    status ENUM('active','maintenance','retired','lost') DEFAULT 'active',
    assigned_to INT UNSIGNED NULL,
    purchase_date DATE NULL,
    warranty_until DATE NULL,
    location VARCHAR(150) NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (tenant_id), KEY (company_id), KEY (assigned_to),
    CONSTRAINT fk_assets_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_assets_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    CONSTRAINT fk_assets_user FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sla_policies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    priority ENUM('low','medium','high','urgent') NOT NULL,
    response_minutes INT UNSIGNED DEFAULT 60,
    resolve_minutes INT UNSIGNED DEFAULT 1440,
    active TINYINT(1) DEFAULT 1,
    description VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (tenant_id),
    CONSTRAINT fk_sla_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE kb_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(40) DEFAULT 'book-open',
    color VARCHAR(20) DEFAULT '#0f172a',
    description VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (tenant_id),
    CONSTRAINT fk_kbcat_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE kb_articles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NULL,
    author_id INT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    excerpt VARCHAR(500) NULL,
    body MEDIUMTEXT NULL,
    status ENUM('draft','published') DEFAULT 'draft',
    visibility ENUM('internal','public') DEFAULT 'internal',
    views INT UNSIGNED DEFAULT 0,
    helpful_yes INT UNSIGNED DEFAULT 0,
    helpful_no INT UNSIGNED DEFAULT 0,
    published_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY (tenant_id), KEY (category_id), KEY (author_id), KEY (status),
    CONSTRAINT fk_kb_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_kb_category FOREIGN KEY (category_id) REFERENCES kb_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_kb_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE automations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    trigger_event ENUM('ticket.created','ticket.updated','ticket.sla_breach','ticket.escalated','ticket.resolved') NOT NULL,
    conditions JSON NULL,
    actions JSON NULL,
    active TINYINT(1) DEFAULT 1,
    run_count INT UNSIGNED DEFAULT 0,
    last_run_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (tenant_id),
    CONSTRAINT fk_autom_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_autom_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ticket_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20) DEFAULT '#0f172a',
    icon VARCHAR(40) DEFAULT 'tag',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (tenant_id),
    CONSTRAINT fk_cat_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    description MEDIUMTEXT NULL,
    category_id INT UNSIGNED NULL,
    company_id INT UNSIGNED NULL,
    asset_id INT UNSIGNED NULL,
    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    status ENUM('open','in_progress','on_hold','resolved','closed') DEFAULT 'open',
    channel ENUM('portal','email','phone','chat','internal') DEFAULT 'portal',
    requester_name VARCHAR(150) NULL,
    requester_email VARCHAR(150) NULL,
    requester_phone VARCHAR(40) NULL,
    requester_user_id INT UNSIGNED NULL,
    assigned_to INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    escalation_level TINYINT UNSIGNED DEFAULT 0,
    sla_due_at DATETIME NULL,
    sla_breached TINYINT(1) DEFAULT 0,
    first_response_at DATETIME NULL,
    resolved_at DATETIME NULL,
    closed_at DATETIME NULL,
    satisfaction_rating TINYINT NULL,
    tags VARCHAR(255) NULL,
    public_token VARCHAR(64) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ticket_code (tenant_id, code),
    KEY (tenant_id), KEY (status), KEY (priority), KEY (assigned_to), KEY (category_id), KEY (company_id),
    CONSTRAINT fk_tick_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_tick_category FOREIGN KEY (category_id) REFERENCES ticket_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_tick_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    CONSTRAINT fk_tick_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE SET NULL,
    CONSTRAINT fk_tick_assigned FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_tick_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ticket_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    ticket_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    author_name VARCHAR(150) NULL,
    author_email VARCHAR(150) NULL,
    body MEDIUMTEXT NOT NULL,
    is_internal TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (ticket_id), KEY (tenant_id),
    CONSTRAINT fk_cmt_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_cmt_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_cmt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ticket_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    ticket_id INT UNSIGNED NOT NULL,
    comment_id INT UNSIGNED NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime VARCHAR(120) NULL,
    size INT UNSIGNED DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (ticket_id),
    CONSTRAINT fk_att_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_att_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ticket_escalations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    ticket_id INT UNSIGNED NOT NULL,
    from_user_id INT UNSIGNED NULL,
    to_user_id INT UNSIGNED NULL,
    from_level TINYINT UNSIGNED DEFAULT 0,
    to_level TINYINT UNSIGNED DEFAULT 1,
    reason VARCHAR(500) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (ticket_id),
    CONSTRAINT fk_esc_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_esc_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    body MEDIUMTEXT NULL,
    color VARCHAR(20) DEFAULT 'yellow',
    pinned TINYINT(1) DEFAULT 0,
    tags VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY (tenant_id), KEY (user_id),
    CONSTRAINT fk_notes_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_notes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE todo_lists (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20) DEFAULT '#0f172a',
    icon VARCHAR(40) DEFAULT 'list',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (tenant_id), KEY (user_id),
    CONSTRAINT fk_tlist_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_tlist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE todos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    list_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    completed TINYINT(1) DEFAULT 0,
    completed_at DATETIME NULL,
    due_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY (tenant_id), KEY (user_id), KEY (list_id),
    CONSTRAINT fk_todos_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_todos_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_todos_list FOREIGN KEY (list_id) REFERENCES todo_lists(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity VARCHAR(50) NULL,
    entity_id INT UNSIGNED NULL,
    meta TEXT NULL,
    ip VARCHAR(60) NULL,
    ua VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (tenant_id), KEY (entity),
    CONSTRAINT fk_audit_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;
