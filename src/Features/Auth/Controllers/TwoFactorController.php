<?php

declare(strict_types=1);

namespace App\Features\Auth\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Features\Auth\Models\User;
use App\Features\Auth\Services\AuthService;
use App\Lib\TwoFactor;

final class TwoFactorController extends Controller
{
    private AuthService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new AuthService();
    }

    public function showSetup(): void
    {
        $userId = Session::get('2fa_user_id');

        if ($userId === null) {
            $this->redirect('/login');

            return;
        }

        $user = User::find($userId);

        if ($user === null) {
            $this->redirect('/login');

            return;
        }

        if (!empty($user['two_factor_secret'])) {
            $this->redirect('/2fa/verify');

            return;
        }

        $secret = Session::get('2fa_setup_secret');

        if ($secret === null) {
            $secret = TwoFactor::generateSecret();
            Session::set('2fa_setup_secret', $secret);
        }

        $uri = TwoFactor::otpAuthUri($secret, $user['email']);

        $this->render('Auth::2fa-setup', ['secret' => $secret, 'uri' => $uri, 'errors' => []]);
    }

    public function confirmSetup(): void
    {
        $this->requireCsrf();

        $userId = Session::get('2fa_user_id');
        $secret = Session::get('2fa_setup_secret');
        $code = (string) Request::input('code', '');

        if ($userId === null || $secret === null) {
            $this->redirect('/login');

            return;
        }

        if (!TwoFactor::verify($secret, $code)) {
            $user = User::find($userId);
            $uri = TwoFactor::otpAuthUri($secret, $user['email'] ?? '');
            $this->render('Auth::2fa-setup', ['secret' => $secret, 'uri' => $uri, 'errors' => ['کد وارد شده نادرست است.']]);

            return;
        }

        $this->service->enableTwoFactor($userId, $secret);
        Session::remove('2fa_setup_secret');
        Session::set('2fa_passed', true);

        Auth::login($userId);
        $this->redirect('/dashboard');
    }

    public function showVerify(): void
    {
        $userId = Session::get('2fa_user_id');

        if ($userId === null) {
            $this->redirect('/login');

            return;
        }

        $this->render('Auth::2fa-verify', ['errors' => []]);
    }

    public function verify(): void
    {
        $this->requireCsrf();

        $userId = Session::get('2fa_user_id');
        $code = (string) Request::input('code', '');

        if ($userId === null) {
            $this->redirect('/login');

            return;
        }

        $user = User::find($userId);

        if ($user === null || empty($user['two_factor_secret']) || !TwoFactor::verify($user['two_factor_secret'], $code)) {
            $this->render('Auth::2fa-verify', ['errors' => ['کد وارد شده نادرست است.']]);

            return;
        }

        Session::set('2fa_passed', true);
        Auth::login($userId);
        $this->redirect('/dashboard');
    }
}
