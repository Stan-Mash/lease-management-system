<?php

namespace App\Filament\Pages;

use App\Models\Lease;
use App\Models\LeaseDocument;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Services\DocumentUploadService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class UploadPhysicalLeases extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.pages.upload-physical-leases';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-arrow-up-tray';
    }

    public static function getNavigationLabel(): string
    {
        return 'Upload Physical Leases';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Lease Management';
    }

    public function getTitle(): string
    {
        return 'Upload Scanned Physical Leases';
    }

    public ?array $data = [];

    // Form fields
    public $property_id = null;
    public $unit_id = null;
    public $tenant_id = null;
    public $lease_id = null;
    public $document_type = 'signed_physical_lease';
    public $document_title = '';
    public $document_date = null;
    public $description = '';
    public $file = null;

    // For creating new lease if needed
    public $create_new_lease = false;
    public $monthly_rent = null;
    public $deposit_amount = null;
    public $start_date = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Find or Create Lease')
                    ->description('Search for an existing lease or create a new one for the physical document')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('property_id')
                                ->label('Property')
                                ->options(Property::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn () => $this->resetUnitAndTenant()),

                            Select::make('unit_id')
                                ->label('Unit')
                                ->options(function () {
                                    if (!$this->property_id) return [];
                                    return Unit::where('property_id', $this->property_id)
                                        ->orderBy('unit_number')
                                        ->pluck('unit_number', 'id');
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(fn () => $this->findExistingLease()),

                            Select::make('tenant_id')
                                ->label('Tenant')
                                ->options(Tenant::orderBy('full_name')->get()->mapWithKeys(fn ($t) => [
                                    $t->id => $t->full_name . ' (' . $t->id_number . ')'
                                ]))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn () => $this->findExistingLease()),
                        ]),

                        Select::make('lease_id')
                            ->label('Existing Lease')
                            ->options(function () {
                                $query = Lease::query();
                                if ($this->unit_id) {
                                    $query->where('unit_id', $this->unit_id);
                                }
                                if ($this->tenant_id) {
                                    $query->where('tenant_id', $this->tenant_id);
                                }
                                if (!$this->unit_id && !$this->tenant_id) {
                                    return [];
                                }
                                return $query->with('tenant')->get()->mapWithKeys(fn ($l) => [
                                    $l->id => $l->reference_number . ' - ' . ($l->tenant?->full_name ?? 'Unknown')
                                ]);
                            })
                            ->searchable()
                            ->helperText('Select an existing lease to attach the document, or leave empty to create a new one')
                            ->live(),

                        // Fields for creating new lease if no existing one selected
                        Section::make('New Lease Details')
                            ->description('Fill these if creating a new lease record for this physical document')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('monthly_rent')
                                        ->label('Monthly Rent')
                                        ->numeric()
                                        ->prefix('Ksh')
                                        ->required(fn () => !$this->lease_id),

                                    TextInput::make('deposit_amount')
                                        ->label('Deposit Amount')
                                        ->numeric()
                                        ->prefix('Ksh'),

                                    DatePicker::make('start_date')
                                        ->label('Lease Start Date')
                                        ->required(fn () => !$this->lease_id),
                                ]),
                            ])
                            ->visible(fn () => !$this->lease_id && ($this->unit_id || $this->tenant_id))
                            ->collapsible(),
                    ]),

                Section::make('Document Details')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('document_type')
                                ->label('Document Type')
                                ->options([
                                    'signed_physical_lease' => 'Signed Physical Lease',
                                    'original_signed' => 'Original Signed Lease',
                                    'amendment' => 'Amendment',
                                    'addendum' => 'Addendum',
                                    'notice' => 'Notice',
                                    'id_copy' => 'Tenant ID Copy',
                                    'deposit_receipt' => 'Deposit Receipt',
                                    'other' => 'Other Document',
                                ])
                                ->default('signed_physical_lease')
                                ->required(),

                            DatePicker::make('document_date')
                                ->label('Date on Document')
                                ->required()
                                ->helperText('The date written on the physical document'),
                        ]),

                        TextInput::make('document_title')
                            ->label('Document Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Signed Lease - John Doe - Unit 314E-01')
                            ->helperText('A descriptive title for easy searching'),

                        Textarea::make('description')
                            ->label('Notes')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('e.g., File cabinet A, folder 23, scanned on 29/01/2026'),

                        FileUpload::make('file')
                            ->label('Scanned Document')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(10240) // 10MB
                            ->disk('local')
                            ->directory('temp-uploads')
                            ->helperText('PDF or scanned images (max 10MB). Images are automatically compressed.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function resetUnitAndTenant(): void
    {
        $this->unit_id = null;
        $this->lease_id = null;
    }

    public function findExistingLease(): void
    {
        if ($this->unit_id || $this->tenant_id) {
            $query = Lease::query();
            if ($this->unit_id) {
                $query->where('unit_id', $this->unit_id);
            }
            if ($this->tenant_id) {
                $query->where('tenant_id', $this->tenant_id);
            }
            $lease = $query->first();
            if ($lease) {
                $this->lease_id = $lease->id;
            }
        }
    }

    public function upload(): void
    {
        $data = $this->form->getState();

        // Validate we have enough info
        if (!$data['lease_id'] && (!$data['unit_id'] && !$data['tenant_id'])) {
            Notification::make()
                ->danger()
                ->title('Missing Information')
                ->body('Please select a property/unit or tenant to associate the document with.')
                ->send();
            return;
        }

        if (!$data['file']) {
            Notification::make()
                ->danger()
                ->title('No File')
                ->body('Please select a file to upload.')
                ->send();
            return;
        }

        DB::beginTransaction();
        try {
            $leaseId = $data['lease_id'];

            // Create new lease if needed
            if (!$leaseId) {
                $unit = $data['unit_id'] ? Unit::find($data['unit_id']) : null;
                $property = $unit?->property ?? ($data['property_id'] ? Property::find($data['property_id']) : null);

                $lease = Lease::create([
                    'reference_number' => 'PHY-' . strtoupper(uniqid()),
                    'source' => 'physical_upload',
                    'lease_type' => $unit?->unit_type ?? 'residential_major',
                    'signing_mode' => 'physical',
                    'workflow_state' => 'active', // Historical leases are already active
                    'tenant_id' => $data['tenant_id'],
                    'unit_id' => $data['unit_id'],
                    'property_id' => $property?->id,
                    'landlord_id' => $property?->landlord_id,
                    'zone' => $property?->zone ?? 'A',
                    'monthly_rent' => $data['monthly_rent'] ?? 0,
                    'deposit_amount' => $data['deposit_amount'] ?? 0,
                    'start_date' => $data['start_date'],
                    'created_by' => auth()->id(),
                ]);
                $leaseId = $lease->id;
            }

            // Upload the document
            $uploadService = new DocumentUploadService();
            $filePath = $data['file'];
            $fullPath = storage_path('app/' . $filePath);

            if (!file_exists($fullPath)) {
                throw new \Exception('Could not find uploaded file.');
            }

            $file = new \Illuminate\Http\UploadedFile(
                $fullPath,
                basename($filePath),
                mime_content_type($fullPath),
                null,
                true
            );

            $document = $uploadService->upload(
                $file,
                $leaseId,
                $data['document_type'],
                $data['document_title'],
                $data['description'] ?? null,
                $data['document_date'] ?? null
            );

            // Clean up temp file
            @unlink($fullPath);

            DB::commit();

            $message = "Document uploaded successfully.";
            if ($document->is_compressed && $document->compression_ratio) {
                $message .= " Compressed by {$document->compression_ratio}%.";
            }
            if (!$data['lease_id']) {
                $message .= " New lease record created.";
            }

            Notification::make()
                ->success()
                ->title('Upload Complete')
                ->body($message)
                ->send();

            // Reset form for next upload
            $this->form->fill();
            $this->property_id = null;
            $this->unit_id = null;
            $this->tenant_id = null;
            $this->lease_id = null;

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->danger()
                ->title('Upload Failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Allow admins and users with lease management roles
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole(['admin', 'super_admin', 'zone_manager', 'field_officer']);
        }

        return true; // Default allow if no role system
    }
}
