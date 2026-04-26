-- Email logging + saas_settings keys for mail
CREATE TABLE IF NOT EXISTS email_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    driver VARCHAR(20) NOT NULL DEFAULT 'resend',
    to_addr TEXT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    status ENUM('sent','failed') NOT NULL DEFAULT 'sent',
    message_id VARCHAR(120) DEFAULT NULL,
    error TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_status (status),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default mail settings if missing
INSERT INTO saas_settings (`key`, `value`) VALUES
    ('mail_driver', 'resend'),
    ('mail_from_email', 'no-reply@kyrosrd.com'),
    ('mail_from_name', 'Kydesk Helpdesk'),
    ('mail_reply_to', 'jonathansandoval@kyrosrd.com'),
    ('resend_api_key', 're_UdsKH5CN_3QKk1NixfgQrCvaUfnUHufpt'),
    ('smtp_host', ''),
    ('smtp_port', '587'),
    ('smtp_user', ''),
    ('smtp_pass', ''),
    ('smtp_secure', 'tls')
ON DUPLICATE KEY UPDATE `key` = `key`;
