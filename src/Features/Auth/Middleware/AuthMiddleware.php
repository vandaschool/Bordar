<?php

declare(strict_types=1);

namespace App\Features\Auth\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware extends Middleware
{
    public function handle(array $routeParams): bool
    {
        if (!Auth::check()) {
            Session::flash('error', 'برای ادامه ابتدا وارد شوید.');
            Response::redirect('/login');
        }

        return true;
    }
}
