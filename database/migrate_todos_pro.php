<?php
/**
 * Kydesk - Todo Pro migration.
 *
 * CLI:        php database/migrate_todos_pro.php
 * Browser:    /kyros-helpdesk/database/migrate_todos_pro.php?token=KYDESK_TODOS_PRO
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== 'KYDESK_TODOS_PRO') {
        http_response_code(403);
        exit('Forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$cfg = $config['db'];

echo "Connecting to {$cfg['host']}:{$cfg['port']}/{$cfg['name']}...\n";
$pdo = new PDO(
    "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}",
    $cfg['user'],
    $cfg['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
echo "OK\n\n";

function tableExists(PDO $pdo, string $table): bool {
    $st = $pdo->prepare('SHOW TABLES LIKE ?');
    $st->execute([$table]);
    return (bool)$st->fetch();
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    if (!tableExists($pdo, $table)) return false;
    $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $st->execute([$column]);
    return (bool)$st->fetch();
}

function indexExists(PDO $pdo, string $table, string $index): bool {
    $st = $pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
    $st->execute([$index]);
    return (bool)$st->fetch();
}

function constraintExists(PDO $pdo, string $constraint): bool {
    $st = $pdo->prepare(
        'SELECT CONSTRAINT_NAME
         FROM information_schema.TABLE_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = ?
         LIMIT 1'
    );
    $st->execute([$constraint]);
    return (bool)$st->fetch();
}

if (!tableExists($pdo, 'todos')) {
    exit("Table todos does not exist. Run the base schema first.\n");
}

$columns = [
    'created_by_id' => 'INT UNSIGNED NULL AFTER user_id',
    'assigned_to_id' => 'INT UNSIGNED NULL AFTER created_by_id',
    'labels' => 'VARCHAR(255) NULL AFTER priority',
    'estimate_minutes' => 'INT UNSIGNED NULL AFTER labels',
    'reminder_at' => 'DATETIME NULL AFTER due_at',
    'reminder_sent_at' => 'DATETIME NULL AFTER reminder_at',
    'email_notifications' => 'TINYINT(1) DEFAULT 1 AFTER reminder_sent_at',
    'sort_order' => 'INT UNSIGNED DEFAULT 0 AFTER email_notifications',
];

foreach ($columns as $column => $definition) {
    if (!columnExists($pdo, 'todos', $column)) {
        $pdo->exec("ALTER TABLE todos ADD COLUMN `$column` $definition");
        echo "+ todos.$column\n";
    }
}

$pdo->exec('UPDATE todos SET created_by_id = user_id WHERE created_by_id IS NULL');
$pdo->exec('UPDATE todos SET assigned_to_id = user_id WHERE assigned_to_id IS NULL');
$pdo->exec('UPDATE todos SET email_notifications = 1 WHERE email_notifications IS NULL');
echo "+ normalized legacy todos\n";

$indexes = [
    'idx_todos_created_by_id' => 'created_by_id',
    'idx_todos_assigned_to_id' => 'assigned_to_id',
    'idx_todos_due_at' => 'due_at',
    'idx_todos_reminder_at' => 'reminder_at',
];
foreach ($indexes as $name => $column) {
    if (!indexExists($pdo, 'todos', $name)) {
        $pdo->exec("ALTER TABLE todos ADD INDEX `$name` (`$column`)");
        echo "+ index $name\n";
    }
}

$constraints = [
    'fk_todos_creator' => 'FOREIGN KEY (created_by_id) REFERENCES users(id) ON DELETE SET NULL',
    'fk_todos_assignee' => 'FOREIGN KEY (assigned_to_id) REFERENCES users(id) ON DELETE SET NULL',
];
foreach ($constraints as $name => $definition) {
    if (!constraintExists($pdo, $name)) {
        try {
            $pdo->exec("ALTER TABLE todos ADD CONSTRAINT `$name` $definition");
            echo "+ constraint $name\n";
        } catch (Throwable $e) {
            echo "! constraint $name skipped: {$e->getMessage()}\n";
        }
    }
}

echo "\nTodo Pro migration complete.\n";
