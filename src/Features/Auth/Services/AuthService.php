<?php

declare(strict_types=1);

namespace App\Features\Auth\Services;

use App\Config\Constants;
use App\Core\AuditLog;
use App\Core\Model;
use App\Core\Session;
use App\Features\Auth\Models\OtpCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Lib\Mailer;

final class AuthService
{
    private const OTP_TTL_MINUTES = 10;

    private const OTP_MAX_ATTEMPTS = 5;

    /** @param array<string, mixed> $data */
    public function register(array $data): array
    {
        $role = Role::findByName(Constants::ROLE_APPLICANT);

        $userId = User::insert([
            'email' => mb_strtolower($data['email']),
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'role_id' => $role['id'],
            'is_active' => 1,
        ]);

        AuditLog::record('user.registered', 'User', $userId, null, ['email' => $data['email']], $userId);

        $this->issueEmailVerificationOtp($userId, $data['email'], $data['first_name']);

        return User::find($userId);
    }

    public function issueEmailVerificationOtp(string $userId, string $email, string $firstName): void
    {
        $this->issueOtp($userId, $email, $firstName, OtpCode::PURPOSE_EMAIL_VERIFY, 'تایید ایمیل ثبت‌نام');
    }

    public function issuePasswordResetOtp(string $userId, string $email, string $firstName): void
    {
        $this->issueOtp($userId, $email, $firstName, OtpCode::PURPOSE_PASSWORD_RESET, 'بازیابی رمز عبور');
    }

    private function issueOtp(string $userId, string $email, string $firstName, string $purpose, string $subject): void
    {
        OtpCode::invalidateActive($userId, $purpose);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::insert([
            'user_id' => $userId,
            'code_hash' => password_hash($code, PASSWORD_BCRYPT),
            'purpose' => $purpose,
            'expires_at' => gmdate('Y-m-d H:i:s', time() + self::OTP_TTL_MINUTES * 60),
        ]);

        $safeName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
        $body = "<div dir=\"rtl\" style=\"font-family:Tahoma\">"
            . "<p>{$safeName} عزیز،</p>"
            . "<p>کد تایید شما: <strong style=\"font-size:20px\">{$code}</strong></p>"
            . '<p>این کد تا ' . self::OTP_TTL_MINUTES . ' دقیقه دیگر معتبر است.</p>'
            . '</div>';

        Mailer::send($email, $subject, $body);

        if (($_ENV['APP_DEBUG'] ?? false)) {
            Session::flash('debug_otp', $code);
        }
    }

    public function verifyOtp(string $userId, string $purpose, string $code): bool
    {
        $otp = OtpCode::latestActive($userId, $purpose);

        if ($otp === null) {
            return false;
        }

        if ((int) $otp['attempts'] >= self::OTP_MAX_ATTEMPTS) {
            return false;
        }

        if (!password_verify($code, $otp['code_hash'])) {
            OtpCode::incrementAttempts($otp['id']);

            return false;
        }

        OtpCode::markConsumed($otp['id']);

        return true;
    }

    public function markEmailVerified(string $userId): void
    {
        User::update($userId, ['email_verified_at' => gmdate('Y-m-d H:i:s')]);
        AuditLog::record('user.email_verified', 'User', $userId);
    }

    public function resetPassword(string $userId, string $newPassword): void
    {
        User::update($userId, ['password_hash' => password_hash($newPassword, PASSWORD_BCRYPT)]);
        AuditLog::record('user.password_reset', 'User', $userId);
    }

    public function isAdmin(array $user): bool
    {
        $role = Role::find($user['role_id']);

        return $role !== null && $role['name'] === Constants::ROLE_ADMIN;
    }

    public function enableTwoFactor(string $userId, string $secret): void
    {
        User::update($userId, ['two_factor_secret' => $secret]);
        AuditLog::record('user.2fa_enabled', 'User', $userId);
    }

    public function logLoginAttempt(?string $userId, string $email, bool $success): void
    {
        AuditLog::record(
            $success ? 'auth.login_success' : 'auth.login_failed',
            'User',
            $userId,
            null,
            ['email' => $email],
            $userId
        );
    }
}
