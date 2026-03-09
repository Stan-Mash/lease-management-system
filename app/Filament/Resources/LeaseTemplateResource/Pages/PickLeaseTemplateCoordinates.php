<?php

namespace App\Filament\Resources\LeaseTemplateResource\Pages;

use App\Filament\Resources\LeaseTemplateResource;
use App\Models\LeaseTemplate;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;

class PickLeaseTemplateCoordinates extends Page
{
    protected static string $resource = LeaseTemplateResource::class;

    protected string $view = 'filament.resources.lease-template-resource.pages.pick-lease-template-coordinates';

    protected static ?string $title = 'Pick Field Positions';

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    public LeaseTemplate $record;

    public ?string $pdfUrl = null;

    public array $textFields = [
        'lease_date_day' => 'Date (day)',
        'lease_date_month' => 'Date (month name)',
        'lease_date_year' => 'Date (year)',
        'start_date_day' => 'Term from (day)',
        'start_date_month' => 'Term from (month)',
        'start_date_year' => 'Term from (year)',
        'end_date_day' => 'Term to (day)',
        'end_date_month' => 'Term to (month)',
        'end_date_year' => 'Term to (year)',
        'landlord_name' => 'Landlord / Lessor name',
        'landlord_po_box' => 'Landlord P.O. Box',
        'tenant_name' => 'Tenant / Lessee name',
        'tenant_id_number' => 'Tenant ID / Company reg no',
        'tenant_po_box' => 'Tenant P.O. Box',
        'property_name' => 'Property / Building name',
        'property_lr_number' => 'L.R. number',
        'unit_code' => 'Unit / Designed as',
        'monthly_rent' => 'Base rent',
        'deposit_amount' => 'Deposit',
        'vat_amount' => 'VAT amount',
        'start_date' => 'Start date (single field)',
        'end_date' => 'End date (single field)',
        'lease_duration_months' => 'Lease duration (e.g. 5 year(s) 3 month(s))',
        'grant_of_lease_duration' => 'Grant of Lease Duration (Section 2)',
        'reference_number' => 'Reference number',
    ];

    public array $signatureFields = [
        'tenant_signature' => 'Tenant signature (include width, height)',
        'manager_signature' => 'Manager/Lessor (include width, height; anchor: above)',
        'witness_signature' => 'Witness signature (include width, height)',
        'advocate_signature' => 'Advocate signature (include width, height; anchor: beside)',
        'guarantor_signature' => 'Guarantor signature (include width, height)',
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

        // Use the same path resolution as TemplatePreviewController so the file is found
        // in public, app, or app/private. Always use the serve-pdf route so the PDF is
        // served with auth and correct headers (no dependency on storage symlink).
        $path = $this->normalizePath($this->record->source_pdf_path);
        $fullPath = $this->resolveSourcePdfPath($path);
        if ($fullPath && file_exists($fullPath)) {
            $this->pdfUrl = Storage::disk('public')->url($this->record->source_pdf_path);
        }

        if (! $this->pdfUrl) {
            Notification::make()
                ->title('PDF file not found')
                ->body('The source PDF could not be found at: ' . $path)
                ->danger()
                ->send();
        }
    }

    /** Normalize path so Linux server finds files when path was saved from Windows. */
    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Resolve the full filesystem path for the template's source PDF.
     * Must match TemplatePreviewController::resolveSourcePdfPath() so serve-pdf finds the same file.
     */
    private function resolveSourcePdfPath(string $path): ?string
    {
        $candidates = [
            Storage::disk('public')->path($path),
            storage_path('app/public/' . $path),
            storage_path('app/' . $path),
            storage_path('app/private/' . $path),
        ];

        foreach ($candidates as $fullPath) {
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
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
