<?php

declare(strict_types=1);

namespace App\Config;

final class App
{
    public static function name(): string
    {
        return $_ENV['APP_NAME'] ?? 'Bordar';
    }

    public static function env(): string
    {
        return $_ENV['APP_ENV'] ?? 'production';
    }

    public static function isDebug(): bool
    {
        return filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public static function url(): string
    {
        return rtrim($_ENV['APP_URL'] ?? '', '/');
    }

    public static function key(): string
    {
        $key = $_ENV['APP_KEY'] ?? '';

        return str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7)) : $key;
    }

    public static function timezone(): string
    {
        return 'Asia/Tehran';
    }
}
