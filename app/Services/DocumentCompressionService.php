<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ZipArchive;

class DocumentCompressionService
{
    /**
     * Maximum file size in bytes before compression is applied (5MB)
     */
    private const COMPRESSION_THRESHOLD = 5 * 1024 * 1024;

    /**
     * Supported MIME types for processing
     */
    private const SUPPORTED_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'image/tiff',
        'image/gif',
    ];

    /**
     * MIME types that are already compressed (skip compression)
     */
    private const ALREADY_COMPRESSED = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
    ];

    /**
     * Store and optionally compress an uploaded file
     */
    public function storeAndCompress(
        UploadedFile $file,
        string $basePath,
        ?int $zoneId = null,
        ?int $propertyId = null,
        ?int $year = null,
    ): array {
        // Validate file type
        $mimeType = $file->getMimeType();
        if (! in_array($mimeType, self::SUPPORTED_TYPES, true)) {
            throw new InvalidArgumentException("Unsupported file type: {$mimeType}");
        }

        // Build storage path
        $storagePath = $this->buildStoragePath($basePath, $zoneId, $propertyId, $year);

        // Generate unique filename
        $uuid = Str::uuid()->toString();
        $extension = $file->getClientOriginalExtension() ?: $this->getExtensionFromMime($mimeType);
        $originalFilename = $file->getClientOriginalName();
        $safeFilename = $uuid . '_' . Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) . '.' . $extension;

        // Get original file size
        $originalSize = $file->getSize();

        // Determine if compression should be applied
        $shouldCompress = $this->shouldCompress($mimeType, $originalSize);

        if ($shouldCompress) {
            return $this->compressAndStore($file, $storagePath, $safeFilename, $originalSize);
        }

        // Store without compression
        $filePath = $file->storeAs($storagePath, $safeFilename, 'local');

        return [
            'file_path' => $filePath,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'file_size' => $originalSize,
            'compressed_size' => null,
            'is_compressed' => false,
            'compression_method' => null,
            'file_hash' => $this->calculateHash($file->getRealPath()),
        ];
    }

    /**
     * Calculate SHA-256 hash of file
     */
    public function calculateHash(string $filePath): string
    {
        return hash_file('sha256', $filePath);
    }

    /**
     * Verify file integrity against stored hash
     */
    public function verifyIntegrity(string $filePath, string $expectedHash): bool
    {
        if (! Storage::disk('local')->exists($filePath)) {
            return false;
        }

        $actualHash = hash_file('sha256', storage_path('app/' . $filePath));

        return hash_equals($expectedHash, $actualHash);
    }

    /**
     * Extract compressed file for viewing/download
     */
    public function extractForDownload(string $compressedPath): ?string
    {
        $fullPath = storage_path('app/' . $compressedPath);

        if (! file_exists($fullPath)) {
            return null;
        }

        $zip = new ZipArchive;
        if ($zip->open($fullPath) !== true) {
            return null;
        }

        // Extract to temp directory
        $tempDir = storage_path('app/temp/' . Str::uuid());
        mkdir($tempDir, 0755, true);

        $zip->extractTo($tempDir);
        $extractedFile = $tempDir . '/' . $zip->getNameIndex(0);
        $zip->close();

        return $extractedFile;
    }

    /**
     * Clean up temporary extracted files
     */
    public function cleanupTempFile(string $tempPath): void
    {
        if (file_exists($tempPath)) {
            unlink($tempPath);

            // Remove parent temp directory if empty
            $tempDir = dirname($tempPath);
            if (is_dir($tempDir) && count(scandir($tempDir)) === 2) {
                rmdir($tempDir);
            }
        }
    }

    /**
     * Get human-readable file size
     */
    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Calculate compression ratio
     */
    public function calculateCompressionRatio(int $originalSize, int $compressedSize): float
    {
        if ($originalSize === 0) {
            return 0;
        }

        return round((1 - ($compressedSize / $originalSize)) * 100, 1);
    }

    /**
     * Get supported MIME types
     */
    public static function getSupportedMimeTypes(): array
    {
        return self::SUPPORTED_TYPES;
    }

    /**
     * Get supported file extensions
     */
    public static function getSupportedExtensions(): array
    {
        return ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'tiff', 'tif', 'gif'];
    }

    /**
     * Compress and store the file
     */
    private function compressAndStore(
        UploadedFile $file,
        string $storagePath,
        string $filename,
        int $originalSize,
    ): array {
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $tempPath = $file->getRealPath();

        // Create zip archive
        $zipFilename = pathinfo($filename, PATHINFO_FILENAME) . '.zip';
        $zipPath = storage_path('app/' . $storagePath . '/' . $zipFilename);

        // Ensure directory exists
        $directory = dirname($zipPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            // Fallback: store without compression
            Log::warning('DocumentCompressionService: Failed to create ZIP archive, storing uncompressed');
            $filePath = $file->storeAs($storagePath, $filename, 'local');

            return [
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'mime_type' => $mimeType,
                'file_size' => $originalSize,
                'compressed_size' => null,
                'is_compressed' => false,
                'compression_method' => null,
                'file_hash' => $this->calculateHash($tempPath),
            ];
        }

        // Add file to archive with maximum compression
        $zip->addFile($tempPath, $originalFilename);
        $zip->setCompressionName($originalFilename, ZipArchive::CM_DEFLATE);
        $zip->close();

        // Get compressed size
        $compressedSize = filesize($zipPath);

        // Calculate hash of original file (before compression)
        $fileHash = $this->calculateHash($tempPath);

        // Check if compression was effective (at least 10% reduction)
        if ($compressedSize >= ($originalSize * 0.9)) {
            // Compression not effective, store original instead
            unlink($zipPath);
            $filePath = $file->storeAs($storagePath, $filename, 'local');

            return [
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'mime_type' => $mimeType,
                'file_size' => $originalSize,
                'compressed_size' => null,
                'is_compressed' => false,
                'compression_method' => null,
                'file_hash' => $fileHash,
            ];
        }

        Log::info('DocumentCompressionService: File compressed', [
            'original_size' => $originalSize,
            'compressed_size' => $compressedSize,
            'reduction' => round((1 - $compressedSize / $originalSize) * 100, 1) . '%',
        ]);

        return [
            'file_path' => $storagePath . '/' . $zipFilename,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'file_size' => $originalSize,
            'compressed_size' => $compressedSize,
            'is_compressed' => true,
            'compression_method' => 'zip',
            'file_hash' => $fileHash,
        ];
    }

    /**
     * Determine if file should be compressed
     */
    private function shouldCompress(string $mimeType, int $fileSize): bool
    {
        // Skip small files
        if ($fileSize < self::COMPRESSION_THRESHOLD) {
            return false;
        }

        // Skip already-compressed formats
        if (in_array($mimeType, self::ALREADY_COMPRESSED, true)) {
            return false;
        }

        return true;
    }

    /**
     * Build storage path based on organization
     */
    private function buildStoragePath(
        string $basePath,
        ?int $zoneId,
        ?int $propertyId,
        ?int $year,
    ): string {
        $path = trim($basePath, '/');

        if ($zoneId) {
            $path .= '/zone_' . $zoneId;
        }

        if ($propertyId) {
            $path .= '/property_' . $propertyId;
        }

        if ($year) {
            $path .= '/' . $year;
        } else {
            $path .= '/' . date('Y');
        }

        return $path;
    }

    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMime(string $mimeType): string
    {
        return match ($mimeType) {
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/tiff' => 'tiff',
            'image/gif' => 'gif',
            default => 'bin',
        };
    }
}
