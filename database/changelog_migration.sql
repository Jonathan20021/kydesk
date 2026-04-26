-- Changelog entries managed from super admin and rendered on /changelog + landing hero pill
CREATE TABLE IF NOT EXISTS changelog_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(40) NOT NULL,
    title VARCHAR(180) NOT NULL,
    summary VARCHAR(255) NULL,
    body MEDIUMTEXT NULL,
    release_type ENUM('major','minor','patch') DEFAULT 'minor',
    hero_pill_label VARCHAR(80) NULL,
    is_featured TINYINT(1) DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_published (is_published, published_at),
    KEY idx_featured (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS changelog_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entry_id INT UNSIGNED NOT NULL,
    item_type ENUM('feature','fix','improvement') DEFAULT 'feature',
    text VARCHAR(500) NOT NULL,
    sort_order INT UNSIGNED DEFAULT 0,
    KEY (entry_id),
    CONSTRAINT fk_chlog_item FOREIGN KEY (entry_id) REFERENCES changelog_entries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
