<?php

declare(strict_types=1);

namespace App\Features\Mentor\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Response;

final class MentorMiddleware extends Middleware
{
    public function handle(array $routeParams): bool
    {
        if (!Auth::check()) {
            Response::redirect('/login');
        }

        if (!Auth::hasRole('Mentor', 'Admin')) {
            http_response_code(403);
            Response::redirect('/dashboard');
        }

        return true;
    }
}
