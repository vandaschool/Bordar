<?php

declare(strict_types=1);

namespace App\Features\Review\Models;

use App\Core\Model;

final class ApplicationClarification extends Model
{
    protected static string $table = 'application_clarifications';

    protected static bool $softDeletes = false;

    /** @return array<int, array<string, mixed>> */
    public static function forApplication(string $applicationId): array
    {
        $sql = 'SELECT * FROM `application_clarifications` WHERE `application_id` = :aid ORDER BY `requested_at` ASC';
        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['aid' => $applicationId]);

        return $stmt->fetchAll();
    }

    public static function hasPending(string $applicationId): bool
    {
        $sql = 'SELECT COUNT(*) FROM `application_clarifications` WHERE `application_id` = :aid AND `response` IS NULL';
        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['aid' => $applicationId]);

        return ((int) $stmt->fetchColumn()) > 0;
    }
}
