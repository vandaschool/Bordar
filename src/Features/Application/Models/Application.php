<?php

declare(strict_types=1);

namespace App\Features\Application\Models;

use App\Core\Model;

final class Application extends Model
{
    protected static string $table = 'applications';

    public static function activeForCompanyAndCohort(string $companyId, string $cohortId): ?array
    {
        $sql = "SELECT * FROM `applications`
                WHERE `company_id` = :company_id AND `cohort_id` = :cohort_id
                  AND `deleted_at` IS NULL AND `status` != 'WITHDRAWN'
                ORDER BY `created_at` DESC LIMIT 1";

        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['company_id' => $companyId, 'cohort_id' => $cohortId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<int, array<string, mixed>> */
    public static function forCompany(string $companyId): array
    {
        $sql = 'SELECT * FROM `applications` WHERE `company_id` = :company_id AND `deleted_at` IS NULL ORDER BY `created_at` DESC';
        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['company_id' => $companyId]);

        return $stmt->fetchAll();
    }

    /** @param array<int, string> $statuses @return array<int, array<string, mixed>> */
    public static function byStatuses(array $statuses): array
    {
        $placeholders = implode(', ', array_fill(0, count($statuses), '?'));
        $sql = "SELECT * FROM `applications` WHERE `deleted_at` IS NULL AND `status` IN ({$placeholders}) ORDER BY `submitted_at` ASC";

        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(array_values($statuses));

        return $stmt->fetchAll();
    }
}
