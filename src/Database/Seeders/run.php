<?php

declare(strict_types=1);

use App\Config\Constants;
use App\Config\Database;
use App\Core\Model;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3));
$dotenv->safeLoad();

$pdo = Database::connection();

$permissionsByRole = [
    Constants::ROLE_ADMIN => ['*'],
    Constants::ROLE_APPLICANT => [
        'application:view-own', 'application:edit-own', 'document:view-own',
        'payment:view-own', 'mentor-session:book', 'profile:edit', 'ticket:create', 'ticket:view-own',
    ],
    Constants::ROLE_MENTOR => [
        'application:view-assigned', 'document:view-assigned', 'availability:manage', 'ticket:view-own', 'ticket:reply',
    ],
    Constants::ROLE_REVIEWER => [
        'application:view-assigned', 'document:view-assigned', 'review:score',
    ],
    Constants::ROLE_VENDOR => [],
    Constants::ROLE_INVESTOR => [],
];

foreach ($permissionsByRole as $roleName => $permissions) {
    $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = :name');
    $stmt->execute(['name' => $roleName]);
    $existing = $stmt->fetch();

    if ($existing) {
        $pdo->prepare('UPDATE roles SET permissions = :perms WHERE id = :id')->execute([
            'perms' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
            'id' => $existing['id'],
        ]);
        echo "Role updated: {$roleName}\n";

        continue;
    }

    $id = Model::uuid();
    $pdo->prepare('INSERT INTO roles (id, name, description, permissions) VALUES (:id, :name, :description, :perms)')->execute([
        'id' => $id,
        'name' => $roleName,
        'description' => $roleName . ' role',
        'perms' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
    ]);
    echo "Role created: {$roleName}\n";
}

$adminEmail = $_ENV['ADMIN_SEED_EMAIL'] ?? 'admin@bordar.local';
$adminPassword = $_ENV['ADMIN_SEED_PASSWORD'] ?? 'Admin@12345';

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$stmt->execute(['email' => $adminEmail]);

if ($stmt->fetch() === false) {
    $adminRoleId = $pdo->query("SELECT id FROM roles WHERE name = 'Admin'")->fetchColumn();

    $pdo->prepare(
        'INSERT INTO users (id, email, password_hash, first_name, last_name, role_id, is_active, email_verified_at)
         VALUES (:id, :email, :password_hash, :first_name, :last_name, :role_id, 1, :verified_at)'
    )->execute([
        'id' => Model::uuid(),
        'email' => $adminEmail,
        'password_hash' => password_hash($adminPassword, PASSWORD_BCRYPT),
        'first_name' => 'مدیر',
        'last_name' => 'سیستم',
        'role_id' => $adminRoleId,
        'verified_at' => gmdate('Y-m-d H:i:s'),
    ]);

    echo "Admin user created: {$adminEmail} / {$adminPassword} (2FA setup required on first login)\n";
} else {
    echo "Admin user already exists: {$adminEmail}\n";
}

echo "Seeding complete.\n";
