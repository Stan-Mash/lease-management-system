<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaseDocumentResource\Pages;

use App\Enums\DocumentQuality;
use App\Enums\DocumentSource;
use App\Enums\DocumentStatus;
use App\Filament\Resources\LeaseDocumentResource;
use App\Models\LeaseDocument;
use App\Models\Property;
use App\Services\DocumentCompressionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class BulkUploadDocuments extends Page
{
    use WithFileUploads;

    protected static string $resource = LeaseDocumentResource::class;

    protected static string $view = 'filament.resources.lease-document-resource.pages.bulk-upload-documents';

    protected static ?string $title = 'Bulk Document Upload';

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';

    public ?array $data = [];

    public array $uploadedFiles = [];

    public int $successCount = 0;

    public int $failedCount = 0;

    public array $errors = [];

    public bool $isProcessing = false;

    public function mount(): void
    {
        $this->form->fill([
            'zone_id' => auth()->user()?->zone_id,
            'document_year' => date('Y'),
            'quality' => DocumentQuality::GOOD->value,
            'source' => DocumentSource::SCANNED->value,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Upload Settings')
                    ->description('These settings will apply to all uploaded documents')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('zone_id')
                                    ->label('Zone')
                                    ->relationship('zone', 'name', fn ($query) => $query->orderBy('name'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('property_id', null)),

                                Forms\Components\Select::make('property_id')
                                    ->label('Property')
                                    ->options(function (Forms\Get $get) {
                                        $zoneId = $get('zone_id');
                                        if (!$zoneId) {
                                            return [];
                                        }
                                        return Property::where('zone_id', $zoneId)
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->required(),

                                Forms\Components\TextInput::make('document_year')
                                    ->label('Document Year')
                                    ->numeric()
                                    ->minValue(1990)
                                    ->maxValue(date('Y'))
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('document_type')
                                    ->label('Document Type')
                                    ->options(LeaseDocument::getDocumentTypes())
                                    ->required(),

                                Forms\Components\Select::make('quality')
                                    ->label('Default Quality')
                                    ->options(DocumentQuality::class)
                                    ->required(),

                                Forms\Components\Select::make('source')
                                    ->label('Source')
                                    ->options(DocumentSource::class)
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Upload Documents')
                    ->description('Drag and drop multiple files or click to browse. Supported: PDF, DOC, DOCX, JPG, PNG, TIFF')
                    ->schema([
                        Forms\Components\FileUpload::make('files')
                            ->label('Select Files')
                            ->multiple()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/jpeg',
                                'image/png',
                                'image/tiff',
                            ])
                            ->maxSize(25 * 1024) // 25MB per file
                            ->maxFiles(50) // Max 50 files at once
                            ->directory('temp-uploads')
                            ->preserveFilenames()
                            ->helperText('Maximum 50 files at once. Max 25MB per file.')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data')
            ->model(LeaseDocument::class);
    }

    public function upload(): void
    {
        $this->validate();

        $data = $this->form->getState();

        if (empty($data['files'])) {
            Notification::make()
                ->title('No files selected')
                ->warning()
                ->send();
            return;
        }

        $this->isProcessing = true;
        $this->successCount = 0;
        $this->failedCount = 0;
        $this->errors = [];

        $compressionService = app(DocumentCompressionService::class);

        DB::beginTransaction();

        try {
            foreach ($data['files'] as $filePath) {
                try {
                    $fullPath = storage_path('app/public/' . $filePath);

                    if (!file_exists($fullPath)) {
                        $this->errors[] = "File not found: " . basename($filePath);
                        $this->failedCount++;
                        continue;
                    }

                    // Get file info
                    $originalFilename = basename($filePath);
                    $mimeType = mime_content_type($fullPath);
                    $fileSize = filesize($fullPath);
                    $fileHash = hash_file('sha256', $fullPath);

                    // Generate title from filename
                    $title = pathinfo($originalFilename, PATHINFO_FILENAME);
                    $title = str_replace(['_', '-'], ' ', $title);
                    $title = ucwords($title);

                    // Build storage path
                    $storagePath = 'lease-documents';
                    $storagePath .= '/zone_' . $data['zone_id'];
                    $storagePath .= '/property_' . $data['property_id'];
                    $storagePath .= '/' . $data['document_year'];

                    // Ensure directory exists
                    Storage::disk('local')->makeDirectory($storagePath);

                    // Generate unique filename
                    $uuid = Str::uuid()->toString();
                    $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
                    $newFilename = $uuid . '_' . Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) . '.' . $extension;
                    $newFilePath = $storagePath . '/' . $newFilename;

                    // Move file
                    Storage::disk('local')->move('public/' . $filePath, $newFilePath);

                    // Check if compression needed (files > 5MB that aren't already compressed formats)
                    $isCompressed = false;
                    $compressedSize = null;
                    $compressionMethod = null;

                    $alreadyCompressedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
                    if ($fileSize > (5 * 1024 * 1024) && !in_array($mimeType, $alreadyCompressedTypes)) {
                        // Apply compression for large non-compressed files
                        // For now, we'll note it should be compressed
                        // Actual compression handled by DocumentCompressionService
                    }

                    // Create document record
                    LeaseDocument::create([
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
                        'compressed_size' => $compressedSize,
                        'is_compressed' => $isCompressed,
                        'compression_method' => $compressionMethod,
                        'file_hash' => $fileHash,
                        'uploaded_by' => auth()->id(),
                    ]);

                    $this->successCount++;
                } catch (\Exception $e) {
                    $this->errors[] = basename($filePath) . ': ' . $e->getMessage();
                    $this->failedCount++;
                }
            }

            DB::commit();

            // Show result notification
            if ($this->successCount > 0 && $this->failedCount === 0) {
                Notification::make()
                    ->title('Upload complete')
                    ->body("{$this->successCount} document(s) uploaded successfully and submitted for review.")
                    ->success()
                    ->send();
            } elseif ($this->successCount > 0 && $this->failedCount > 0) {
                Notification::make()
                    ->title('Upload partially complete')
                    ->body("{$this->successCount} succeeded, {$this->failedCount} failed.")
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Upload failed')
                    ->body("All {$this->failedCount} file(s) failed to upload.")
                    ->danger()
                    ->send();
            }

            // Reset form
            $this->form->fill([
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

            Notification::make()
                ->title('Upload failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->isProcessing = false;
    }

    public function getViewData(): array
    {
        return [
            'successCount' => $this->successCount,
            'failedCount' => $this->failedCount,
            'errors' => $this->errors,
            'isProcessing' => $this->isProcessing,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back to Documents')
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => static::$resource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
