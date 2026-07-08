<?php

declare(strict_types=1);

namespace App\Core;

use App\Lib\Sanitizer;

final class Request
{
    /** @return array<string, mixed> */
    public static function post(): array
    {
        return Sanitizer::sanitizeArray($_POST);
    }

    /** @return array<string, mixed> */
    public static function query(): array
    {
        return Sanitizer::sanitizeArray($_GET);
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;

        return is_string($value) ? Sanitizer::string($value) : $value;
    }

    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isJson(): bool
    {
        return str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
    }

    /** @return array<string, mixed> */
    public static function json(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);

        return is_array($data) ? Sanitizer::sanitizeArray($data) : [];
    }

    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function userAgent(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512);
    }

    public static function csrfToken(): ?string
    {
        return $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }
}
