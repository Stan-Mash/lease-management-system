<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DocumentAudit;
use App\Models\LeaseDocument;
use App\Services\DocumentCompressionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LeaseDocumentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DocumentCompressionService $compressionService
    ) {}

    /**
     * Download document with policy-based authorization and audit logging.
     */
    public function download(LeaseDocument $leaseDocument): BinaryFileResponse|Response
    {
        $this->authorize('download', $leaseDocument);

        if (!$leaseDocument->fileExists()) {
            abort(404, 'Document file not found');
        }

        // Log the download action
        $leaseDocument->logAudit(
            DocumentAudit::ACTION_DOWNLOAD,
            'Document downloaded by ' . auth()->user()->name
        );

        $filePath = $leaseDocument->getFullPath();
        $downloadName = $leaseDocument->original_filename;

        // If compressed, extract for download
        if ($leaseDocument->is_compressed && $leaseDocument->compression_method === 'zip') {
            $extractedPath = $this->compressionService->extractForDownload($leaseDocument->file_path);

            if (!$extractedPath) {
                abort(500, 'Failed to extract compressed file');
            }

            return response()->download($extractedPath, $downloadName)->deleteFileAfterSend(true);
        }

        return response()->download($filePath, $downloadName);
    }

    /**
     * Preview document inline with policy-based authorization.
     */
    public function preview(LeaseDocument $leaseDocument): Response|BinaryFileResponse
    {
        $this->authorize('view', $leaseDocument);

        if (!$leaseDocument->fileExists()) {
            abort(404, 'Document file not found');
        }

        // Only allow preview for certain MIME types
        $previewableMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
        ];

        if (!in_array($leaseDocument->mime_type, $previewableMimes, true)) {
            abort(400, 'This file type cannot be previewed');
        }

        $filePath = $leaseDocument->getFullPath();

        // If compressed, extract for preview
        if ($leaseDocument->is_compressed && $leaseDocument->compression_method === 'zip') {
            $extractedPath = $this->compressionService->extractForDownload($leaseDocument->file_path);

            if (!$extractedPath) {
                abort(500, 'Failed to extract compressed file');
            }

            return response()->file($extractedPath, [
                'Content-Type' => $leaseDocument->mime_type,
                'Content-Disposition' => 'inline; filename="' . $leaseDocument->original_filename . '"',
            ])->deleteFileAfterSend(true);
        }

        return response()->file($filePath, [
            'Content-Type' => $leaseDocument->mime_type,
            'Content-Disposition' => 'inline; filename="' . $leaseDocument->original_filename . '"',
        ]);
    }

    /**
     * Verify document integrity with policy-based authorization.
     */
    public function verifyIntegrity(LeaseDocument $leaseDocument): JsonResponse
    {
        $this->authorize('view', $leaseDocument);

        $isValid = $leaseDocument->verifyIntegrity();

        return response()->json([
            'document_id' => $leaseDocument->id,
            'title' => $leaseDocument->title,
            'stored_hash' => $leaseDocument->file_hash,
            'integrity_valid' => $isValid,
            'checked_at' => now()->toIso8601String(),
        ]);
    }
}
