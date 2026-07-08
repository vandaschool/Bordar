<?php

declare(strict_types=1);

use App\Core\Router;
use App\Core\View;
use App\Features\Auth\Controllers\AuthController;
use App\Features\Auth\Controllers\TwoFactorController;
use App\Features\Auth\Middleware\AuthMiddleware;
use App\Features\Auth\Middleware\GuestMiddleware;
use App\Features\User\Controllers\DashboardController;

$router = new Router();

$router->get('/', function () {
    $view = new View();
    echo $view->render('home', ['auth' => \App\Core\Auth::user()]);
});

$router->get('/register', [AuthController::class, 'showRegister'], [GuestMiddleware::class]);
$router->post('/register', [AuthController::class, 'register'], [GuestMiddleware::class]);

$router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
$router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class]);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

$router->get('/verify-email', [AuthController::class, 'showVerifyEmail']);
$router->post('/verify-email', [AuthController::class, 'verifyEmail']);
$router->post('/verify-email/resend', [AuthController::class, 'resendVerificationOtp']);

$router->get('/forgot-password', [AuthController::class, 'showForgotPassword'], [GuestMiddleware::class]);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword'], [GuestMiddleware::class]);
$router->get('/reset-password', [AuthController::class, 'showResetPassword'], [GuestMiddleware::class]);
$router->post('/reset-password', [AuthController::class, 'resetPassword'], [GuestMiddleware::class]);

$router->get('/2fa/setup', [TwoFactorController::class, 'showSetup']);
$router->post('/2fa/setup', [TwoFactorController::class, 'confirmSetup']);
$router->get('/2fa/verify', [TwoFactorController::class, 'showVerify']);
$router->post('/2fa/verify', [TwoFactorController::class, 'verify']);

$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

return $router;
