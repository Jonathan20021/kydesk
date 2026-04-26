-- Kydesk Developer Portal Migration
-- Adds tables for developer portal: developers, dev_plans, dev_subscriptions,
-- dev_apps, dev_api_tokens, dev_invoices, dev_payments, dev_api_usage
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- Developers (separate accounts: people/teams that consume the API to build their own apps)
CREATE TABLE IF NOT EXISTS developers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    company VARCHAR(150) NULL,
    website VARCHAR(200) NULL,
    country VARCHAR(80) NULL,
    phone VARCHAR(40) NULL,
    avatar VARCHAR(255) NULL,
    bio VARCHAR(500) NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_verified TINYINT(1) DEFAULT 0,
    suspended_at DATETIME NULL,
    suspended_reason VARCHAR(255) NULL,
    last_login_at DATETIME NULL,
    last_login_ip VARCHAR(60) NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Developer plans (separate from tenant plans — these are API usage / app build plans)
CREATE TABLE IF NOT EXISTS dev_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(500) NULL,
    price_monthly DECIMAL(10,2) DEFAULT 0,
    price_yearly DECIMAL(10,2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'USD',
    max_apps INT DEFAULT 1,
    max_requests_month INT DEFAULT 10000,
    max_tokens_per_app INT DEFAULT 5,
    rate_limit_per_min INT DEFAULT 60,
    overage_price_per_1k DECIMAL(10,4) DEFAULT 0,
    features JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_public TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    trial_days INT DEFAULT 0,
    color VARCHAR(20) DEFAULT '#0ea5e9',
    icon VARCHAR(40) DEFAULT 'code',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Developer subscriptions
CREATE TABLE IF NOT EXISTS dev_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    status ENUM('trial','active','past_due','suspended','cancelled','expired') DEFAULT 'trial',
    billing_cycle ENUM('monthly','yearly','lifetime') DEFAULT 'monthly',
    amount DECIMAL(10,2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'USD',
    started_at DATETIME NULL,
    trial_ends_at DATETIME NULL,
    current_period_start DATETIME NULL,
    current_period_end DATETIME NULL,
    cancelled_at DATETIME NULL,
    auto_renew TINYINT(1) DEFAULT 1,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY (developer_id), KEY (plan_id), KEY (status),
    CONSTRAINT fk_devsub_developer FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE CASCADE,
    CONSTRAINT fk_devsub_plan FOREIGN KEY (plan_id) REFERENCES dev_plans(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Developer Apps (each one is a project/integration the developer is building)
-- Each app gets its own isolated tenant (hidden from regular admin tenant list).
CREATE TABLE IF NOT EXISTS dev_apps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_id INT UNSIGNED NOT NULL,
    tenant_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(500) NULL,
    homepage_url VARCHAR(255) NULL,
    callback_url VARCHAR(255) NULL,
    logo VARCHAR(255) NULL,
    environment ENUM('development','staging','production') DEFAULT 'development',
    status ENUM('active','suspended','archived') DEFAULT 'active',
    suspended_reason VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY (developer_id), KEY (status), KEY (tenant_id),
    CONSTRAINT fk_devapp_developer FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE CASCADE,
    CONSTRAINT fk_devapp_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mark developer-sandbox tenants so the regular admin tenant list can hide them.
ALTER TABLE tenants
    ADD COLUMN IF NOT EXISTS is_developer_sandbox TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS dev_app_id INT UNSIGNED NULL;

-- API tokens for developer apps (separate from tenant api_tokens)
CREATE TABLE IF NOT EXISTS dev_api_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_id INT UNSIGNED NOT NULL,
    app_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    token_preview VARCHAR(20) NOT NULL,
    scopes VARCHAR(255) DEFAULT 'read,write',
    last_used_at DATETIME NULL,
    last_ip VARCHAR(45) NULL,
    expires_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (developer_id), KEY (app_id), KEY (token_hash),
    CONSTRAINT fk_devtok_developer FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE CASCADE,
    CONSTRAINT fk_devtok_app FOREIGN KEY (app_id) REFERENCES dev_apps(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Developer Invoices
CREATE TABLE IF NOT EXISTS dev_invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(40) NOT NULL UNIQUE,
    developer_id INT UNSIGNED NOT NULL,
    subscription_id INT UNSIGNED NULL,
    status ENUM('draft','pending','paid','partial','overdue','cancelled','refunded') DEFAULT 'pending',
    subtotal DECIMAL(10,2) DEFAULT 0,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    discount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) DEFAULT 0,
    amount_paid DECIMAL(10,2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'USD',
    description VARCHAR(500) NULL,
    issue_date DATE NULL,
    due_date DATE NULL,
    paid_at DATETIME NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY (developer_id), KEY (subscription_id), KEY (status),
    CONSTRAINT fk_devinv_developer FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE CASCADE,
    CONSTRAINT fk_devinv_sub FOREIGN KEY (subscription_id) REFERENCES dev_subscriptions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Developer Payments
CREATE TABLE IF NOT EXISTS dev_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    method ENUM('manual','card','transfer','paypal','stripe','crypto','other') DEFAULT 'manual',
    reference VARCHAR(150) NULL,
    status ENUM('pending','completed','failed','refunded') DEFAULT 'completed',
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    paid_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (developer_id), KEY (invoice_id), KEY (status),
    CONSTRAINT fk_devpay_developer FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE CASCADE,
    CONSTRAINT fk_devpay_invoice FOREIGN KEY (invoice_id) REFERENCES dev_invoices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API usage tracking per developer/app/day (aggregated)
CREATE TABLE IF NOT EXISTS dev_api_usage (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_id INT UNSIGNED NOT NULL,
    app_id INT UNSIGNED NULL,
    token_id INT UNSIGNED NULL,
    period_date DATE NOT NULL,
    requests INT UNSIGNED DEFAULT 0,
    errors INT UNSIGNED DEFAULT 0,
    bytes_in BIGINT UNSIGNED DEFAULT 0,
    bytes_out BIGINT UNSIGNED DEFAULT 0,
    last_at DATETIME NULL,
    UNIQUE KEY uq_devusage (developer_id, app_id, period_date),
    KEY (developer_id), KEY (app_id), KEY (period_date),
    CONSTRAINT fk_devusg_developer FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE CASCADE,
    CONSTRAINT fk_devusg_app FOREIGN KEY (app_id) REFERENCES dev_apps(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Developer audit logs (actions on the developer portal)
CREATE TABLE IF NOT EXISTS dev_audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity VARCHAR(50) NULL,
    entity_id INT UNSIGNED NULL,
    meta TEXT NULL,
    ip VARCHAR(60) NULL,
    ua VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (developer_id), KEY (entity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;

-- Seed default developer plans
INSERT IGNORE INTO dev_plans (slug, name, description, price_monthly, price_yearly, max_apps, max_requests_month, max_tokens_per_app, rate_limit_per_min, overage_price_per_1k, features, is_active, is_public, is_featured, sort_order, trial_days, color, icon)
VALUES
('dev_free', 'Free', 'Para experimentar y prototipar', 0, 0, 1, 10000, 2, 30, 0,
 '["api_access","sandbox","community_support","basic_analytics"]', 1, 1, 0, 1, 0, '#64748b', 'sparkles'),
('dev_starter', 'Starter', 'Para apps en producción inicial', 19, 190, 3, 100000, 5, 60, 0.5,
 '["api_access","webhooks","email_support","standard_analytics","custom_domain"]', 1, 1, 0, 2, 14, '#0ea5e9', 'rocket'),
('dev_growth', 'Growth', 'Para SaaS con tracción', 49, 490, 10, 1000000, 20, 240, 0.3,
 '["api_access","webhooks","priority_support","advanced_analytics","custom_domain","sla","white_label_basic"]', 1, 1, 1, 3, 14, '#7c5cff', 'zap'),
('dev_scale', 'Scale', 'Para apps a gran escala', 149, 1490, 50, 10000000, 100, 600, 0.15,
 '["api_access","webhooks","dedicated_support","advanced_analytics","custom_domain","sla","white_label","sso"]', 1, 1, 0, 4, 30, '#f59e0b', 'crown'),
('dev_enterprise', 'Enterprise', 'Volumen ilimitado y soporte 24/7', 499, 4990, 999, 999999999, 999, 9999, 0.05,
 '["api_access","webhooks","dedicated_support","advanced_analytics","custom_domain","sla","white_label","sso","on_premise","dedicated_infra"]', 1, 1, 0, 5, 30, '#dc2626', 'shield');

-- Seed developer-portal saas_settings (uses existing saas_settings table)
INSERT IGNORE INTO saas_settings (`key`, `value`) VALUES
('dev_portal_enabled', '1'),
('dev_portal_name', 'Kydesk Developers'),
('dev_portal_tagline', 'API helpdesk para construir tus apps'),
('dev_portal_support_email', 'developers@kyrosrd.com'),
('dev_portal_default_plan', 'dev_free'),
('dev_portal_allow_registration', '1'),
('dev_portal_require_verification', '0'),
('dev_portal_default_trial_days', '14'),
('dev_portal_overage_enabled', '1');
