<?php

declare(strict_types=1);

namespace App\Lib;

use App\Config\App;

/** HMAC-signed, time-limited tokens for document download links (Document Vault access control). */
final class SignedUrl
{
    public static function sign(string $resourceId, int $ttlSeconds = 300): array
    {
        $expires = time() + $ttlSeconds;
        $signature = self::signature($resourceId, $expires);

        return ['expires' => $expires, 'signature' => $signature];
    }

    public static function verify(string $resourceId, int $expires, string $signature): bool
    {
        if ($expires < time()) {
            return false;
        }

        return hash_equals(self::signature($resourceId, $expires), $signature);
    }

    private static function signature(string $resourceId, int $expires): string
    {
        return hash_hmac('sha256', $resourceId . '|' . $expires, App::key());
    }
}
