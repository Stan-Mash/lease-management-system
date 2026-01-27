<?php

namespace App\Filament\Resources\LeaseTemplateResource\Pages;

use App\Filament\Resources\LeaseTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaseTemplates extends ListRecords
{
    protected static string $resource = LeaseTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
