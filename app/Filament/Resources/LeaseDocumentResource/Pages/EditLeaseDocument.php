<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaseDocumentResource\Pages;

use App\Filament\Resources\LeaseDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaseDocument extends EditRecord
{
    protected static string $resource = LeaseDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => $this->record->can_delete),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
