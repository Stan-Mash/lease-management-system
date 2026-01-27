<?php

namespace App\Filament\Resources\LawyerResource\Pages;

use App\Filament\Resources\LawyerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLawyer extends EditRecord
{
    protected static string $resource = LawyerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
