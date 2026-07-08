<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Lib\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function test_required_field_missing_fails(): void
    {
        $v = Validator::make([], ['email' => 'required|email']);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('email', $v->errors());
    }

    public function test_valid_email_passes(): void
    {
        $v = Validator::make(['email' => 'user@example.com'], ['email' => 'required|email']);

        $this->assertTrue($v->passes());
    }

    public function test_invalid_email_fails(): void
    {
        $v = Validator::make(['email' => 'not-an-email'], ['email' => 'required|email']);

        $this->assertTrue($v->fails());
    }

    public function test_min_length_rule(): void
    {
        $v = Validator::make(['password' => 'short'], ['password' => 'min:8']);

        $this->assertTrue($v->fails());
    }

    public function test_confirmed_rule_requires_matching_field(): void
    {
        $v = Validator::make(
            ['password' => 'Password123', 'password_confirmation' => 'Different123'],
            ['password' => 'confirmed']
        );

        $this->assertTrue($v->fails());
    }

    public function test_strong_password_rule_rejects_weak_passwords(): void
    {
        $v = Validator::make(['password' => 'alllowercase'], ['password' => 'strong_password']);
        $this->assertTrue($v->fails());

        $v2 = Validator::make(['password' => 'Password123'], ['password' => 'strong_password']);
        $this->assertTrue($v2->passes());
    }
}
