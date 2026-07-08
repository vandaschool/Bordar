<?php

declare(strict_types=1);

namespace App\Features\Document\Services;

use App\Core\AuditLog;
use App\Features\Application\Models\Application;
use App\Features\Company\Models\Company;
use App\Features\Document\Models\Document;
use App\Features\Review\Models\ApplicationReviewer;
use App\Lib\FileEncryptor;
use App\Lib\FileUploader;
use App\Lib\SignedUrl;

final class DocumentService
{
    public const TYPES = [
        'BUSINESS_LICENSE' => 'جواز کسب / پروانه فعالیت',
        'REGISTRATION_CERTIFICATE' => 'آگهی ثبت شرکت',
        'FINANCIAL_STATEMENT' => 'صورت مالی',
        'EXPORT_LICENSE' => 'کارت بازرگانی / مجوز صادرات',
        'OTHER' => 'سایر',
    ];

    /**
     * @param array{name: string, tmp_name: string, size: int, error: int} $file $_FILES-style entry
     */
    public function upload(
        string $companyId,
        string $uploadedByUserId,
        array $file,
        string $documentType,
        ?string $expiresAt,
        ?string $replacesDocumentId
    ): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('بارگذاری فایل با خطا مواجه شد.');
        }

        if ($file['size'] > FileUploader::MAX_SIZE_BYTES) {
            throw new \RuntimeException('حجم فایل نباید بیشتر از ۱۰ مگابایت باشد.');
        }

        if (!is_uploaded_file($file['tmp_name']) && php_sapi_name() !== 'cli') {
            throw new \RuntimeException('فایل نامعتبر است.');
        }

        return $this->storeFromPath($companyId, $uploadedByUserId, $file['tmp_name'], $file['name'], (int) $file['size'], $documentType, $expiresAt, $replacesDocumentId);
    }

    /**
     * Core storage logic shared by upload() (real HTTP uploads) and tests /
     * future CLI import tools, which supply an already-materialized file
     * path instead of a $_FILES entry.
     */
    public function storeFromPath(
        string $companyId,
        string $uploadedByUserId,
        string $tmpPath,
        string $originalName,
        int $size,
        string $documentType,
        ?string $expiresAt,
        ?string $replacesDocumentId
    ): array {
        $extension = FileUploader::detectExtension($originalName);

        if (!FileUploader::isAllowedExtension($extension)) {
            throw new \InvalidArgumentException('نوع فایل مجاز نیست. فرمت‌های مجاز: ' . implode('، ', FileUploader::allowedExtensions()));
        }

        if (!FileUploader::magicNumberMatches($tmpPath, $extension)) {
            throw new \InvalidArgumentException('محتوای فایل با پسوند اعلام‌شده مطابقت ندارد.');
        }

        $plaintext = file_get_contents($tmpPath);
        $checksum = hash('sha256', $plaintext);
        $encrypted = FileEncryptor::encrypt($plaintext);

        $storageDir = rtrim($_ENV['STORAGE_PATH'] ?? sys_get_temp_dir(), '/') . "/documents/{$companyId}";
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0700, true);
        }

        $storedName = bin2hex(random_bytes(16)) . '.enc';
        $fullPath = $storageDir . '/' . $storedName;
        file_put_contents($fullPath, $encrypted['ciphertext']);

        $version = 1;
        if ($replacesDocumentId !== null) {
            $previous = Document::find($replacesDocumentId);
            $version = $previous !== null ? (int) $previous['version'] + 1 : 1;
        }

        $id = Document::insert([
            'company_id' => $companyId,
            'uploaded_by_user_id' => $uploadedByUserId,
            'document_type' => $documentType,
            'file_name' => basename($originalName),
            'file_type' => $extension,
            'file_size' => $size,
            'file_path' => "documents/{$companyId}/{$storedName}",
            'is_encrypted' => 1,
            'encryption_iv' => $encrypted['iv'],
            'checksum' => $checksum,
            'status' => 'PENDING',
            'expires_at' => $expiresAt,
            'version' => $version,
            'replaces_document_id' => $replacesDocumentId,
        ]);

        AuditLog::record('document.uploaded', 'Document', $id, null, ['file_name' => $originalName, 'version' => $version]);

        return Document::find($id);
    }

    /** @return array{content: string, file_name: string, file_type: string} */
    public function decryptForDownload(array $document): array
    {
        $storagePath = rtrim($_ENV['STORAGE_PATH'] ?? sys_get_temp_dir(), '/') . '/' . $document['file_path'];
        $ciphertext = file_get_contents($storagePath);

        if ($ciphertext === false) {
            throw new \RuntimeException('فایل روی سرور یافت نشد.');
        }

        $plaintext = FileEncryptor::decrypt($ciphertext, $document['encryption_iv']);

        if (hash('sha256', $plaintext) !== $document['checksum']) {
            throw new \RuntimeException('یکپارچگی فایل تایید نشد.');
        }

        AuditLog::record('document.downloaded', 'Document', $document['id']);

        return ['content' => $plaintext, 'file_name' => $document['file_name'], 'file_type' => $document['file_type']];
    }

    public function delete(string $documentId): void
    {
        Document::softDelete($documentId);
        AuditLog::record('document.deleted', 'Document', $documentId);
    }

    public function signedDownloadUrl(string $documentId, int $ttlSeconds = 300): string
    {
        $signed = SignedUrl::sign($documentId, $ttlSeconds);

        return "/documents/{$documentId}/download?expires={$signed['expires']}&sig={$signed['signature']}";
    }

    public function isExpired(array $document): bool
    {
        return $document['expires_at'] !== null && strtotime($document['expires_at']) < time();
    }

    /**
     * Admin: full access. Company owner: access to their own company's
     * documents. Reviewer: access to documents of companies whose
     * application is currently assigned to them (and they haven't flagged a
     * conflict of interest on). Deliberately a pure function of its
     * arguments (no implicit session/Auth lookups) so access rules are
     * independently testable and unambiguous about which user is being
     * checked.
     */
    public function canAccess(array $document, ?string $userId, bool $isAdmin): bool
    {
        if ($isAdmin) {
            return true;
        }

        if ($userId === null) {
            return false;
        }

        $owner = Company::findByOwner($userId);
        if ($owner !== null && $owner['id'] === $document['company_id']) {
            return true;
        }

        foreach (ApplicationReviewer::forReviewer($userId) as $assignment) {
            if ($assignment['conflict_of_interest']) {
                continue;
            }
            $application = Application::find($assignment['application_id']);
            if ($application !== null && $application['company_id'] === $document['company_id']) {
                return true;
            }
        }

        return false;
    }
}
