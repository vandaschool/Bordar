<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\Database;
use App\Core\AuditLog;
use PDO;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class AuditLogThrottleTest extends TestCase
{
    private static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = Database::connection();
    }

    protected function setUp(): void
    {
        self::$pdo->exec('TRUNCATE TABLE audit_logs');
    }

    public function test_counts_only_matching_action_and_ip_within_window(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        AuditLog::record('auth.login_failed', 'User', null, null, ['email' => 'a@example.com']);
        AuditLog::record('auth.login_failed', 'User', null, null, ['email' => 'a@example.com']);

        $_SERVER['REMOTE_ADDR'] = '203.0.113.99';
        AuditLog::record('auth.login_failed', 'User', null, null, ['email' => 'b@example.com']);

        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        AuditLog::record('auth.login_success', 'User', null, null, ['email' => 'a@example.com']);

        $count = AuditLog::countRecentByAction('auth.login_failed', '203.0.113.10', 15);

        $this->assertSame(2, $count);
    }

    public function test_old_entries_outside_the_window_are_not_counted(): void
    {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.5';

        $stmt = self::$pdo->prepare(
            'INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `ip_address`, `user_agent`, `created_at`)
             VALUES (UUID(), NULL, :action, :entity_type, NULL, :ip, :ua, :created_at)'
        );
        $stmt->execute([
            'action' => 'auth.login_failed',
            'entity_type' => 'User',
            'ip' => '198.51.100.5',
            'ua' => 'phpunit',
            'created_at' => gmdate('Y-m-d H:i:s', time() - 3600),
        ]);

        AuditLog::record('auth.login_failed', 'User', null, null, ['email' => 'c@example.com']);

        $count = AuditLog::countRecentByAction('auth.login_failed', '198.51.100.5', 15);

        $this->assertSame(1, $count);
    }

    public function test_reaching_threshold_can_be_used_to_block_further_attempts(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.0.2.50';

        for ($i = 0; $i < 10; $i++) {
            AuditLog::record('auth.login_failed', 'User', null, null, ['email' => 'brute@example.com']);
        }

        $count = AuditLog::countRecentByAction('auth.login_failed', '192.0.2.50', 15);

        $this->assertGreaterThanOrEqual(10, $count);
    }
}
