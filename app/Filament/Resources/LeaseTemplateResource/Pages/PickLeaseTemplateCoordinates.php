<?php

namespace App\Filament\Resources\LeaseTemplateResource\Pages;

use App\Filament\Resources\LeaseTemplateResource;
use App\Models\LeaseTemplate;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class PickLeaseTemplateCoordinates extends Page
{
    protected static string $resource = LeaseTemplateResource::class;

    protected static string $view = 'filament.resources.lease-template-resource.pages.pick-lease-template-coordinates';

    protected static ?string $title = 'Pick Field Positions';

    public LeaseTemplate $record;

    public ?string $pdfUrl = null;

    public array $textFields = [
        'tenant_name' => 'Tenant name',
        'unit_code' => 'Unit code',
        'property_name' => 'Property name',
        'monthly_rent' => 'Monthly rent',
        'start_date' => 'Start date',
        'end_date' => 'End date',
        'landlord_name' => 'Landlord name',
        'reference_number' => 'Reference number',
    ];

    public array $signatureFields = [
        'tenant_signature' => 'Tenant signature (include width, height)',
        'manager_signature' => 'Manager signature (include width, height)',
    ];

    public function mount(LeaseTemplate | int | string $record): void
    {
        $this->record = $record instanceof LeaseTemplate ? $record : LeaseTemplate::findOrFail($record);

        if (empty($this->record->source_pdf_path)) {
            Notification::make()
                ->title('No PDF uploaded')
                ->body('This template has no source PDF. Upload a PDF on the Edit page first.')
                ->danger()
                ->send();

            $this->redirect(LeaseTemplateResource::getUrl('edit', ['record' => $this->record]));
        }

        // Resolve PDF URL for browser access
        $path = $this->record->source_pdf_path;
        foreach (['public', null] as $disk) {
            $fullPath = $disk
                ? storage_path('app/public/' . $path)
                : storage_path('app/' . $path);
            if (file_exists($fullPath)) {
                $this->pdfUrl = $disk
                    ? asset('storage/' . $path)
                    : route('templates.serve-pdf', ['template' => $this->record->id]);
                break;
            }
        }

        if (! $this->pdfUrl) {
            Notification::make()
                ->title('PDF file not found')
                ->body('The source PDF could not be found at: ' . $path)
                ->danger()
                ->send();
        }
    }

    public function saveCoordinates(array $coordinates): void
    {
        $this->record->update(['pdf_coordinate_map' => $coordinates]);

        Notification::make()
            ->title('Coordinates saved')
            ->body('The coordinate map has been saved. Generated leases will now use this PDF with field positions.')
            ->success()
            ->send();

        $this->redirect(LeaseTemplateResource::getUrl('edit', ['record' => $this->record]));
    }

    public static function getNavigationLabel(): string
    {
        return 'Pick positions';
    }
}
