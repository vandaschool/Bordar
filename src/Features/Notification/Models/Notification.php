<?php

declare(strict_types=1);

namespace App\Features\Notification\Models;

use App\Core\Model;

final class Notification extends Model
{
    protected static string $table = 'notifications';

    /** @return array<int, array<string, mixed>> */
    public static function forUser(string $userId, int $limit = 20): array
    {
        $sql = "SELECT * FROM `notifications`
                WHERE `user_id` = :user_id AND `channel` = 'IN_APP' AND `deleted_at` IS NULL
                ORDER BY `created_at` DESC LIMIT :limit";

        $stmt = static::pdo()->prepare($sql);
        $stmt->bindValue('user_id', $userId);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function unreadCount(string $userId): int
    {
        $sql = "SELECT COUNT(*) FROM `notifications`
                WHERE `user_id` = :user_id AND `channel` = 'IN_APP' AND `is_read` = 0 AND `deleted_at` IS NULL";

        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    public static function markRead(string $id, string $userId): void
    {
        $sql = 'UPDATE `notifications` SET `is_read` = 1 WHERE `id` = :id AND `user_id` = :user_id';
        static::pdo()->prepare($sql)->execute(['id' => $id, 'user_id' => $userId]);
    }

    public static function markAllRead(string $userId): void
    {
        $sql = "UPDATE `notifications` SET `is_read` = 1 WHERE `user_id` = :user_id AND `channel` = 'IN_APP'";
        static::pdo()->prepare($sql)->execute(['user_id' => $userId]);
    }
}
