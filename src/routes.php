<?php

declare(strict_types=1);

use App\Core\Router;
use App\Core\View;
use App\Features\Auth\Controllers\AuthController;
use App\Features\Auth\Controllers\TwoFactorController;
use App\Features\Auth\Middleware\AdminMiddleware;
use App\Features\Auth\Middleware\AuthMiddleware;
use App\Features\Auth\Middleware\GuestMiddleware;
use App\Features\Application\Controllers\ApplicationController;
use App\Features\Cohort\Controllers\CohortController;
use App\Features\Company\Controllers\CompanyController;
use App\Features\Company\Middleware\RequireCompanyMiddleware;
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

$router->group(['middleware' => [AuthMiddleware::class]], function (Router $router) {
    $router->get('/company/create', [CompanyController::class, 'showCreate']);
    $router->post('/company', [CompanyController::class, 'store']);
    $router->get('/company', [CompanyController::class, 'show']);
    $router->get('/company/edit', [CompanyController::class, 'showEdit']);
    $router->put('/company', [CompanyController::class, 'update']);

    $router->group(['middleware' => [RequireCompanyMiddleware::class]], function (Router $router) {
        $router->get('/applications', [ApplicationController::class, 'index']);
        $router->get('/applications/start', [ApplicationController::class, 'showStart']);
        $router->post('/applications/start', [ApplicationController::class, 'start']);
        $router->get('/applications/{id}', [ApplicationController::class, 'show']);
        $router->post('/applications/{id}/autosave', [ApplicationController::class, 'autosave']);
        $router->post('/applications/{id}/submit', [ApplicationController::class, 'submit']);
    });
});

$router->group(['prefix' => '/admin', 'middleware' => [AdminMiddleware::class]], function (Router $router) {
    $router->get('/cohorts', [CohortController::class, 'index']);
    $router->get('/cohorts/create', [CohortController::class, 'showCreate']);
    $router->post('/cohorts', [CohortController::class, 'store']);
    $router->get('/cohorts/{id}/edit', [CohortController::class, 'showEdit']);
    $router->put('/cohorts/{id}', [CohortController::class, 'update']);
});

return $router;
