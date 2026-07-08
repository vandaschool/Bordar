<?php

declare(strict_types=1);

namespace App\Core;

use App\Config\App;
use Throwable;

final class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleException(Throwable $e): void
    {
        error_log(sprintf('[%s] %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));

        self::respond($e);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            self::respond(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        }
    }

    private static function respond(Throwable $e): void
    {
        if (headers_sent()) {
            return;
        }

        $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
            || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

        http_response_code(500);

        $message = App::isDebug() ? $e->getMessage() : 'خطای غیرمنتظره‌ای رخ داد. لطفاً بعداً دوباره تلاش کنید.';

        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            $payload = ['success' => false, 'message' => $message];
            if (App::isDebug()) {
                $payload['trace'] = $e->getTraceAsString();
            }
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);

            return;
        }

        $view = new View();
        echo $view->render('errors/500', ['message' => $message, 'debug' => App::isDebug() ? $e : null], null);
    }
}
