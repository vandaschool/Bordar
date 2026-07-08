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
use App\Features\Document\Controllers\DocumentController;
use App\Features\Notification\Controllers\NotificationController;
use App\Features\Onboarding\Controllers\OnboardingController;
use App\Features\Payment\Controllers\PaymentAdminController;
use App\Features\Payment\Controllers\PaymentController;
use App\Features\Review\Controllers\ReviewAdminController;
use App\Features\Review\Controllers\ReviewerController;
use App\Features\Review\Middleware\ReviewerMiddleware;
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
$router->get('/notifications', [NotificationController::class, 'index'], [AuthMiddleware::class]);

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
        $router->post('/applications/{id}/clarifications/{clarificationId}/respond', [ApplicationController::class, 'respondClarification']);
    });

    $router->group(['middleware' => [RequireCompanyMiddleware::class]], function (Router $router) {
        $router->get('/documents', [DocumentController::class, 'index']);
        $router->post('/documents', [DocumentController::class, 'upload']);
        $router->post('/documents/{id}/delete', [DocumentController::class, 'delete']);
    });

    $router->get('/documents/{id}/download', [DocumentController::class, 'download']);

    $router->get('/payment', [PaymentController::class, 'index']);
    $router->post('/payment/{installmentNumber}/zarinpal', [PaymentController::class, 'payViaZarinpal']);
    $router->post('/payment/{installmentNumber}/manual', [PaymentController::class, 'submitManualTransfer']);
    $router->get('/payment/callback', [PaymentController::class, 'zarinpalCallback']);

    $router->get('/onboarding', [OnboardingController::class, 'index']);
    $router->post('/onboarding/checklist', [OnboardingController::class, 'toggleItem']);
    $router->post('/onboarding/tour/complete', [OnboardingController::class, 'completeTour']);
});

$router->group(['prefix' => '/admin', 'middleware' => [AdminMiddleware::class]], function (Router $router) {
    $router->get('/cohorts', [CohortController::class, 'index']);
    $router->get('/cohorts/create', [CohortController::class, 'showCreate']);
    $router->post('/cohorts', [CohortController::class, 'store']);
    $router->get('/cohorts/{id}/edit', [CohortController::class, 'showEdit']);
    $router->put('/cohorts/{id}', [CohortController::class, 'update']);

    $router->get('/reviews', [ReviewAdminController::class, 'index']);
    $router->get('/reviews/{id}', [ReviewAdminController::class, 'show']);
    $router->post('/reviews/{id}/assign', [ReviewAdminController::class, 'assign']);
    $router->post('/reviews/{id}/unassign/{reviewerUserId}', [ReviewAdminController::class, 'unassign']);
    $router->post('/reviews/{id}/override', [ReviewAdminController::class, 'override']);

    $router->get('/payments', [PaymentAdminController::class, 'index']);
    $router->get('/payments/{id}/receipt', [PaymentAdminController::class, 'receipt']);
    $router->post('/payments/{id}/approve', [PaymentAdminController::class, 'approve']);
    $router->post('/payments/{id}/reject', [PaymentAdminController::class, 'reject']);
});

$router->group(['prefix' => '/reviewer', 'middleware' => [ReviewerMiddleware::class]], function (Router $router) {
    $router->get('/queue', [ReviewerController::class, 'queue']);
    $router->get('/applications/{id}', [ReviewerController::class, 'show']);
    $router->post('/applications/{id}/draft', [ReviewerController::class, 'saveDraft']);
    $router->post('/applications/{id}/submit', [ReviewerController::class, 'submit']);
    $router->post('/applications/{id}/conflict', [ReviewerController::class, 'toggleConflict']);
    $router->post('/applications/{id}/clarify', [ReviewerController::class, 'requestClarification']);
});

return $router;
