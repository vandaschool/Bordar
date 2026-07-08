<?php

declare(strict_types=1);

namespace App\Features\Cohort\Models;

use App\Core\Model;

final class Cohort extends Model
{
    protected static string $table = 'cohorts';

    /** Cohorts an applicant can currently apply to: not archived/completed and before the deadline. */
    public static function openForApplications(): array
    {
        $sql = "SELECT * FROM `cohorts`
                WHERE `deleted_at` IS NULL
                  AND `status` IN ('UPCOMING', 'ACTIVE')
                  AND (`application_deadline` IS NULL OR `application_deadline` >= :now)
                ORDER BY `start_date` ASC";

        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['now' => gmdate('Y-m-d H:i:s')]);

        return $stmt->fetchAll();
    }

    public static function allOrderedByStartDate(): array
    {
        $sql = 'SELECT * FROM `cohorts` WHERE `deleted_at` IS NULL ORDER BY `start_date` DESC';

        return static::pdo()->query($sql)->fetchAll();
    }
}
