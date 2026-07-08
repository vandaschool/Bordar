<?php

declare(strict_types=1);

namespace App\Features\Auth\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Response;

final class GuestMiddleware extends Middleware
{
    public function handle(array $routeParams): bool
    {
        if (Auth::check()) {
            Response::redirect('/dashboard');
        }

        return true;
    }
}
