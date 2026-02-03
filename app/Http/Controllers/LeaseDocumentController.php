<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LeaseDocument;
use App\Services\DocumentCompressionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaseDocumentController extends Controller
{
    public function __construct(
        private DocumentCompressionService $compressionService
    ) {}

    /**
     * Download document
     */
    public function download(LeaseDocument $leaseDocument): BinaryFileResponse|Response
    {
        // Check if user has access (for now, any authenticated user)
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        if (!$leaseDocument->fileExists()) {
            abort(404, 'Document file not found');
        }

        $filePath = $leaseDocument->getFullPath();
        $downloadName = $leaseDocument->original_filename;

        // If compressed, extract for download
        if ($leaseDocument->is_compressed && $leaseDocument->compression_method === 'zip') {
            $extractedPath = $this->compressionService->extractForDownload($leaseDocument->file_path);

            if (!$extractedPath) {
                abort(500, 'Failed to extract compressed file');
            }

            // Return extracted file and clean up after
            return response()->download($extractedPath, $downloadName)->deleteFileAfterSend(true);
        }

        return response()->download($filePath, $downloadName);
    }

    /**
     * Preview document (inline display)
     */
    public function preview(LeaseDocument $leaseDocument): Response|BinaryFileResponse
    {
        // Check if user has access
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

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

            // Return file inline
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
     * Verify document integrity
     */
    public function verifyIntegrity(LeaseDocument $leaseDocument): Response
    {
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

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
