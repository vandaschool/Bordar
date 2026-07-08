<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Lib\Sanitizer;
use PHPUnit\Framework\TestCase;

final class SanitizerTest extends TestCase
{
    public function test_html_escapes_script_tags(): void
    {
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', Sanitizer::html('<script>alert(1)</script>'));
    }

    public function test_email_is_lowercased_and_trimmed(): void
    {
        $this->assertSame('user@example.com', Sanitizer::email('  USER@Example.com  '));
    }

    public function test_iranian_mobile_validation(): void
    {
        $this->assertTrue(Sanitizer::isValidIranianMobile('09123456789'));
        $this->assertTrue(Sanitizer::isValidIranianMobile('+989123456789'));
        $this->assertFalse(Sanitizer::isValidIranianMobile('123456'));
    }

    public function test_sanitize_array_strips_control_characters_recursively(): void
    {
        $result = Sanitizer::sanitizeArray(['name' => "ali\x00", 'nested' => ['x' => "b\x01"]]);

        $this->assertSame('ali', $result['name']);
        $this->assertSame('b', $result['nested']['x']);
    }
}
