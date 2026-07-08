<?php

declare(strict_types=1);

namespace App\Features\Mentor\Models;

use App\Core\Model;

final class MentorSession extends Model
{
    protected static string $table = 'mentor_sessions';

    /** @return array<int, array<string, mixed>> Active (non-cancelled) sessions for a mentor within [from, to). */
    public static function activeForMentorInRange(string $mentorId, string $fromUtc, string $toUtc): array
    {
        $sql = "SELECT * FROM `mentor_sessions`
                WHERE `mentor_id` = :mentor_id AND `deleted_at` IS NULL
                  AND `status` IN ('SCHEDULED', 'COMPLETED')
                  AND `start_time` >= :from AND `start_time` < :to
                ORDER BY `start_time` ASC";

        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['mentor_id' => $mentorId, 'from' => $fromUtc, 'to' => $toUtc]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function forCompany(string $companyId): array
    {
        $sql = 'SELECT * FROM `mentor_sessions` WHERE `company_id` = :company_id AND `deleted_at` IS NULL ORDER BY `start_time` DESC';
        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['company_id' => $companyId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function forMentor(string $mentorId): array
    {
        $sql = "SELECT * FROM `mentor_sessions`
                WHERE `mentor_id` = :mentor_id AND `deleted_at` IS NULL AND `status` != 'CANCELED'
                ORDER BY `start_time` DESC";
        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['mentor_id' => $mentorId]);

        return $stmt->fetchAll();
    }
}
