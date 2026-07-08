<?php

declare(strict_types=1);

namespace App\Features\Document\Models;

use App\Core\Model;

final class Document extends Model
{
    protected static string $table = 'documents';

    /** @return array<int, array<string, mixed>> Latest version of every document "slot" for a company. */
    public static function forCompany(string $companyId): array
    {
        $sql = "SELECT d.* FROM `documents` d
                WHERE d.`company_id` = :company_id AND d.`deleted_at` IS NULL
                  AND NOT EXISTS (
                      SELECT 1 FROM `documents` d2
                      WHERE d2.`replaces_document_id` = d.`id` AND d2.`deleted_at` IS NULL
                  )
                ORDER BY d.`created_at` DESC";

        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['company_id' => $companyId]);

        return $stmt->fetchAll();
    }
}
