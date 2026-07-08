<?php

declare(strict_types=1);

namespace App\Features\Auth\Models;

use App\Core\Model;

final class OtpCode extends Model
{
    protected static string $table = 'otp_codes';

    protected static bool $softDeletes = false;

    public const PURPOSE_EMAIL_VERIFY = 'EMAIL_VERIFY';

    public const PURPOSE_PASSWORD_RESET = 'PASSWORD_RESET';

    public static function latestActive(string $userId, string $purpose): ?array
    {
        $sql = 'SELECT * FROM `otp_codes`
                WHERE `user_id` = :user_id AND `purpose` = :purpose
                  AND `consumed_at` IS NULL AND `expires_at` > :now
                ORDER BY `created_at` DESC LIMIT 1';

        $stmt = static::pdo()->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'purpose' => $purpose,
            'now' => gmdate('Y-m-d H:i:s'),
        ]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function invalidateActive(string $userId, string $purpose): void
    {
        $sql = 'UPDATE `otp_codes` SET `consumed_at` = :now
                WHERE `user_id` = :user_id AND `purpose` = :purpose AND `consumed_at` IS NULL';

        static::pdo()->prepare($sql)->execute([
            'now' => gmdate('Y-m-d H:i:s'),
            'user_id' => $userId,
            'purpose' => $purpose,
        ]);
    }

    public static function incrementAttempts(string $id): void
    {
        static::pdo()->prepare('UPDATE `otp_codes` SET `attempts` = `attempts` + 1 WHERE `id` = :id')->execute(['id' => $id]);
    }

    public static function markConsumed(string $id): void
    {
        static::pdo()->prepare('UPDATE `otp_codes` SET `consumed_at` = :now WHERE `id` = :id')->execute([
            'now' => gmdate('Y-m-d H:i:s'),
            'id' => $id,
        ]);
    }
}
