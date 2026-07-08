<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    private const BASE_PATHS = [
        __DIR__ . '/../Features',
        __DIR__ . '/../Views',
    ];

    public function render(string $view, array $data = [], ?string $layout = 'layout'): string
    {
        $content = $this->renderRaw($view, $data);

        if ($layout === null) {
            return $content;
        }

        return $this->renderRaw('layout/' . $layout, array_merge($data, ['content' => $content]));
    }

    private function renderRaw(string $view, array $viewData): string
    {
        $path = $this->resolve($view);

        // Extracted under a throwaway local name so a view-data key literally
        // called "data" (several views pass one) can't collide with this
        // method's own parameter and get silently dropped by EXTR_SKIP.
        extract($viewData, EXTR_SKIP);

        ob_start();
        require $path;

        return (string) ob_get_clean();
    }

    private function resolve(string $view): string
    {
        $relative = str_replace('.', '/', $view) . '.php';

        // Feature views live at Features/{Feature}/Views/{name}.php
        if (str_contains($view, '::')) {
            [$feature, $name] = explode('::', $view, 2);
            $candidate = __DIR__ . "/../Features/{$feature}/Views/{$name}.php";
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        foreach (self::BASE_PATHS as $base) {
            $candidate = $base . '/' . $relative;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        // Also look inside every feature's Views directory directly, e.g. "Auth/Views/login"
        $candidate = __DIR__ . '/../Features/' . $relative;
        if (is_file($candidate)) {
            return $candidate;
        }

        throw new \RuntimeException("View not found: {$view}");
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
