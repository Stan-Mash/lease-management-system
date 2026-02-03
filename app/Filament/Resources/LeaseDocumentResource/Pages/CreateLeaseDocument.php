<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaseDocumentResource\Pages;

use App\Enums\DocumentStatus;
use App\Filament\Resources\LeaseDocumentResource;
use App\Services\DocumentCompressionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateLeaseDocument extends CreateRecord
{
    protected static string $resource = LeaseDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set uploaded_by
        $data['uploaded_by'] = auth()->id();

        // Set initial status
        $data['status'] = DocumentStatus::PENDING_REVIEW;

        // If file was uploaded via Filament's FileUpload
        if (isset($data['file_path']) && is_string($data['file_path'])) {
            $filePath = $data['file_path'];
            $fullPath = storage_path('app/public/' . $filePath);

            if (file_exists($fullPath)) {
                // Get file info
                $data['original_filename'] = basename($filePath);
                $data['mime_type'] = mime_content_type($fullPath);
                $data['file_size'] = filesize($fullPath);
                $data['file_hash'] = hash_file('sha256', $fullPath);

                // Move file to organized structure
                $compressionService = app(DocumentCompressionService::class);

                // Build proper path
                $basePath = 'lease-documents';
                $zoneId = $data['zone_id'] ?? null;
                $propertyId = $data['property_id'] ?? null;
                $year = $data['document_year'] ?? date('Y');

                $newPath = $basePath;
                if ($zoneId) {
                    $newPath .= '/zone_' . $zoneId;
                }
                if ($propertyId) {
                    $newPath .= '/property_' . $propertyId;
                }
                $newPath .= '/' . $year;

                // Ensure directory exists
                Storage::disk('local')->makeDirectory($newPath);

                // Move file
                $newFilePath = $newPath . '/' . basename($filePath);
                Storage::disk('local')->move('public/' . $filePath, $newFilePath);

                $data['file_path'] = $newFilePath;
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Document uploaded successfully and submitted for review';
    }
}
