<?php

declare(strict_types=1);

namespace App\Features\Document\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Tenant;
use App\Features\Document\Models\Document;
use App\Features\Document\Services\DocumentService;
use App\Lib\DateConverter;

final class DocumentController extends Controller
{
    private DocumentService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new DocumentService();
    }

    public function index(): void
    {
        $companyId = Tenant::companyId();
        $documents = $companyId !== null ? Document::forCompany($companyId) : [];

        $documents = array_map(function (array $d) {
            $d['is_expired'] = $this->service->isExpired($d);
            $d['download_url'] = $this->service->signedDownloadUrl($d['id']);

            return $d;
        }, $documents);

        $this->render('Document::index', ['documents' => $documents, 'types' => DocumentService::TYPES]);
    }

    public function upload(): void
    {
        $this->requireCsrf();

        $companyId = Tenant::companyId();

        if ($companyId === null) {
            $this->redirect('/company/create');

            return;
        }

        $documentType = (string) Request::input('document_type', 'OTHER');
        $expiresAtJalali = (string) Request::input('expires_at', '');
        $replaces = (string) Request::input('replaces_document_id', '');

        try {
            $this->service->upload(
                $companyId,
                Auth::id(),
                $_FILES['file'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'name' => '', 'tmp_name' => '', 'size' => 0],
                array_key_exists($documentType, DocumentService::TYPES) ? $documentType : 'OTHER',
                $expiresAtJalali !== '' ? DateConverter::fromJalali($expiresAtJalali, '23:59:59') : null,
                $replaces !== '' ? $replaces : null
            );
            Session::flash('status', 'سند با موفقیت بارگذاری شد.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/documents');
    }

    public function download(string $id): void
    {
        $document = Document::find($id);

        $expires = (int) Request::input('expires', '0');
        $signature = (string) Request::input('sig', '');

        if ($document === null || !\App\Lib\SignedUrl::verify($id, $expires, $signature)) {
            http_response_code(403);
            echo 'دسترسی غیرمجاز یا لینک منقضی‌شده.';

            return;
        }

        if (!$this->service->canAccess($document, Auth::id(), Auth::hasRole('Admin'))) {
            http_response_code(403);
            echo 'شما به این سند دسترسی ندارید.';

            return;
        }

        try {
            $file = $this->service->decryptForDownload($document);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo $e->getMessage();

            return;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . rawurlencode($file['file_name']) . '"');
        header('Content-Length: ' . strlen($file['content']));
        header('X-Content-Type-Options: nosniff');
        echo $file['content'];
    }

    public function delete(string $id): void
    {
        $this->requireCsrf();

        $companyId = Tenant::companyId();
        $document = Document::find($id);

        if ($document === null || ($document['company_id'] !== $companyId && !Auth::hasRole('Admin'))) {
            Session::flash('error', 'دسترسی غیرمجاز.');
            $this->redirect('/documents');

            return;
        }

        $this->service->delete($id);
        Session::flash('status', 'سند حذف شد.');
        $this->redirect('/documents');
    }
}
