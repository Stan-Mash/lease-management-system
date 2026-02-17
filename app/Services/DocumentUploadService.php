<?php

namespace App\Services;

use App\Models\LeaseDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Log;

class DocumentUploadService
{
    private const MAX_IMAGE_DIMENSION = 1920;

    private const JPEG_QUALITY = 75;

    private const PDF_MAX_SIZE_MB = 10;

    public function upload(
        UploadedFile $file,
        int $leaseId,
        string $documentType,
        string $title,
        ?string $description = null,
        ?string $documentDate = null,
    ): LeaseDocument {
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $originalSize = $file->getSize();

        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $directory = "lease-documents/{$leaseId}";
        $filePath = "{$directory}/{$filename}";

        // Process and compress based on file type
        $processedFile = $this->processFile($file, $mimeType);
        $compressedSize = strlen($processedFile);
        $isCompressed = $compressedSize < $originalSize;

        // Store the file
        Storage::disk('local')->put($filePath, $processedFile);

        // Calculate hash for integrity
        $fileHash = hash('sha256', $processedFile);

        return LeaseDocument::create([
            'lease_id' => $leaseId,
            'document_type' => $documentType,
            'title' => $title,
            'description' => $description,
            'file_path' => $filePath,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'file_size' => $originalSize,
            'compressed_size' => $isCompressed ? $compressedSize : null,
            'file_hash' => $fileHash,
            'is_compressed' => $isCompressed,
            'uploaded_by' => auth()->id(),
            'document_date' => $documentDate,
        ]);
    }

    public function delete(LeaseDocument $document): bool
    {
        // Delete file from storage
        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        return $document->delete();
    }

    private function processFile(UploadedFile $file, string $mimeType): string
    {
        // Handle images - compress and resize
        if (str_starts_with($mimeType, 'image/')) {
            return $this->compressImage($file);
        }

        // Handle PDFs - optimize if possible
        if ($mimeType === 'application/pdf') {
            return $this->processPdf($file);
        }

        // For other files, return as-is
        return file_get_contents($file->getRealPath());
    }

    private function compressImage(UploadedFile $file): string
    {
        $image = match ($file->getMimeType()) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($file->getRealPath()),
            'image/png' => imagecreatefrompng($file->getRealPath()),
            'image/gif' => imagecreatefromgif($file->getRealPath()),
            'image/webp' => imagecreatefromwebp($file->getRealPath()),
            default => null,
        };

        if (! $image) {
            return file_get_contents($file->getRealPath());
        }

        // Get dimensions
        $width = imagesx($image);
        $height = imagesy($image);

        // Resize if too large
        if ($width > self::MAX_IMAGE_DIMENSION || $height > self::MAX_IMAGE_DIMENSION) {
            $ratio = min(self::MAX_IMAGE_DIMENSION / $width, self::MAX_IMAGE_DIMENSION / $height);
            $newWidth = (int) ($width * $ratio);
            $newHeight = (int) ($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG
            if ($file->getMimeType() === 'image/png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        // Output to string
        ob_start();
        imagejpeg($image, null, self::JPEG_QUALITY);
        $output = ob_get_clean();
        imagedestroy($image);

        return $output;
    }

    private function processPdf(UploadedFile $file): string
    {
        // For now, just return the original PDF
        // In production, you could use Ghostscript or similar for compression
        $content = file_get_contents($file->getRealPath());

        // If file is larger than limit, we could implement PDF compression here
        // using external tools like Ghostscript
        $sizeMb = strlen($content) / 1024 / 1024;

        if ($sizeMb > self::PDF_MAX_SIZE_MB) {
            // Log warning about large file
            Log::warning("Large PDF uploaded: {$sizeMb}MB for file {$file->getClientOriginalName()}");
        }

        return $content;
    }
}
