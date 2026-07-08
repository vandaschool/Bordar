<?php

declare(strict_types=1);

namespace App\Features\User\Controllers;

use App\Core\Auth;
use App\Core\Controller;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->render('User::dashboard', ['role' => Auth::role()]);
    }
}
