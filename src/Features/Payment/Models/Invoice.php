<?php

declare(strict_types=1);

namespace App\Features\Payment\Models;

use App\Core\Model;

final class Invoice extends Model
{
    protected static string $table = 'invoices';

    public static function forCompanyAndCohort(string $companyId, string $cohortId): ?array
    {
        $sql = 'SELECT * FROM `invoices` WHERE `company_id` = :company_id AND `cohort_id` = :cohort_id AND `deleted_at` IS NULL LIMIT 1';
        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['company_id' => $companyId, 'cohort_id' => $cohortId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
