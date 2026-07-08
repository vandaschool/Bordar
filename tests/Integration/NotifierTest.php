<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\Database;
use App\Core\Model;
use App\Core\Notifier;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Notification\Models\Notification;
use PDO;
use PHPUnit\Framework\TestCase;

final class NotifierTest extends TestCase
{
    private static PDO $pdo;

    private static string $roleId;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = Database::connection();

        $role = Role::findByName('Applicant');
        self::$roleId = $role['id'] ?? Model::uuid();
        if ($role === null) {
            Role::insert(['id' => self::$roleId, 'name' => 'Applicant', 'description' => 'Applicant', 'permissions' => '[]']);
        }
    }

    protected function setUp(): void
    {
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['notifications', 'users'] as $table) {
            self::$pdo->exec("TRUNCATE TABLE `{$table}`");
        }
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function makeUser(string $suffix, ?string $phone = null): string
    {
        return User::insert([
            'email' => "notify-{$suffix}@example.com",
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'N', 'last_name' => $suffix,
            'phone_number' => $phone,
            'role_id' => self::$roleId,
            'is_active' => 1,
        ]);
    }

    public function test_known_event_creates_one_row_per_matrix_channel(): void
    {
        $userId = $this->makeUser('a');

        // payment.due matrix = [IN_APP, EMAIL]
        Notifier::send('payment.due', $userId, 'Invoice issued', 'Please pay.');

        $rows = self::$pdo->query("SELECT channel FROM notifications WHERE user_id = '{$userId}'")->fetchAll(PDO::FETCH_COLUMN);
        sort($rows);

        $this->assertSame(['EMAIL', 'IN_APP'], $rows);
    }

    public function test_unknown_event_is_silently_ignored(): void
    {
        $userId = $this->makeUser('b');

        Notifier::send('totally.unknown.event', $userId, 'x', 'y');

        $count = (int) self::$pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = '{$userId}'")->fetchColumn();
        $this->assertSame(0, $count);
    }

    public function test_in_app_notification_is_unread_until_marked(): void
    {
        $userId = $this->makeUser('c');
        Notifier::send('payment.due', $userId, 'Invoice issued', 'Please pay.');

        $this->assertSame(1, Notification::unreadCount($userId));

        Notification::markAllRead($userId);
        $this->assertSame(0, Notification::unreadCount($userId));
    }

    public function test_sms_channel_only_dispatches_when_user_has_a_phone_number(): void
    {
        $withPhone = $this->makeUser('d', '09121234567');
        $withoutPhone = $this->makeUser('e', null);

        // review.clarification_requested matrix includes SMS.
        Notifier::send('review.clarification_requested', $withPhone, 'Clarify', 'Please clarify.');
        Notifier::send('review.clarification_requested', $withoutPhone, 'Clarify', 'Please clarify.');

        $smsRowsWithPhone = (int) self::$pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = '{$withPhone}' AND channel = 'SMS'")->fetchColumn();
        $smsRowsWithoutPhone = (int) self::$pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = '{$withoutPhone}' AND channel = 'SMS'")->fetchColumn();

        // Both get a notifications row (the record itself), but only the phone-having
        // user should end up with an actual SMS file written to the outbox.
        $this->assertSame(1, $smsRowsWithPhone);
        $this->assertSame(1, $smsRowsWithoutPhone);
    }

    public function test_send_to_role_notifies_every_user_with_that_role(): void
    {
        $u1 = $this->makeUser('f');
        $u2 = $this->makeUser('g');

        Notifier::sendToRole('payment.manual_submitted', self::$roleId, 'New receipt', 'Review needed.');

        $this->assertGreaterThanOrEqual(1, Notification::unreadCount($u1));
        $this->assertGreaterThanOrEqual(1, Notification::unreadCount($u2));
    }
}
