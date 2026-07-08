<?php

declare(strict_types=1);

namespace App\Lib;

/**
 * Minimal SMS sender stub. No real gateway is configured for local/dev, so
 * outgoing messages are written to STORAGE_PATH/sms for inspection, mirroring
 * Mailer's approach. Swap the body of send() for a real gateway client
 * (e.g. Kavenegar/Ghasedak) at deploy time without touching call sites.
 */
final class SmsSender
{
    public static function send(string $toMobile, string $message): bool
    {
        $dir = rtrim($_ENV['STORAGE_PATH'] ?? sys_get_temp_dir(), '/') . '/sms';

        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $file = $dir . '/' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.txt';
        file_put_contents($file, "To: {$toMobile}\n{$message}");

        return true;
    }
}
