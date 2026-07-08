<?php

declare(strict_types=1);

namespace App\Features\Review\Models;

use App\Config\Database;
use PDO;

/**
 * application_reviewers uses a composite primary key (application_id,
 * reviewer_user_id), which the generic Core\Model helpers (single-column PK)
 * don't support, so this table gets its own thin data-access class instead.
 */
final class ApplicationReviewer
{
    private static function pdo(): PDO
    {
        return Database::connection();
    }

    public static function find(string $applicationId, string $reviewerUserId): ?array
    {
        $sql = 'SELECT * FROM `application_reviewers`
                WHERE `application_id` = :aid AND `reviewer_user_id` = :rid AND `deleted_at` IS NULL';
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(['aid' => $applicationId, 'rid' => $reviewerUserId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<int, array<string, mixed>> */
    public static function forApplication(string $applicationId): array
    {
        $sql = 'SELECT * FROM `application_reviewers` WHERE `application_id` = :aid AND `deleted_at` IS NULL ORDER BY `assigned_at` ASC';
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(['aid' => $applicationId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function forReviewer(string $reviewerUserId): array
    {
        $sql = 'SELECT * FROM `application_reviewers` WHERE `reviewer_user_id` = :rid AND `deleted_at` IS NULL ORDER BY `assigned_at` DESC';
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(['rid' => $reviewerUserId]);

        return $stmt->fetchAll();
    }

    public static function activeWorkloadCount(string $reviewerUserId): int
    {
        $sql = "SELECT COUNT(*) FROM `application_reviewers`
                WHERE `reviewer_user_id` = :rid AND `deleted_at` IS NULL
                  AND `status` IN ('ASSIGNED', 'DRAFT') AND `conflict_of_interest` = 0";
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(['rid' => $reviewerUserId]);

        return (int) $stmt->fetchColumn();
    }

    public static function create(string $applicationId, string $reviewerUserId): void
    {
        $sql = 'INSERT INTO `application_reviewers` (`application_id`, `reviewer_user_id`, `status`) VALUES (:aid, :rid, :status)';
        self::pdo()->prepare($sql)->execute(['aid' => $applicationId, 'rid' => $reviewerUserId, 'status' => 'ASSIGNED']);
    }

    /** @param array<string, mixed> $data */
    public static function update(string $applicationId, string $reviewerUserId, array $data): void
    {
        $assignments = implode(', ', array_map(static fn (string $c) => "`{$c}` = :{$c}", array_keys($data)));
        $sql = "UPDATE `application_reviewers` SET {$assignments} WHERE `application_id` = :__aid AND `reviewer_user_id` = :__rid";

        $data['__aid'] = $applicationId;
        $data['__rid'] = $reviewerUserId;

        self::pdo()->prepare($sql)->execute($data);
    }

    public static function softDelete(string $applicationId, string $reviewerUserId): void
    {
        self::update($applicationId, $reviewerUserId, ['deleted_at' => gmdate('Y-m-d H:i:s')]);
    }
}
