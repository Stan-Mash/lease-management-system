<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaseDocumentResource\Pages;

use App\Enums\DocumentQuality;
use App\Enums\DocumentSource;
use App\Enums\DocumentStatus;
use App\Filament\Resources\LeaseDocumentResource;
use App\Models\DocumentAudit;
use App\Models\Lease;
use App\Models\LeaseDocument;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Services\DocumentCompressionService;
use App\Services\DocumentUploadService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class DocumentUploadCenter extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static string $resource = LeaseDocumentResource::class;

    protected string $view = 'filament.resources.lease-document-resource.pages.document-upload-center';

    protected static ?string $title = 'Upload Center';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cloud-arrow-up';

    // Current active tab
    public string $activeTab = 'bulk';

    // ===== BULK UPLOAD DATA =====
    public ?array $bulkData = [];
    public int $bulkSuccessCount = 0;
    public int $bulkFailedCount = 0;
    public array $bulkErrors = [];

    // ===== SINGLE UPLOAD DATA =====
    public ?array $singleData = [];

    // ===== LEASE-LINKED UPLOAD DATA =====
    public ?array $leaseData = [];
    public $lease_property_id = null;
    public $lease_unit_id = null;
    public $lease_tenant_id = null;
    public $selected_lease_id = null;

    public function mount(): void
    {
        $this->bulkForm->fill([
            'zone_id' => auth()->user()?->zone_id,
            'document_year' => date('Y'),
            'quality' => DocumentQuality::GOOD->value,
            'source' => DocumentSource::SCANNED->value,
        ]);

        $this->singleForm->fill([
            'zone_id' => auth()->user()?->zone_id,
            'document_year' => date('Y'),
            'quality' => DocumentQuality::GOOD->value,
            'source' => DocumentSource::SCANNED->value,
        ]);

        $this->leaseForm->fill([
            'document_type' => 'signed_physical_lease',
        ]);
    }

    // ===========================
    // BULK UPLOAD FORM
    // ===========================
    protected function getForms(): array
    {
        return [
            'bulkForm',
            'singleForm',
            'leaseForm',
        ];
    }

    public function bulkForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Batch Settings')
                    ->description('These settings apply to all files in this batch')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('zone_id')
                                    ->label('Zone')
                                    ->relationship('zone', 'name', fn ($query) => $query->orderBy('name'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('property_id', null)),

                                Select::make('property_id')
                                    ->label('Property')
                                    ->options(function (Get $get) {
                                        $zoneId = $get('zone_id');
                                        if (!$zoneId) return [];
                                        return Property::where('zone_id', $zoneId)
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->required(),

                                TextInput::make('document_year')
                                    ->label('Document Year')
                                    ->numeric()
                                    ->minValue(1990)
                                    ->maxValue(date('Y'))
                                    ->required(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Select::make('document_type')
                                    ->label('Document Type')
                                    ->options(LeaseDocument::getDocumentTypes())
                                    ->required(),

                                Select::make('quality')
                                    ->label('Quality Rating')
                                    ->options(DocumentQuality::class)
                                    ->required(),

                                Select::make('source')
                                    ->label('Source')
                                    ->options(DocumentSource::class)
                                    ->required(),
                            ]),
                    ]),

                Section::make('Select Files')
                    ->description('Drag and drop up to 50 files or click to browse')
                    ->icon('heroicon-o-document-arrow-up')
                    ->schema([
                        FileUpload::make('files')
                            ->label('')
                            ->multiple()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/jpeg',
                                'image/png',
                                'image/tiff',
                            ])
                            ->maxSize(25 * 1024)
                            ->maxFiles(50)
                            ->directory('temp-uploads')
                            ->preserveFilenames()
                            ->helperText('PDF, DOC, DOCX, JPG, PNG, TIFF | Max 25MB per file | Up to 50 files')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('bulkData')
            ->model(LeaseDocument::class);
    }

    // ===========================
    // SINGLE UPLOAD FORM
    // ===========================
    public function singleForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Document Information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('zone_id')
                                    ->label('Zone')
                                    ->relationship('zone', 'name', fn ($query) => $query->orderBy('name'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('property_id', null)),

                                Select::make('property_id')
                                    ->label('Property')
                                    ->options(function (Get $get) {
                                        $zoneId = $get('zone_id');
                                        if (!$zoneId) return [];
                                        return Property::where('zone_id', $zoneId)
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('document_type')
                                    ->label('Document Type')
                                    ->options(LeaseDocument::getDocumentTypes())
                                    ->required(),

                                TextInput::make('document_year')
                                    ->label('Document Year')
                                    ->numeric()
                                    ->minValue(1990)
                                    ->maxValue(date('Y'))
                                    ->required(),
                            ]),

                        TextInput::make('title')
                            ->label('Document Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Lease Agreement - John Doe - Unit 5A'),

                        Textarea::make('description')
                            ->label('Description / Notes')
                            ->rows(2)
                            ->placeholder('Optional notes about this document'),
                    ]),

                Section::make('File & Quality')
                    ->icon('heroicon-o-document')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Document File')
                            ->required()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/jpeg',
                                'image/png',
                                'image/tiff',
                            ])
                            ->maxSize(25 * 1024)
                            ->directory('temp-uploads')
                            ->preserveFilenames()
                            ->helperText('PDF, DOC, DOCX, JPG, PNG, TIFF | Max 25MB'),

                        Grid::make(3)
                            ->schema([
                                Select::make('quality')
                                    ->label('Quality Rating')
                                    ->options(DocumentQuality::class)
                                    ->required(),

                                Select::make('source')
                                    ->label('Source')
                                    ->options(DocumentSource::class)
                                    ->required(),

                                DatePicker::make('document_date')
                                    ->label('Date on Document')
                                    ->helperText('Optional'),
                            ]),
                    ]),
            ])
            ->statePath('singleData')
            ->model(LeaseDocument::class);
    }

    // ===========================
    // LEASE-LINKED UPLOAD FORM
    // ===========================
    public function leaseForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Find or Create Lease')
                    ->description('Link document to an existing lease or create a new lease record')
                    ->icon('heroicon-o-magnifying-glass')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('lease_property_id')
                                ->label('Property')
                                ->options(Property::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('lease_unit_id', null);
                                    $set('selected_lease_id', null);
                                    $this->lease_unit_id = null;
                                    $this->selected_lease_id = null;
                                }),

                            Select::make('lease_unit_id')
                                ->label('Unit')
                                ->options(function (Get $get) {
                                    $propertyId = $get('lease_property_id');
                                    if (!$propertyId) return [];
                                    return Unit::where('property_id', $propertyId)
                                        ->orderBy('unit_number')
                                        ->pluck('unit_number', 'id');
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    $this->lease_unit_id = $state;
                                    $this->autoFindLease($set);
                                }),

                            Select::make('lease_tenant_id')
                                ->label('Tenant')
                                ->options(
                                    Tenant::orderBy('full_name')
                                        ->get()
                                        ->mapWithKeys(fn ($t) => [
                                            $t->id => $t->full_name . ' (' . $t->id_number . ')'
                                        ])
                                )
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    $this->lease_tenant_id = $state;
                                    $this->autoFindLease($set);
                                }),
                        ]),

                        Select::make('selected_lease_id')
                            ->label('Linked Lease')
                            ->options(function (Get $get) {
                                $unitId = $get('lease_unit_id');
                                $tenantId = $get('lease_tenant_id');

                                if (!$unitId && !$tenantId) return [];

                                $query = Lease::query();
                                if ($unitId) $query->where('unit_id', $unitId);
                                if ($tenantId) $query->where('tenant_id', $tenantId);

                                return $query->with('tenant')->get()->mapWithKeys(fn ($l) => [
                                    $l->id => $l->reference_number . ' - ' . ($l->tenant?->full_name ?? 'Unknown')
                                ]);
                            })
                            ->searchable()
                            ->helperText('Select existing lease or leave empty to create new')
                            ->live(),

                        // New Lease Fields (visible when no lease selected)
                        Section::make('New Lease Details')
                            ->description('Required when creating a new lease record')
                            ->icon('heroicon-o-plus-circle')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('monthly_rent')
                                        ->label('Monthly Rent')
                                        ->numeric()
                                        ->prefix('Ksh')
                                        ->required(),

                                    TextInput::make('deposit_amount')
                                        ->label('Deposit')
                                        ->numeric()
                                        ->prefix('Ksh'),

                                    DatePicker::make('lease_start_date')
                                        ->label('Lease Start Date')
                                        ->required(),
                                ]),
                            ])
                            ->visible(fn (Get $get) => !$get('selected_lease_id') && ($get('lease_unit_id') || $get('lease_tenant_id')))
                            ->collapsible(),
                    ]),

                Section::make('Document Details')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('document_type')
                                ->label('Document Type')
                                ->options([
                                    'signed_physical_lease' => 'Signed Physical Lease',
                                    'original_signed' => 'Original Signed Agreement',
                                    'amendment' => 'Amendment',
                                    'addendum' => 'Addendum',
                                    'notice' => 'Notice',
                                    'id_copy' => 'Tenant ID Copy',
                                    'deposit_receipt' => 'Deposit Receipt',
                                    'payment_receipt' => 'Payment Receipt',
                                    'other' => 'Other Document',
                                ])
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
                            ->placeholder('e.g., Signed Lease - John Doe - Unit 314E'),

                        Textarea::make('description')
                            ->label('Notes / Archive Location')
                            ->rows(2)
                            ->placeholder('e.g., File cabinet A, folder 23'),

                        FileUpload::make('lease_file')
                            ->label('Scanned Document')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/tiff'])
                            ->maxSize(25 * 1024)
                            ->directory('temp-uploads')
                            ->helperText('PDF or images | Max 25MB | Images auto-compressed'),
                    ]),
            ])
            ->statePath('leaseData');
    }

    protected function autoFindLease(Set $set): void
    {
        $unitId = $this->lease_unit_id;
        $tenantId = $this->lease_tenant_id;

        if (!$unitId && !$tenantId) return;

        $query = Lease::query();
        if ($unitId) $query->where('unit_id', $unitId);
        if ($tenantId) $query->where('tenant_id', $tenantId);

        $lease = $query->first();
        if ($lease) {
            $set('selected_lease_id', $lease->id);
            $this->selected_lease_id = $lease->id;
        }
    }

    // ===========================
    // UPLOAD ACTIONS
    // ===========================
    public function uploadBulk(): void
    {
        $this->bulkForm->validate();
        $data = $this->bulkForm->getState();

        if (empty($data['files'])) {
            Notification::make()->title('No files selected')->warning()->send();
            return;
        }

        $this->bulkSuccessCount = 0;
        $this->bulkFailedCount = 0;
        $this->bulkErrors = [];

        DB::beginTransaction();

        try {
            foreach ($data['files'] as $filePath) {
                try {
                    $fullPath = storage_path('app/public/' . $filePath);

                    if (!file_exists($fullPath)) {
                        $this->bulkErrors[] = "File not found: " . basename($filePath);
                        $this->bulkFailedCount++;
                        continue;
                    }

                    $originalFilename = basename($filePath);
                    $mimeType = mime_content_type($fullPath);
                    $fileSize = filesize($fullPath);
                    $fileHash = hash_file('sha256', $fullPath);

                    $title = pathinfo($originalFilename, PATHINFO_FILENAME);
                    $title = str_replace(['_', '-'], ' ', $title);
                    $title = ucwords($title);

                    $storagePath = 'lease-documents/zone_' . $data['zone_id'] . '/property_' . $data['property_id'] . '/' . $data['document_year'];
                    Storage::disk('local')->makeDirectory($storagePath);

                    $uuid = Str::uuid()->toString();
                    $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
                    $newFilename = $uuid . '_' . Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) . '.' . $extension;
                    $newFilePath = $storagePath . '/' . $newFilename;

                    Storage::disk('local')->move('public/' . $filePath, $newFilePath);

                    $document = LeaseDocument::create([
                        'zone_id' => $data['zone_id'],
                        'property_id' => $data['property_id'],
                        'document_type' => $data['document_type'],
                        'document_year' => $data['document_year'],
                        'quality' => $data['quality'],
                        'source' => $data['source'],
                        'status' => DocumentStatus::PENDING_REVIEW,
                        'title' => $title,
                        'file_path' => $newFilePath,
                        'original_filename' => $originalFilename,
                        'mime_type' => $mimeType,
                        'file_size' => $fileSize,
                        'file_hash' => $fileHash,
                        'uploaded_by' => auth()->id(),
                        'version' => 1,
                    ]);

                    $document->logAudit(
                        DocumentAudit::ACTION_UPLOAD,
                        'Document uploaded via bulk upload: ' . $originalFilename,
                        newValues: ['filename' => $originalFilename, 'file_size' => $fileSize, 'mime_type' => $mimeType]
                    );

                    $this->bulkSuccessCount++;
                } catch (\Exception $e) {
                    $this->bulkErrors[] = basename($filePath) . ': ' . $e->getMessage();
                    $this->bulkFailedCount++;
                }
            }

            DB::commit();

            if ($this->bulkSuccessCount > 0 && $this->bulkFailedCount === 0) {
                Notification::make()->title('Upload Complete')->body("{$this->bulkSuccessCount} document(s) uploaded successfully.")->success()->send();
            } elseif ($this->bulkSuccessCount > 0) {
                Notification::make()->title('Partial Success')->body("{$this->bulkSuccessCount} succeeded, {$this->bulkFailedCount} failed.")->warning()->send();
            } else {
                Notification::make()->title('Upload Failed')->body("All {$this->bulkFailedCount} file(s) failed.")->danger()->send();
            }

            // Reset files only
            $this->bulkForm->fill([
                'zone_id' => $data['zone_id'],
                'property_id' => $data['property_id'],
                'document_year' => $data['document_year'],
                'document_type' => $data['document_type'],
                'quality' => $data['quality'],
                'source' => $data['source'],
                'files' => [],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Upload Failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function uploadSingle(): void
    {
        $this->singleForm->validate();
        $data = $this->singleForm->getState();

        if (empty($data['file'])) {
            Notification::make()->title('No file selected')->warning()->send();
            return;
        }

        DB::beginTransaction();

        try {
            $filePath = $data['file'];
            $fullPath = storage_path('app/public/' . $filePath);

            if (!file_exists($fullPath)) {
                throw new \Exception('Uploaded file not found.');
            }

            $originalFilename = basename($filePath);
            $mimeType = mime_content_type($fullPath);
            $fileSize = filesize($fullPath);
            $fileHash = hash_file('sha256', $fullPath);

            $storagePath = 'lease-documents/zone_' . $data['zone_id'] . '/property_' . $data['property_id'] . '/' . $data['document_year'];
            Storage::disk('local')->makeDirectory($storagePath);

            $uuid = Str::uuid()->toString();
            $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
            $newFilename = $uuid . '_' . Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) . '.' . $extension;
            $newFilePath = $storagePath . '/' . $newFilename;

            Storage::disk('local')->move('public/' . $filePath, $newFilePath);

            $document = LeaseDocument::create([
                'zone_id' => $data['zone_id'],
                'property_id' => $data['property_id'],
                'document_type' => $data['document_type'],
                'document_year' => $data['document_year'],
                'quality' => $data['quality'],
                'source' => $data['source'],
                'status' => DocumentStatus::PENDING_REVIEW,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'document_date' => $data['document_date'] ?? null,
                'file_path' => $newFilePath,
                'original_filename' => $originalFilename,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'file_hash' => $fileHash,
                'uploaded_by' => auth()->id(),
                'version' => 1,
            ]);

            $document->logAudit(
                DocumentAudit::ACTION_UPLOAD,
                'Document uploaded: ' . $data['title'],
                newValues: ['filename' => $originalFilename, 'file_size' => $fileSize]
            );

            DB::commit();

            Notification::make()->title('Document Uploaded')->body('Your document has been submitted for review.')->success()->send();

            $this->singleForm->fill([
                'zone_id' => $data['zone_id'],
                'property_id' => $data['property_id'],
                'document_year' => $data['document_year'],
                'quality' => $data['quality'],
                'source' => $data['source'],
                'title' => '',
                'description' => '',
                'file' => null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Upload Failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function uploadLeaseLinked(): void
    {
        $this->leaseForm->validate();
        $data = $this->leaseForm->getState();

        if (empty($data['lease_file'])) {
            Notification::make()->title('No file selected')->warning()->send();
            return;
        }

        if (!$data['selected_lease_id'] && !$data['lease_unit_id'] && !$data['lease_tenant_id']) {
            Notification::make()->title('Missing Information')->body('Please select a property/unit or tenant.')->danger()->send();
            return;
        }

        DB::beginTransaction();

        try {
            $leaseId = $data['selected_lease_id'];

            // Create new lease if needed
            if (!$leaseId) {
                $unit = $data['lease_unit_id'] ? Unit::find($data['lease_unit_id']) : null;
                $property = $unit?->property ?? ($data['lease_property_id'] ? Property::find($data['lease_property_id']) : null);

                $lease = Lease::create([
                    'reference_number' => 'PHY-' . strtoupper(uniqid()),
                    'source' => 'physical_upload',
                    'lease_type' => $unit?->unit_type ?? 'residential_major',
                    'signing_mode' => 'physical',
                    'workflow_state' => 'active',
                    'tenant_id' => $data['lease_tenant_id'],
                    'unit_id' => $data['lease_unit_id'],
                    'property_id' => $property?->id,
                    'landlord_id' => $property?->landlord_id,
                    'zone_id' => $property?->zone_id,
                    'monthly_rent' => $data['monthly_rent'] ?? 0,
                    'deposit_amount' => $data['deposit_amount'] ?? 0,
                    'start_date' => $data['lease_start_date'],
                    'created_by' => auth()->id(),
                ]);
                $leaseId = $lease->id;
            }

            // Get lease for zone info
            $lease = Lease::with('property.zone')->find($leaseId);

            $filePath = $data['lease_file'];
            $fullPath = storage_path('app/public/' . $filePath);

            if (!file_exists($fullPath)) {
                throw new \Exception('Uploaded file not found.');
            }

            $originalFilename = basename($filePath);
            $mimeType = mime_content_type($fullPath);
            $fileSize = filesize($fullPath);
            $fileHash = hash_file('sha256', $fullPath);

            $storagePath = 'lease-documents/leases/' . $leaseId;
            Storage::disk('local')->makeDirectory($storagePath);

            $uuid = Str::uuid()->toString();
            $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
            $newFilename = $uuid . '.' . $extension;
            $newFilePath = $storagePath . '/' . $newFilename;

            Storage::disk('local')->move('public/' . $filePath, $newFilePath);

            $document = LeaseDocument::create([
                'lease_id' => $leaseId,
                'zone_id' => $lease->property?->zone_id ?? $lease->zone_id,
                'property_id' => $lease->property_id,
                'tenant_id' => $lease->tenant_id,
                'unit_id' => $lease->unit_id,
                'document_type' => $data['document_type'],
                'document_date' => $data['document_date'],
                'document_year' => $data['document_date'] ? date('Y', strtotime($data['document_date'])) : date('Y'),
                'quality' => DocumentQuality::GOOD,
                'source' => DocumentSource::SCANNED,
                'status' => DocumentStatus::LINKED,
                'title' => $data['document_title'],
                'description' => $data['description'] ?? null,
                'file_path' => $newFilePath,
                'original_filename' => $originalFilename,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'file_hash' => $fileHash,
                'uploaded_by' => auth()->id(),
                'linked_by' => auth()->id(),
                'linked_at' => now(),
                'version' => 1,
            ]);

            $document->logAudit(
                DocumentAudit::ACTION_UPLOAD,
                'Physical lease document uploaded and linked to ' . $lease->reference_number,
                newValues: ['lease_id' => $leaseId, 'filename' => $originalFilename]
            );

            DB::commit();

            $message = 'Document uploaded and linked to lease.';
            if (!$data['selected_lease_id']) {
                $message .= ' New lease record created: ' . $lease->reference_number;
            }

            Notification::make()->title('Upload Complete')->body($message)->success()->send();

            // Reset form
            $this->leaseForm->fill(['document_type' => 'signed_physical_lease']);
            $this->lease_property_id = null;
            $this->lease_unit_id = null;
            $this->lease_tenant_id = null;
            $this->selected_lease_id = null;

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Upload Failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getViewData(): array
    {
        $userId = auth()->id();

        return [
            'activeTab' => $this->activeTab,
            'bulkSuccessCount' => $this->bulkSuccessCount,
            'bulkFailedCount' => $this->bulkFailedCount,
            'bulkErrors' => $this->bulkErrors,
            'stats' => [
                'today' => LeaseDocument::where('uploaded_by', $userId)->whereDate('created_at', today())->count(),
                'pending' => LeaseDocument::where('uploaded_by', $userId)->pendingReview()->count(),
                'total' => LeaseDocument::where('uploaded_by', $userId)->count(),
            ],
            'recentUploads' => LeaseDocument::where('uploaded_by', $userId)
                ->with(['zone', 'property', 'lease'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('myUploads')
                ->label('My Uploads')
                ->icon('heroicon-o-folder-open')
                ->url(fn () => LeaseDocumentResource::getUrl('my-uploads'))
                ->color('gray'),

            \Filament\Actions\Action::make('reviewQueue')
                ->label('Review Queue')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(fn () => LeaseDocumentResource::getUrl('review'))
                ->color('warning')
                ->badge(fn () => LeaseDocument::pendingReview()->count() ?: null),
        ];
    }
}
