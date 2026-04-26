-- Kydesk Developer Portal — V2 enhancements
-- Adds per-developer quota overrides + saas_settings keys
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- Per-developer overrides (NULL means "use plan defaults")
ALTER TABLE developers
    ADD COLUMN IF NOT EXISTS custom_max_apps INT NULL AFTER bio,
    ADD COLUMN IF NOT EXISTS custom_max_requests_month INT NULL AFTER custom_max_apps,
    ADD COLUMN IF NOT EXISTS custom_max_tokens_per_app INT NULL AFTER custom_max_requests_month,
    ADD COLUMN IF NOT EXISTS custom_rate_limit_per_min INT NULL AFTER custom_max_tokens_per_app,
    ADD COLUMN IF NOT EXISTS quota_alerts_enabled TINYINT(1) DEFAULT 1 AFTER custom_rate_limit_per_min;

-- API request log for rate limiting (rolling window per minute)
CREATE TABLE IF NOT EXISTS dev_api_request_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_id INT UNSIGNED NOT NULL,
    app_id INT UNSIGNED NULL,
    token_id INT UNSIGNED NULL,
    method VARCHAR(10) NOT NULL,
    path VARCHAR(255) NOT NULL,
    status_code SMALLINT UNSIGNED DEFAULT 0,
    duration_ms INT UNSIGNED DEFAULT 0,
    ip VARCHAR(45) NULL,
    ua VARCHAR(255) NULL,
    created_at DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
    KEY idx_dev_recent (developer_id, created_at),
    KEY idx_app_recent (app_id, created_at),
    KEY (token_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Webhooks (developer can register webhooks for events)
CREATE TABLE IF NOT EXISTS dev_webhooks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_id INT UNSIGNED NOT NULL,
    app_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    url VARCHAR(500) NOT NULL,
    secret VARCHAR(128) NULL,
    events VARCHAR(500) NOT NULL DEFAULT '*',
    is_active TINYINT(1) DEFAULT 1,
    last_triggered_at DATETIME NULL,
    last_status_code SMALLINT UNSIGNED NULL,
    failure_count INT UNSIGNED DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY (developer_id), KEY (app_id),
    CONSTRAINT fk_devwh_developer FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE CASCADE,
    CONSTRAINT fk_devwh_app FOREIGN KEY (app_id) REFERENCES dev_apps(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;

-- New saas settings for V2
INSERT IGNORE INTO saas_settings (`key`, `value`) VALUES
('dev_portal_enforce_quota', '1'),
('dev_portal_enforce_rate_limit', '1'),
('dev_portal_block_on_overage', '0'),
('dev_portal_alert_at_pct', '80'),
('dev_portal_company_label', 'Kydesk Developers');
