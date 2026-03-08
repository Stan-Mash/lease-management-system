<?php

namespace App\Filament\Resources\LeaseTemplateResource\Pages;

use App\Filament\Resources\LeaseTemplateResource;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLeaseTemplate extends ViewRecord
{
    protected static string $resource = LeaseTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit')
                ->tooltip('Click Edit to upload or change the PDF'),

            Actions\Action::make('restoreFromVersion')
                ->label('Restore from version')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->modalHeading('Restore template to a previous version')
                ->modalDescription('This restores the template text, styles, and variables from the selected version. It does not restore the uploaded PDF or coordinate map.')
                ->form([
                    Select::make('version_number')
                        ->label('Version to restore')
                        ->options(function () {
                            $versions = $this->record->versions()->orderByDesc('version_number')->get();
                            return $versions->isEmpty()
                                ? []
                                : $versions->mapWithKeys(fn ($v) => [
                                    $v->version_number => sprintf('v%d — %s — %s', $v->version_number, $v->created_at->format('Y-m-d H:i'), $v->change_summary ?? 'No summary'),
                                ])->all();
                        })
                        ->required()
                        ->searchable()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $num = (int) $data['version_number'];
                    if ($this->record->restoreFromVersion($num)) {
                        Notification::make()->success()->title('Template restored')->body("Restored to version {$num}.")->send();
                        $this->redirect(LeaseTemplateResource::getUrl('edit', ['record' => $this->record]));
                    } else {
                        Notification::make()->danger()->title('Restore failed')->body("Version {$num} not found.")->send();
                    }
                })
                ->visible(fn () => $this->record->versions()->exists())
                ->tooltip('Restore template content from a previous version'),

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

            Actions\DeleteAction::make(),
        ];
    }
}
