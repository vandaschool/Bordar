<?php

declare(strict_types=1);

namespace App\Features\Notification\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Features\Notification\Models\Notification;

final class NotificationController extends Controller
{
    public function index(): void
    {
        $notifications = Notification::forUser(Auth::id(), 50);
        Notification::markAllRead(Auth::id());

        $this->render('Notification::index', ['notifications' => $notifications]);
    }
}
