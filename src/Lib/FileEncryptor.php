<?php

declare(strict_types=1);

namespace App\Lib;

use App\Config\App;

/** AES-256-CBC at-rest encryption for uploaded documents (Document Vault). */
final class FileEncryptor
{
    private const CIPHER = 'aes-256-cbc';

    private static function key(): string
    {
        // APP_KEY may be any length/format; derive a fixed 32-byte cipher key from it.
        return hash('sha256', App::key(), true);
    }

    /** @return array{ciphertext: string, iv: string} iv is base64-encoded. */
    public static function encrypt(string $plaintext): array
    {
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new \RuntimeException('رمزنگاری فایل با خطا مواجه شد.');
        }

        return ['ciphertext' => $ciphertext, 'iv' => base64_encode($iv)];
    }

    public static function decrypt(string $ciphertext, string $ivBase64): string
    {
        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, base64_decode($ivBase64));

        if ($plaintext === false) {
            throw new \RuntimeException('رمزگشایی فایل با خطا مواجه شد.');
        }

        return $plaintext;
    }
}
