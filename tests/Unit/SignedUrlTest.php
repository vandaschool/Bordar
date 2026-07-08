<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Lib\SignedUrl;
use PHPUnit\Framework\TestCase;

final class SignedUrlTest extends TestCase
{
    public function test_freshly_signed_url_verifies(): void
    {
        $signed = SignedUrl::sign('doc-123', 300);

        $this->assertTrue(SignedUrl::verify('doc-123', $signed['expires'], $signed['signature']));
    }

    public function test_expired_signature_fails(): void
    {
        $signed = SignedUrl::sign('doc-123', -10);

        $this->assertFalse(SignedUrl::verify('doc-123', $signed['expires'], $signed['signature']));
    }

    public function test_tampered_resource_id_fails(): void
    {
        $signed = SignedUrl::sign('doc-123', 300);

        $this->assertFalse(SignedUrl::verify('doc-999', $signed['expires'], $signed['signature']));
    }

    public function test_tampered_signature_fails(): void
    {
        $signed = SignedUrl::sign('doc-123', 300);

        $this->assertFalse(SignedUrl::verify('doc-123', $signed['expires'], $signed['signature'] . 'x'));
    }

    public function test_tampered_expiry_fails(): void
    {
        $signed = SignedUrl::sign('doc-123', 300);

        $this->assertFalse(SignedUrl::verify('doc-123', $signed['expires'] + 100000, $signed['signature']));
    }
}
