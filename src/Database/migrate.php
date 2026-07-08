<?php

declare(strict_types=1);

use App\Config\Database;
use App\Lib\SqlSplitter;

require dirname(__DIR__) . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->safeLoad();

$pdo = Database::connection();

$pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS `migrations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) UNIQUE NOT NULL,
        `applied_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    SQL);

$applied = $pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);

$dir = __DIR__ . '/Migrations';
$files = glob($dir . '/*.sql');
sort($files);

foreach ($files as $file) {
    $name = basename($file);

    if (in_array($name, $applied, true)) {
        continue;
    }

    echo "Applying migration: {$name}\n";
    $sql = file_get_contents($file);

    foreach (SqlSplitter::statements($sql) as $statement) {
        $pdo->exec($statement);
    }

    $stmt = $pdo->prepare('INSERT INTO migrations (migration) VALUES (:name)');
    $stmt->execute(['name' => $name]);
}

echo "Migrations up to date.\n";
