<?php

namespace App\Filament\Resources\LeaseTemplateResource\Pages;

use App\Filament\Resources\LeaseTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLeaseTemplate extends ViewRecord
{
    protected static string $resource = LeaseTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pickCoordinates')
                ->label('Pick positions on PDF')
                ->icon('heroicon-o-cursor-arrow-rays')
                ->color('warning')
                ->url(fn () => LeaseTemplateResource::getUrl('pick-coordinates', ['record' => $this->record]))
                ->visible(fn () => ! empty($this->record->source_pdf_path)),

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
