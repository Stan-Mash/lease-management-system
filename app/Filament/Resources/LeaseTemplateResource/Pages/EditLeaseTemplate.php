<?php

namespace App\Filament\Resources\LeaseTemplateResource\Pages;

use App\Filament\Resources\LeaseTemplateResource;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLeaseTemplate extends EditRecord
{
    protected static string $resource = LeaseTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('viewUploadedPdf')
                ->label('View uploaded PDF')
                ->icon('heroicon-o-document-magnifying-glass')
                ->url(fn () => route('templates.serve-pdf', ['template' => $this->record]))
                ->visible(fn () => ! empty($this->record->source_pdf_path))
                ->openUrlInNewTab()
                ->tooltip('Open the raw uploaded PDF (no data stamped)'),

            Actions\Action::make('pickCoordinates')
                ->label('Pick positions on PDF')
                ->icon('heroicon-o-cursor-arrow-rays')
                ->color('warning')
                ->url(fn () => LeaseTemplateResource::getUrl('pick-coordinates', ['record' => $this->record]))
                ->visible(fn () => ! empty($this->record->source_pdf_path))
                ->tooltip('Click on the PDF to mark where each field appears (makes output match your PDF exactly)'),

            Actions\Action::make('preview_pdf')
                ->label('Preview as PDF')
                ->icon('heroicon-o-document')
                ->color('success')
                ->url(fn () => route('templates.preview-pdf', ['template' => $this->record]))
                ->openUrlInNewTab()
                ->tooltip('Open PDF preview with sample data'),

            Actions\Action::make('preview_code')
                ->label('View Code')
                ->icon('heroicon-o-code-bracket')
                ->color('info')
                ->modalHeading('Template Code Preview')
                ->modalContent(view('filament.pages.template-preview', ['template' => $this->record]))
                ->modalWidth('7xl')
                ->slideOver()
                ->tooltip('View template code and structure'),

            Actions\Action::make('restoreFromVersion')
                ->label('Restore from version')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->modalHeading('Restore template to a previous version')
                ->modalDescription('This restores the template text, styles, and variables from the selected version. It does not restore the uploaded PDF or coordinate map (those are not stored in version history).')
                ->form([
                    Select::make('version_number')
                        ->label('Version to restore')
                        ->options(function () {
                            $versions = $this->record->versions()->orderByDesc('version_number')->get();
                            if ($versions->isEmpty()) {
                                return [];
                            }
                            return $versions->mapWithKeys(function ($v) {
                                $label = sprintf(
                                    'v%d — %s — %s',
                                    $v->version_number,
                                    $v->created_at->format('Y-m-d H:i'),
                                    $v->change_summary ?? 'No summary'
                                );
                                return [$v->version_number => $label];
                            })->all();
                        })
                        ->required()
                        ->searchable()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $num = (int) $data['version_number'];
                    $ok = $this->record->restoreFromVersion($num);
                    if ($ok) {
                        Notification::make()
                            ->success()
                            ->title('Template restored')
                            ->body("Restored to version {$num}. Reloading form.")
                            ->send();
                        $this->redirect(LeaseTemplateResource::getUrl('edit', ['record' => $this->record]));
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Restore failed')
                            ->body("Version {$num} not found.")
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->versions()->exists())
                ->tooltip('Restore template content from a previous version (Blade, CSS, variables only)'),

            Actions\Action::make('version_history')
                ->label('Version History')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->url(fn () => static::getResource()::getUrl('view', ['record' => $this->record]))
                ->tooltip('View all versions'),

            Actions\Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $newTemplate = $this->record->replicate();
                    $newTemplate->name = $this->record->name . ' (Copy)';
                    $newTemplate->slug = null; // Will auto-generate
                    $newTemplate->is_default = false;
                    $newTemplate->version_number = 1;
                    $newTemplate->created_by = auth()->id();
                    $newTemplate->updated_by = auth()->id();
                    $newTemplate->save();

                    Notification::make()
                        ->success()
                        ->title('Template Duplicated')
                        ->body('A copy of this template has been created.')
                        ->send();

                    return redirect()->to(static::getResource()::getUrl('edit', ['record' => $newTemplate]));
                }),

            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        // If marked as default, unset other defaults for this type
        if (! empty($data['is_default']) && $this->record->template_type === $data['template_type']) {
            \App\Models\LeaseTemplate::where('template_type', $data['template_type'])
                ->where('is_default', true)
                ->where('id', '!=', $this->record->id)
                ->update(['is_default' => false]);
        }

        // Extract variables from blade content if it changed
        if (isset($data['blade_content']) && $data['blade_content'] !== $this->record->blade_content) {
            preg_match_all('/\{\{\s*\$([a-zA-Z0-9_>-]+)\s*\}\}/', $data['blade_content'], $matches);
            $data['available_variables'] = array_unique($matches[1] ?? []);
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        $wasContentChanged = $this->record->wasChanged('blade_content') || $this->record->wasChanged('css_styles');

        if ($wasContentChanged) {
            return Notification::make()
                ->success()
                ->title('Template Updated')
                ->body('A new version (v' . $this->record->version_number . ') has been created automatically.');
        }

        return Notification::make()
            ->success()
            ->title('Template Updated')
            ->body('Template settings have been saved.');
    }
}
