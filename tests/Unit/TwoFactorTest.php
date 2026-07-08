<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Lib\TwoFactor;
use PHPUnit\Framework\TestCase;

final class TwoFactorTest extends TestCase
{
    public function test_generated_secret_produces_a_verifiable_code(): void
    {
        $secret = TwoFactor::generateSecret();
        $code = TwoFactor::currentCode($secret);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue(TwoFactor::verify($secret, $code));
    }

    public function test_wrong_code_fails_verification(): void
    {
        $secret = TwoFactor::generateSecret();
        $wrongCode = TwoFactor::currentCode($secret) === '000000' ? '111111' : '000000';

        $this->assertFalse(TwoFactor::verify($secret, $wrongCode));
    }

    public function test_malformed_code_is_rejected(): void
    {
        $secret = TwoFactor::generateSecret();

        $this->assertFalse(TwoFactor::verify($secret, 'abcdef'));
        $this->assertFalse(TwoFactor::verify($secret, '123'));
    }

    public function test_otpauth_uri_contains_issuer_and_secret(): void
    {
        $secret = TwoFactor::generateSecret();
        $uri = TwoFactor::otpAuthUri($secret, 'admin@bordar.local', 'Bordar');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString($secret, $uri);
        $this->assertStringContainsString('issuer=Bordar', $uri);
    }
}
