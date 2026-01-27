<?php

namespace App\Filament\Resources\LeaseTemplateResource\Pages;

use App\Filament\Resources\LeaseTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditLeaseTemplate extends EditRecord
{
    protected static string $resource = LeaseTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
        if (!empty($data['is_default']) && $this->record->template_type === $data['template_type']) {
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

    protected function getSavedNotification(): ?\Filament\Notifications\Notification
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
