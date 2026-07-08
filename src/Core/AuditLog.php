<?php

declare(strict_types=1);

namespace App\Core;

use App\Config\Database;

final class AuditLog
{
    /** @param array<string, mixed>|null $old @param array<string, mixed>|null $new */
    public static function record(
        string $action,
        string $entityType,
        ?string $entityId = null,
        ?array $old = null,
        ?array $new = null,
        ?string $userId = null
    ): void {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'INSERT INTO `audit_logs`
                (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_value`, `new_value`, `ip_address`, `user_agent`)
             VALUES (:id, :user_id, :action, :entity_type, :entity_id, :old_value, :new_value, :ip, :ua)'
        );

        $stmt->execute([
            'id' => Model::uuid(),
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_value' => $old === null ? null : json_encode($old, JSON_UNESCAPED_UNICODE),
            'new_value' => $new === null ? null : json_encode($new, JSON_UNESCAPED_UNICODE),
            'ip' => Request::ip(),
            'ua' => Request::userAgent(),
        ]);
    }

    /** Used for lightweight brute-force throttling (e.g. failed login attempts per IP). */
    public static function countRecentByAction(string $action, string $ipAddress, int $withinMinutes): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM `audit_logs`
             WHERE `action` = :action AND `ip_address` = :ip AND `created_at` >= :since'
        );

        $stmt->execute([
            'action' => $action,
            'ip' => $ipAddress,
            'since' => gmdate('Y-m-d H:i:s', time() - $withinMinutes * 60),
        ]);

        return (int) $stmt->fetchColumn();
    }
}
