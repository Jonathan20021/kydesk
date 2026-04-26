-- API Tokens for tenant API access
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    token_preview VARCHAR(20) NOT NULL,
    scopes VARCHAR(255) DEFAULT 'read,write',
    last_used_at DATETIME NULL,
    last_ip VARCHAR(45) NULL,
    expires_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (tenant_id),
    KEY (token_hash),
    CONSTRAINT fk_apitok_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_apitok_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Replies on saas_support_tickets (so the tenant can converse with super admin)
CREATE TABLE IF NOT EXISTS saas_support_replies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    author_type ENUM('tenant','super_admin') NOT NULL,
    author_id INT UNSIGNED NULL,
    author_name VARCHAR(150) NULL,
    body MEDIUMTEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (ticket_id),
    CONSTRAINT fk_ssrep_ticket FOREIGN KEY (ticket_id) REFERENCES saas_support_tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
