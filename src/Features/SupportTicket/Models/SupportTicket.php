<?php

declare(strict_types=1);

namespace App\Features\SupportTicket\Models;

use App\Core\Model;

final class SupportTicket extends Model
{
    protected static string $table = 'support_tickets';

    public const STATUSES = ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED', 'ESCALATED', 'PENDING_USER_RESPONSE'];

    /** @return array<int, array<string, mixed>> */
    public static function forUser(string $userId): array
    {
        $sql = 'SELECT * FROM `support_tickets` WHERE `user_id` = :user_id AND `deleted_at` IS NULL ORDER BY `updated_at` DESC';
        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function allOpenOrdered(): array
    {
        $sql = "SELECT * FROM `support_tickets`
                WHERE `deleted_at` IS NULL AND `status` NOT IN ('CLOSED', 'RESOLVED')
                ORDER BY FIELD(`priority`, 'URGENT', 'HIGH', 'MEDIUM', 'LOW'), `created_at` ASC";

        return static::pdo()->query($sql)->fetchAll();
    }
}
