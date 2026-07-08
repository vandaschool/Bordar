<?php

declare(strict_types=1);

namespace App\Features\Company\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Response;
use App\Core\Session;
use App\Core\Tenant;

/** Admins bypass this check (they act on behalf of a specific company_id per request instead). */
final class RequireCompanyMiddleware extends Middleware
{
    public function handle(array $routeParams): bool
    {
        if (Auth::hasRole('Admin')) {
            return true;
        }

        if (!Tenant::hasCompany()) {
            Session::flash('error', 'ابتدا باید پروفایل شرکت خود را تکمیل کنید.');
            Response::redirect('/company/create');
        }

        return true;
    }
}
