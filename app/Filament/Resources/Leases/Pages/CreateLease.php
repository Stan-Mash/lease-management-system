<?php

namespace App\Filament\Resources\Leases\Pages;

use App\Filament\Resources\Leases\LeaseResource;
use App\Services\LeaseReferenceService;
use Filament\Resources\Pages\CreateRecord;

class CreateLease extends CreateRecord
{
    protected static string $resource = LeaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate reference number
        $data['reference_number'] = LeaseReferenceService::generate(
            $data['lease_type'],
            $data['zone']
        );

        // Set initial workflow state
        $data['workflow_state'] = 'draft';

        // Set document version
        $data['document_version'] = 1;

        // Set created by
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
