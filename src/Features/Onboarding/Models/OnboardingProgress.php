<?php

declare(strict_types=1);

namespace App\Features\Onboarding\Models;

use App\Config\Database;
use PDO;

/** company_onboarding_progress uses a composite primary key (company_id, item_key). */
final class OnboardingProgress
{
    private static function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array<string, string> item_key => completed_at */
    public static function completedItems(string $companyId): array
    {
        $sql = 'SELECT `item_key`, `completed_at` FROM `company_onboarding_progress` WHERE `company_id` = :company_id';
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(['company_id' => $companyId]);

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public static function markComplete(string $companyId, string $itemKey): void
    {
        $sql = 'INSERT IGNORE INTO `company_onboarding_progress` (`company_id`, `item_key`, `completed_at`) VALUES (:company_id, :item_key, :now)';
        self::pdo()->prepare($sql)->execute(['company_id' => $companyId, 'item_key' => $itemKey, 'now' => gmdate('Y-m-d H:i:s')]);
    }

    public static function markIncomplete(string $companyId, string $itemKey): void
    {
        $sql = 'DELETE FROM `company_onboarding_progress` WHERE `company_id` = :company_id AND `item_key` = :item_key';
        self::pdo()->prepare($sql)->execute(['company_id' => $companyId, 'item_key' => $itemKey]);
    }

    public static function isComplete(string $companyId, string $itemKey): bool
    {
        return array_key_exists($itemKey, self::completedItems($companyId));
    }
}
