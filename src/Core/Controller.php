<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    protected function render(string $view, array $data = [], ?string $layout = 'layout'): string
    {
        echo $this->view->render($view, array_merge($data, [
            'auth' => Auth::user(),
            'csrf' => Session::csrfToken(),
        ]), $layout);

        return '';
    }

    protected function json(array $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function redirect(string $to): never
    {
        Response::redirect($to);
    }

    protected function requireCsrf(): void
    {
        if (!Session::verifyCsrfToken(Request::csrfToken())) {
            Response::error('نشست شما نامعتبر است. صفحه را رفرش کرده و دوباره تلاش کنید.', 419);
        }
    }
}
