<?php

namespace App\Filament\Resources\LawyerResource\Pages;

use App\Filament\Resources\LawyerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLawyers extends ListRecords
{
    protected static string $resource = LawyerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
