<?php

declare(strict_types=1);

namespace App\Lib;

final class Sanitizer
{
    public static function string(string $value): string
    {
        $value = trim($value);

        return filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW) ?: '';
    }

    public static function html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function email(string $value): string
    {
        $clean = filter_var(trim($value), FILTER_SANITIZE_EMAIL) ?: '';

        return mb_strtolower($clean);
    }

    /** @param array<string, mixed> $input */
    public static function sanitizeArray(array $input): array
    {
        $result = [];

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::sanitizeArray($value);
            } elseif (is_string($value)) {
                $result[$key] = self::string($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public static function isValidEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** Accepts Iranian mobile numbers in local (09xxxxxxxxx) or E.164 (+989xxxxxxxxx) form. */
    public static function isValidIranianMobile(string $value): bool
    {
        return (bool) preg_match('/^(?:\+98|0)9\d{9}$/', $value);
    }
}
