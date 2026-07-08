<?php

declare(strict_types=1);

namespace App\Features\SupportTicket\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Response;

/** Per the RBAC matrix, ticket triage is available to Admin and Mentor, not Reviewer/Applicant/Vendor. */
final class StaffTicketMiddleware extends Middleware
{
    public function handle(array $routeParams): bool
    {
        if (!Auth::check()) {
            Response::redirect('/login');
        }

        if (!Auth::hasRole('Admin', 'Mentor')) {
            http_response_code(403);
            Response::redirect('/dashboard');
        }

        return true;
    }
}
