<?php

declare(strict_types=1);

use App\Config\Database;

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$_ENV['DB_DATABASE'] = $_ENV['DB_TEST_DATABASE'] ?? 'bordar_test';

$pdo = Database::connection();

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ([
    'notifications', 'company_onboarding_progress', 'payments', 'invoices', 'images',
    'application_clarifications', 'application_reviewers', 'documents',
    'applications', 'company_cohorts', 'cohorts', 'companies',
    'otp_codes', 'audit_logs', 'user_sessions', 'users', 'roles', 'migrations',
] as $table) {
    $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

$dir = dirname(__DIR__) . '/src/Database/Migrations';
$files = glob($dir . '/*.sql');
sort($files);

foreach ($files as $file) {
    foreach (array_filter(array_map('trim', explode(';', file_get_contents($file)))) as $statement) {
        $pdo->exec($statement);
    }
}

echo "Test database ready.\n";
