<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\Constants;
use App\Config\Database;
use App\Core\Model;
use App\Core\Session;
use App\Features\Auth\Models\OtpCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Auth\Services\AuthService;
use PDO;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class AuthServiceTest extends TestCase
{
    private static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = Database::connection();

        foreach ([Constants::ROLE_ADMIN, Constants::ROLE_APPLICANT] as $roleName) {
            if (Role::findByName($roleName) === null) {
                Role::insert([
                    'id' => Model::uuid(),
                    'name' => $roleName,
                    'description' => $roleName,
                    'permissions' => json_encode($roleName === Constants::ROLE_ADMIN ? ['*'] : []),
                ]);
            }
        }

        Session::start();
    }

    protected function setUp(): void
    {
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        self::$pdo->exec('TRUNCATE TABLE otp_codes');
        self::$pdo->exec('TRUNCATE TABLE audit_logs');
        self::$pdo->exec('TRUNCATE TABLE users');
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    public function test_register_creates_user_with_applicant_role_and_hashed_password(): void
    {
        $service = new AuthService();

        $user = $service->register([
            'email' => 'newuser@example.com',
            'password' => 'Password123',
            'first_name' => 'Ali',
            'last_name' => 'Rezaei',
        ]);

        $this->assertNotNull($user);
        $this->assertNull($user['email_verified_at']);
        $this->assertTrue(password_verify('Password123', $user['password_hash']));

        $role = Role::find($user['role_id']);
        $this->assertSame(Constants::ROLE_APPLICANT, $role['name']);
    }

    public function test_register_issues_a_verifiable_otp(): void
    {
        $service = new AuthService();
        $user = $service->register([
            'email' => 'otpuser@example.com',
            'password' => 'Password123',
            'first_name' => 'Sara',
            'last_name' => 'Ahmadi',
        ]);

        $code = Session::flash('debug_otp');
        $this->assertNotNull($code, 'debug OTP should be flashed when APP_DEBUG is enabled');

        $this->assertTrue($service->verifyOtp($user['id'], OtpCode::PURPOSE_EMAIL_VERIFY, $code));
    }

    public function test_wrong_otp_code_is_rejected_and_does_not_consume_the_real_code(): void
    {
        $service = new AuthService();
        $user = $service->register([
            'email' => 'wrongotp@example.com',
            'password' => 'Password123',
            'first_name' => 'Reza',
            'last_name' => 'Karimi',
        ]);

        $realCode = Session::flash('debug_otp');

        $this->assertFalse($service->verifyOtp($user['id'], OtpCode::PURPOSE_EMAIL_VERIFY, '000000'));
        $this->assertTrue($service->verifyOtp($user['id'], OtpCode::PURPOSE_EMAIL_VERIFY, $realCode));
    }

    public function test_otp_cannot_be_reused_after_verification(): void
    {
        $service = new AuthService();
        $user = $service->register([
            'email' => 'reuseotp@example.com',
            'password' => 'Password123',
            'first_name' => 'Nima',
            'last_name' => 'Sadeghi',
        ]);

        $code = Session::flash('debug_otp');

        $this->assertTrue($service->verifyOtp($user['id'], OtpCode::PURPOSE_EMAIL_VERIFY, $code));
        $this->assertFalse($service->verifyOtp($user['id'], OtpCode::PURPOSE_EMAIL_VERIFY, $code));
    }

    public function test_mark_email_verified_sets_timestamp(): void
    {
        $service = new AuthService();
        $user = $service->register([
            'email' => 'verifyme@example.com',
            'password' => 'Password123',
            'first_name' => 'Leila',
            'last_name' => 'Hosseini',
        ]);

        $service->markEmailVerified($user['id']);

        $refreshed = User::find($user['id']);
        $this->assertNotNull($refreshed['email_verified_at']);
    }

    public function test_reset_password_updates_hash(): void
    {
        $service = new AuthService();
        $user = $service->register([
            'email' => 'resetme@example.com',
            'password' => 'OldPassword123',
            'first_name' => 'Omid',
            'last_name' => 'Yousefi',
        ]);

        $service->resetPassword($user['id'], 'NewPassword456');

        $refreshed = User::find($user['id']);
        $this->assertTrue(password_verify('NewPassword456', $refreshed['password_hash']));
        $this->assertFalse(password_verify('OldPassword123', $refreshed['password_hash']));
    }

    public function test_duplicate_email_registration_is_detectable_via_model(): void
    {
        $service = new AuthService();
        $service->register([
            'email' => 'dupe@example.com',
            'password' => 'Password123',
            'first_name' => 'A',
            'last_name' => 'B',
        ]);

        $this->assertTrue(User::emailExists('dupe@example.com'));
        $this->assertTrue(User::emailExists('DUPE@example.com'));
    }

    public function test_soft_deleted_user_is_excluded_from_lookups(): void
    {
        $service = new AuthService();
        $user = $service->register([
            'email' => 'softdelete@example.com',
            'password' => 'Password123',
            'first_name' => 'C',
            'last_name' => 'D',
        ]);

        User::softDelete($user['id']);

        $this->assertNull(User::find($user['id']));
        $this->assertFalse(User::emailExists('softdelete@example.com'));
    }
}
