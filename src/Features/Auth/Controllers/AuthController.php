<?php

declare(strict_types=1);

namespace App\Features\Auth\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Features\Auth\Models\OtpCode;
use App\Features\Auth\Models\User;
use App\Features\Auth\Services\AuthService;
use App\Lib\Validator;

final class AuthController extends Controller
{
    private const LOGIN_THROTTLE_MAX_ATTEMPTS = 10;

    private const LOGIN_THROTTLE_WINDOW_MINUTES = 15;

    private AuthService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new AuthService();
    }

    public function showRegister(): void
    {
        $this->render('Auth::register', ['errors' => [], 'old' => []]);
    }

    public function register(): void
    {
        $this->requireCsrf();

        $data = Request::post();

        $validator = Validator::make($data, [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|strong_password',
            'password_confirmation' => 'required|same:password',
        ]);

        if (User::emailExists($data['email'] ?? '')) {
            $validator = Validator::make($data, ['email' => 'required']);
            $errors = $validator->errors();
            $errors['email'][] = 'این ایمیل قبلاً ثبت شده است.';
            $this->render('Auth::register', ['errors' => $errors, 'old' => $data]);

            return;
        }

        if ($validator->fails()) {
            $this->render('Auth::register', ['errors' => $validator->errors(), 'old' => $data]);

            return;
        }

        $user = $this->service->register($data);

        Session::set('pending_verify_user_id', $user['id']);

        $this->redirect('/verify-email');
    }

    public function showVerifyEmail(): void
    {
        $userId = Session::get('pending_verify_user_id');

        if ($userId === null) {
            $this->redirect('/login');

            return;
        }

        $user = User::find($userId);
        $this->render('Auth::verify-email', ['email' => $user['email'] ?? '', 'errors' => []]);
    }

    public function verifyEmail(): void
    {
        $this->requireCsrf();

        $userId = Session::get('pending_verify_user_id');
        $code = (string) Request::input('code', '');

        if ($userId === null) {
            $this->redirect('/login');

            return;
        }

        if (!$this->service->verifyOtp($userId, OtpCode::PURPOSE_EMAIL_VERIFY, $code)) {
            $user = User::find($userId);
            $this->render('Auth::verify-email', ['email' => $user['email'] ?? '', 'errors' => ['کد وارد شده نامعتبر یا منقضی‌شده است.']]);

            return;
        }

        $this->service->markEmailVerified($userId);
        Session::remove('pending_verify_user_id');

        $user = User::find($userId);
        $this->startSessionFor($user);
    }

    public function resendVerificationOtp(): void
    {
        $this->requireCsrf();

        $userId = Session::get('pending_verify_user_id');

        if ($userId === null) {
            $this->redirect('/login');

            return;
        }

        $user = User::find($userId);

        if ($user !== null) {
            $this->service->issueEmailVerificationOtp($user['id'], $user['email'], $user['first_name']);
        }

        Session::flash('status', 'کد جدید ارسال شد.');
        $this->redirect('/verify-email');
    }

    public function showLogin(): void
    {
        $this->render('Auth::login', ['errors' => []]);
    }

    public function login(): void
    {
        $this->requireCsrf();

        $email = (string) Request::input('email', '');
        $password = (string) Request::input('password', '');

        $validator = Validator::make(['email' => $email, 'password' => $password], [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $this->render('Auth::login', ['errors' => $validator->errors()]);

            return;
        }

        $recentFailures = AuditLog::countRecentByAction('auth.login_failed', Request::ip(), self::LOGIN_THROTTLE_WINDOW_MINUTES);

        if ($recentFailures >= self::LOGIN_THROTTLE_MAX_ATTEMPTS) {
            $this->render('Auth::login', ['errors' => [
                'تعداد تلاش‌های ورود ناموفق بیش از حد مجاز است. لطفاً ' . self::LOGIN_THROTTLE_WINDOW_MINUTES . ' دقیقه دیگر تلاش کنید.',
            ]]);

            return;
        }

        $user = User::findByEmail($email);
        $ok = $user !== null && (int) $user['is_active'] === 1 && password_verify($password, $user['password_hash']);

        $this->service->logLoginAttempt($user['id'] ?? null, $email, $ok);

        if (!$ok) {
            $this->render('Auth::login', ['errors' => ['ایمیل یا رمز عبور اشتباه است.']]);

            return;
        }

        if ($user['email_verified_at'] === null) {
            Session::set('pending_verify_user_id', $user['id']);
            $this->service->issueEmailVerificationOtp($user['id'], $user['email'], $user['first_name']);
            $this->redirect('/verify-email');

            return;
        }

        $this->startSessionFor($user);
    }

    /** @param array<string, mixed> $user */
    private function startSessionFor(array $user): void
    {
        if ($this->service->isAdmin($user)) {
            Session::set('2fa_user_id', $user['id']);
            Session::remove('2fa_passed');

            if (empty($user['two_factor_secret'])) {
                $this->redirect('/2fa/setup');

                return;
            }

            $this->redirect('/2fa/verify');

            return;
        }

        Auth::login($user['id']);
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        $this->requireCsrf();
        Auth::logout();
        $this->redirect('/login');
    }

    public function showForgotPassword(): void
    {
        $this->render('Auth::forgot-password', ['errors' => [], 'status' => Session::flash('status')]);
    }

    public function forgotPassword(): void
    {
        $this->requireCsrf();

        $email = (string) Request::input('email', '');
        $user = User::findByEmail($email);

        if ($user !== null) {
            $this->service->issuePasswordResetOtp($user['id'], $user['email'], $user['first_name']);
            Session::set('pending_reset_user_id', $user['id']);
        }

        // Always behave the same way to avoid leaking which emails are registered.
        $this->redirect('/reset-password');
    }

    public function showResetPassword(): void
    {
        if (Session::get('pending_reset_user_id') === null) {
            $this->redirect('/forgot-password');

            return;
        }

        $this->render('Auth::reset-password', ['errors' => []]);
    }

    public function resetPassword(): void
    {
        $this->requireCsrf();

        $userId = Session::get('pending_reset_user_id');

        if ($userId === null) {
            $this->redirect('/forgot-password');

            return;
        }

        $code = (string) Request::input('code', '');
        $password = (string) Request::input('password', '');
        $confirmation = (string) Request::input('password_confirmation', '');

        $validator = Validator::make(
            ['password' => $password, 'password_confirmation' => $confirmation],
            ['password' => 'required|strong_password', 'password_confirmation' => 'required|same:password']
        );

        if ($validator->fails()) {
            $this->render('Auth::reset-password', ['errors' => $validator->errors()]);

            return;
        }

        if (!$this->service->verifyOtp($userId, OtpCode::PURPOSE_PASSWORD_RESET, $code)) {
            $this->render('Auth::reset-password', ['errors' => ['کد وارد شده نامعتبر یا منقضی‌شده است.']]);

            return;
        }

        $this->service->resetPassword($userId, $password);
        Session::remove('pending_reset_user_id');
        Session::flash('status', 'رمز عبور با موفقیت تغییر کرد. اکنون وارد شوید.');

        $this->redirect('/login');
    }
}
