<?php

namespace App\Filament\Resources\LeaseTemplateResource\Pages;

use App\Filament\Resources\LeaseTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewLeaseTemplate extends ViewRecord
{
    protected static string $resource = LeaseTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Preview Template')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->modalHeading('Template Preview')
                ->modalContent(view('filament.pages.template-preview', ['template' => $this->record]))
                ->modalWidth('7xl')
                ->slideOver(),

            Actions\EditAction::make(),

            Actions\DeleteAction::make(),
        ];
    }
}
