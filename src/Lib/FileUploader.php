<?php

declare(strict_types=1);

namespace App\Lib;

/**
 * Validates uploaded documents by their actual binary header (magic
 * numbers), not just the filename extension or client-supplied MIME type -
 * both of which are trivially spoofable.
 */
final class FileUploader
{
    public const MAX_SIZE_BYTES = 10 * 1024 * 1024; // 10MB

    /** @var array<string, array<int, string>> extension => list of acceptable raw byte-string signatures */
    private const SIGNATURES = [
        'pdf' => ["\x25\x50\x44\x46"],
        'jpg' => ["\xFF\xD8\xFF"],
        'jpeg' => ["\xFF\xD8\xFF"],
        'png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
        'docx' => ["\x50\x4B\x03\x04"],
        'xlsx' => ["\x50\x4B\x03\x04"],
    ];

    /** @return array<int, string> */
    public static function allowedExtensions(): array
    {
        return array_keys(self::SIGNATURES);
    }

    public static function detectExtension(string $originalName): string
    {
        return strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    }

    public static function isAllowedExtension(string $extension): bool
    {
        return isset(self::SIGNATURES[$extension]);
    }

    public static function magicNumberMatches(string $filePath, string $extension): bool
    {
        $signatures = self::SIGNATURES[$extension] ?? null;

        if ($signatures === null) {
            return false;
        }

        $maxLen = max(array_map('strlen', $signatures));
        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            return false;
        }

        $header = fread($handle, $maxLen);
        fclose($handle);

        if ($header === false) {
            return false;
        }

        foreach ($signatures as $signature) {
            if (str_starts_with($header, $signature)) {
                return true;
            }
        }

        return false;
    }
}
