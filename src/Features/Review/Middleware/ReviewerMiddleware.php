<?php

declare(strict_types=1);

namespace App\Features\Review\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Response;

final class ReviewerMiddleware extends Middleware
{
    public function handle(array $routeParams): bool
    {
        if (!Auth::check()) {
            Response::redirect('/login');
        }

        if (!Auth::hasRole('Reviewer', 'Admin')) {
            http_response_code(403);
            Response::redirect('/dashboard');
        }

        return true;
    }
}
