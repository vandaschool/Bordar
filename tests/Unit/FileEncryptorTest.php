<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Lib\FileEncryptor;
use PHPUnit\Framework\TestCase;

final class FileEncryptorTest extends TestCase
{
    public function test_encrypt_then_decrypt_round_trips_to_original_plaintext(): void
    {
        $plaintext = random_bytes(2048);

        $encrypted = FileEncryptor::encrypt($plaintext);
        $decrypted = FileEncryptor::decrypt($encrypted['ciphertext'], $encrypted['iv']);

        $this->assertSame($plaintext, $decrypted);
    }

    public function test_ciphertext_does_not_equal_plaintext(): void
    {
        $plaintext = 'sensitive business document contents';

        $encrypted = FileEncryptor::encrypt($plaintext);

        $this->assertNotSame($plaintext, $encrypted['ciphertext']);
    }

    public function test_each_encryption_uses_a_fresh_iv(): void
    {
        $a = FileEncryptor::encrypt('same content');
        $b = FileEncryptor::encrypt('same content');

        $this->assertNotSame($a['iv'], $b['iv']);
        $this->assertNotSame($a['ciphertext'], $b['ciphertext']);
    }

    public function test_wrong_iv_fails_to_reproduce_original_plaintext(): void
    {
        $original = 'some content that is long enough';
        $encrypted = FileEncryptor::encrypt($original);
        $otherIv = base64_encode(random_bytes(16));

        try {
            $result = FileEncryptor::decrypt($encrypted['ciphertext'], $otherIv);
            $this->assertNotSame($original, $result);
        } catch (\RuntimeException $e) {
            // A corrupted PKCS7 padding is also an acceptable failure mode.
            $this->assertStringContainsString('رمزگشایی', $e->getMessage());
        }
    }
}
