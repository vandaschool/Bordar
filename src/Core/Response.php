<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function error(string $message, int $status = 400, array $errors = []): never
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    public static function success(array $data = [], string $message = ''): never
    {
        self::json(array_merge([
            'success' => true,
            'message' => $message,
        ], $data), 200);
    }

    public static function redirect(string $to): never
    {
        $base = \App\Config\App::url();
        $location = str_starts_with($to, 'http') ? $to : $base . '/' . ltrim($to, '/');
        header('Location: ' . $location);
        exit;
    }
}
