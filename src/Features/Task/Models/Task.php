<?php

declare(strict_types=1);

namespace App\Features\Task\Models;

use App\Core\Model;

final class Task extends Model
{
    protected static string $table = 'tasks';

    public const STATUSES = ['TODO', 'IN_PROGRESS', 'DONE', 'BLOCKED'];

    /** @return array<int, array<string, mixed>> */
    public static function forCompany(string $companyId): array
    {
        $sql = 'SELECT * FROM `tasks` WHERE `company_id` = :company_id AND `deleted_at` IS NULL ORDER BY `created_at` ASC';
        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['company_id' => $companyId]);

        return $stmt->fetchAll();
    }
}
