<?php

namespace App\Filament\Resources\Leases\Pages;

use App\Filament\Resources\Leases\LeaseResource;
use App\Models\Guarantor;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLease extends EditRecord
{
    protected static string $resource = LeaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn () => $this->record->workflow_state === 'draft'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['guarantors'] = $this->record->guarantors
            ->map(fn (Guarantor $g) => [
                'name' => $g->name,
                'id_number' => $g->id_number,
                'phone' => $g->phone,
                'email' => $g->email,
                'relationship' => $g->relationship,
                'guarantee_amount' => $g->guarantee_amount,
                'signed' => $g->signed,
                'notes' => $g->notes,
            ])
            ->all();

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->guarantors()->delete();
        $items = $this->data['guarantors'] ?? [];
        if (! is_array($items)) {
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
