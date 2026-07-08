<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Lib\FileUploader;
use PHPUnit\Framework\TestCase;

final class FileUploaderTest extends TestCase
{
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }
        $this->tempFiles = [];
    }

    private function writeTemp(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'bordar_test_');
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }

    public function test_detects_extension_case_insensitively(): void
    {
        $this->assertSame('pdf', FileUploader::detectExtension('Contract.PDF'));
        $this->assertSame('png', FileUploader::detectExtension('logo.png'));
    }

    public function test_allowed_extensions_are_recognized(): void
    {
        $this->assertTrue(FileUploader::isAllowedExtension('pdf'));
        $this->assertTrue(FileUploader::isAllowedExtension('docx'));
        $this->assertFalse(FileUploader::isAllowedExtension('exe'));
        $this->assertFalse(FileUploader::isAllowedExtension('php'));
    }

    public function test_valid_pdf_header_matches(): void
    {
        $path = $this->writeTemp("%PDF-1.4\n%rest of a fake pdf body");

        $this->assertTrue(FileUploader::magicNumberMatches($path, 'pdf'));
    }

    public function test_valid_png_header_matches(): void
    {
        $path = $this->writeTemp("\x89\x50\x4E\x47\x0D\x0A\x1A\x0A" . 'rest of fake png bytes');

        $this->assertTrue(FileUploader::magicNumberMatches($path, 'png'));
    }

    public function test_php_file_renamed_to_pdf_is_rejected_by_magic_number(): void
    {
        // A classic upload-bypass attempt: malicious PHP payload with a .pdf extension.
        $path = $this->writeTemp("<?php system(\$_GET['c']); ?>");

        $this->assertFalse(FileUploader::magicNumberMatches($path, 'pdf'));
    }

    public function test_mismatched_extension_is_rejected(): void
    {
        $path = $this->writeTemp("%PDF-1.4\nfake pdf content");

        $this->assertFalse(FileUploader::magicNumberMatches($path, 'png'));
    }
}
