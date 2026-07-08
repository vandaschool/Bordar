<?php

declare(strict_types=1);

namespace App\Features\Cohort\Models;

use App\Config\Database;
use PDO;

/** company_cohorts uses a composite primary key (company_id, cohort_id). */
final class CompanyCohort
{
    private static function pdo(): PDO
    {
        return Database::connection();
    }

    /** Creates the pivot row on first application, or updates its status thereafter. */
    public static function upsertStatus(string $companyId, string $cohortId, string $status): void
    {
        $joinedAt = $status === 'ACCEPTED' ? gmdate('Y-m-d H:i:s') : null;

        $sql = 'INSERT INTO `company_cohorts` (`company_id`, `cohort_id`, `status`, `joined_at`)
                VALUES (:company_id, :cohort_id, :status, :joined_at)
                ON DUPLICATE KEY UPDATE `status` = VALUES(`status`),
                    `joined_at` = COALESCE(VALUES(`joined_at`), `joined_at`)';

        self::pdo()->prepare($sql)->execute([
            'company_id' => $companyId,
            'cohort_id' => $cohortId,
            'status' => $status,
            'joined_at' => $joinedAt,
        ]);
    }

    public static function find(string $companyId, string $cohortId): ?array
    {
        $sql = 'SELECT * FROM `company_cohorts` WHERE `company_id` = :company_id AND `cohort_id` = :cohort_id';
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(['company_id' => $companyId, 'cohort_id' => $cohortId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
