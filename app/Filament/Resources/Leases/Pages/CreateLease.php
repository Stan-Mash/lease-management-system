<?php

namespace App\Filament\Resources\Leases\Pages;

use App\Filament\Resources\Leases\LeaseResource;
use App\Models\Guarantor;
use App\Models\Unit;
use App\Services\LeaseReferenceService;
use Filament\Resources\Pages\CreateRecord;

class CreateLease extends CreateRecord
{
    protected static string $resource = LeaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate reference number
        $unit = Unit::find($data['unit_id'] ?? null);
        $unitCode = $unit?->unit_code ?? 'UNK';
        $data['reference_number'] = LeaseReferenceService::generate(
            $data['source'] ?? 'chabrin_issued',
            $data['lease_type'],
            $unitCode,
            null,
            $data['zone_id'] ?? null,
        );

        $data['date_created'] = $data['date_created'] ?? now();
        $data['workflow_state'] = 'draft';
        $data['document_version'] = 1;

        // Set created by
        $data['created_by'] = auth()->id();

        // Don't pass repeater data to the model (handled in afterCreate)
        unset($data['guarantors']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $items = $this->data['guarantors'] ?? [];
        if (empty($items) || ! is_array($items)) {
            return;
        }
        foreach ($items as $row) {
            if (empty($row['name'] ?? null)) {
                continue;
            }
            Guarantor::create([
                'lease_id' => $this->record->id,
                'name' => $row['name'],
                'id_number' => $row['id_number'] ?? '',
                'phone' => $row['phone'] ?? '',
                'email' => $row['email'] ?? null,
                'relationship' => $row['relationship'] ?? 'Other',
                'guarantee_amount' => $row['guarantee_amount'] ?? null,
                'signed' => (bool) ($row['signed'] ?? false),
                'notes' => $row['notes'] ?? null,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
