-- Kydesk SaaS Super Admin Migration
-- Adds tables for super admin panel: super_admins, plans, subscriptions, invoices, payments, saas_settings, support_tickets
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- Super Admins (separate from tenant users, full SaaS access)
CREATE TABLE IF NOT EXISTS super_admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(40) NULL,
    avatar VARCHAR(255) NULL,
    role ENUM('owner','admin','support','billing') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    last_login_at DATETIME NULL,
    last_login_ip VARCHAR(60) NULL,
    two_factor_enabled TINYINT(1) DEFAULT 0,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Configurable SaaS Plans
CREATE TABLE IF NOT EXISTS plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(500) NULL,
    price_monthly DECIMAL(10,2) DEFAULT 0,
    price_yearly DECIMAL(10,2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'USD',
    max_users INT DEFAULT 999,
    max_tickets_month INT DEFAULT 999999,
    max_kb_articles INT DEFAULT 999,
    max_storage_mb INT DEFAULT 5120,
    features JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_public TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    trial_days INT DEFAULT 0,
    color VARCHAR(20) DEFAULT '#7c5cff',
    icon VARCHAR(40) DEFAULT 'rocket',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tenant subscriptions to plans
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
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
    KEY (tenant_id), KEY (plan_id), KEY (status),
    CONSTRAINT fk_sub_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Invoices
CREATE TABLE IF NOT EXISTS invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(40) NOT NULL UNIQUE,
    tenant_id INT UNSIGNED NOT NULL,
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
    KEY (tenant_id), KEY (subscription_id), KEY (status),
    CONSTRAINT fk_inv_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_inv_sub FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments (record of payment events)
CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
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
    KEY (tenant_id), KEY (invoice_id), KEY (status),
    CONSTRAINT fk_pay_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_pay_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Global SaaS settings (key/value)
CREATE TABLE IF NOT EXISTS saas_settings (
    `key` VARCHAR(80) NOT NULL PRIMARY KEY,
    `value` TEXT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Super admin audit/activity logs
CREATE TABLE IF NOT EXISTS super_audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    super_admin_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity VARCHAR(50) NULL,
    entity_id INT UNSIGNED NULL,
    meta TEXT NULL,
    ip VARCHAR(60) NULL,
    ua VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (super_admin_id), KEY (entity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Support tickets between tenants and SaaS provider
CREATE TABLE IF NOT EXISTS saas_support_tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    subject VARCHAR(200) NOT NULL,
    body MEDIUMTEXT NULL,
    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    status ENUM('open','in_progress','waiting','resolved','closed') DEFAULT 'open',
    assigned_to INT UNSIGNED NULL,
    resolved_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY (tenant_id), KEY (status), KEY (assigned_to),
    CONSTRAINT fk_sst_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add columns to tenants if not present (run separately if needed)
ALTER TABLE tenants
    ADD COLUMN IF NOT EXISTS subscription_id INT UNSIGNED NULL AFTER plan,
    ADD COLUMN IF NOT EXISTS billing_email VARCHAR(150) NULL AFTER support_email,
    ADD COLUMN IF NOT EXISTS country VARCHAR(80) NULL AFTER website,
    ADD COLUMN IF NOT EXISTS suspended_at DATETIME NULL AFTER is_active,
    ADD COLUMN IF NOT EXISTS suspended_reason VARCHAR(255) NULL AFTER suspended_at,
    ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER suspended_reason;

SET FOREIGN_KEY_CHECKS=1;

-- Seed default plans
INSERT IGNORE INTO plans (slug, name, description, price_monthly, price_yearly, max_users, max_tickets_month, max_kb_articles, features, is_active, is_public, is_featured, sort_order, trial_days, color, icon)
VALUES
('starter', 'Starter', 'Para equipos pequeños que recién inician', 0, 0, 3, 100, 10,
 '["tickets","kb","notes","todos","companies","assets","reports","users","roles","settings"]', 1, 1, 0, 1, 14, '#3b82f6', 'rocket'),
('pro', 'Pro', 'Para equipos en crecimiento', 29, 290, 25, 5000, 200,
 '["tickets","kb","notes","todos","companies","assets","reports","users","roles","settings","automations","sla","audit"]', 1, 1, 1, 2, 14, '#7c5cff', 'zap'),
('business', 'Business', 'Para empresas medianas', 79, 790, 100, 20000, 500,
 '["tickets","kb","notes","todos","companies","assets","reports","users","roles","settings","automations","sla","audit"]', 1, 1, 0, 3, 14, '#a78bfa', 'building'),
('enterprise', 'Enterprise', 'Solución completa con SSO y branding', 199, 1990, 9999, 999999, 9999,
 '["tickets","kb","notes","todos","companies","assets","reports","users","roles","settings","automations","sla","audit","sso","custom_branding"]', 1, 1, 0, 4, 30, '#f59e0b', 'crown');

-- Seed default SaaS settings
INSERT IGNORE INTO saas_settings (`key`, `value`) VALUES
('saas_name', 'Kydesk'),
('saas_company', 'Kydesk SaaS'),
('saas_support_email', 'support@kydesk.com'),
('saas_billing_email', 'billing@kydesk.com'),
('saas_currency', 'USD'),
('saas_tax_rate', '0'),
('saas_invoice_prefix', 'INV'),
('saas_default_plan', 'pro'),
('saas_default_trial_days', '14'),
('saas_allow_registration', '1'),
('saas_terms_url', ''),
('saas_privacy_url', '');

-- Default super admin is seeded by database/migrate_superadmin.php
-- Email: superadmin@kydesk.com  Password: superadmin123 (change after first login!)
