<?php

declare(strict_types=1);

namespace App\Lib;

/**
 * Minimal mail sender. Uses PHP mail() when SMTP-less sending is available,
 * and always writes an .eml copy under STORAGE_PATH/mail for local/dev
 * inspection since shared-hosting SMTP is not configured in Phase 1.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $bodyHtml): bool
    {
        self::writeToOutbox($to, $subject, $bodyHtml);

        if (($_ENV['APP_ENV'] ?? 'local') === 'local') {
            // Avoid depending on a real MTA in local/dev environments.
            return true;
        }

        $from = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@bordar.local';
        $fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Bordar';

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$from}>\r\n";

        return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $bodyHtml, $headers);
    }

    private static function writeToOutbox(string $to, string $subject, string $bodyHtml): void
    {
        $dir = rtrim($_ENV['STORAGE_PATH'] ?? sys_get_temp_dir(), '/') . '/mail';

        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $file = $dir . '/' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.html';
        $content = "<!-- To: {$to} | Subject: {$subject} -->\n" . $bodyHtml;
        file_put_contents($file, $content);
    }
}
