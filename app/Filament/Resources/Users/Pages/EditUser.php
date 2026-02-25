<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $path = $data['signature_upload'] ?? null;
        if (is_string($path) && $path !== '') {
            $fullPath = storage_path('app/' . $path);
            if (file_exists($fullPath)) {
                $pngBytes = file_get_contents($fullPath);
                if ($pngBytes !== false) {
                    $this->record->signature_image = $pngBytes;
                    $this->record->saveQuietly();
                }
                Storage::disk('local')->delete($path);
            }
            unset($data['signature_upload']);
        }

        return $data;
    }
}
