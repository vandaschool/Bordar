<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Integration tests run against a dedicated test database so they never
// touch local/dev data.
$_ENV['DB_DATABASE'] = $_ENV['DB_TEST_DATABASE'] ?? 'bordar_test';
putenv('DB_DATABASE=' . $_ENV['DB_DATABASE']);

date_default_timezone_set('Asia/Tehran');
