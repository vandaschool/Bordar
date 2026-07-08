<?php

declare(strict_types=1);

namespace App\Features\Auth\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Response;
use App\Core\Session;

/** Requires an authenticated Admin whose 2FA challenge has been passed in this session. */
final class AdminMiddleware extends Middleware
{
    public function handle(array $routeParams): bool
    {
        if (!Auth::check()) {
            Response::redirect('/login');
        }

        if (!Auth::hasRole('Admin')) {
            http_response_code(403);
            Session::flash('error', 'شما به این بخش دسترسی ندارید.');
            Response::redirect('/dashboard');
        }

        if (!Session::get('2fa_passed', false)) {
            Response::redirect('/2fa/verify');
        }

        return true;
    }
}
