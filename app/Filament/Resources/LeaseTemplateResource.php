<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaseTemplateResource\Pages;
use App\Models\LeaseTemplate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class LeaseTemplateResource extends Resource
{
    protected static ?string $model = LeaseTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Lease Templates';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('Template Configuration')
                    ->tabs([
                        // Tab 1: Basic Information
                        Tabs\Tab::make('Basic Information')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(100)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(
                                        fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state)),
                                    )
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true)
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('template_type')
                                    ->label('Template Type')
                                    ->options([
                                        'residential_major' => 'Residential Major',
                                        'residential_micro' => 'Residential Micro',
                                        'commercial' => 'Commercial',
                                        'custom' => 'Custom',
                                    ])
                                    ->required()
                                    ->default('residential_major'),

                                Forms\Components\Select::make('source_type')
                                    ->label('Source Type')
                                    ->options([
                                        'uploaded_pdf' => 'Uploaded PDF',
                                        'custom_blade' => 'Custom Blade Template',
                                        'system_default' => 'System Default',
                                    ])
                                    ->required()
                                    ->default('custom_blade'),

                                Forms\Components\Textarea::make('description')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Only active templates can be assigned to leases'),

                                Forms\Components\Toggle::make('is_default')
                                    ->label('Set as Default for This Type')
                                    ->helperText('Only one template can be default per type')
                                    ->default(false),
                            ])->columns(2),

                        // Tab 2: PDF Upload
                        Tabs\Tab::make('PDF Upload')
                            ->schema([
                                Forms\Components\FileUpload::make('source_pdf_path')
                                    ->label('Upload PDF Template')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->directory('templates/source-pdfs')
                                    ->helperText('Upload your existing PDF lease document. The system will extract content and convert it to an editable template.')
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make('upload_info')
                                    ->content('After uploading a PDF, the template content will be automatically populated in the Template Editor tab.')
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn ($get) => $get('source_type') === 'uploaded_pdf'),

                        // Tab 3: Template Editor
                        Tabs\Tab::make('Template Editor')
                            ->schema([
                                Forms\Components\Textarea::make('blade_content')
                                    ->label('Blade Template Code')
                                    ->required()
                                    ->rows(20)
                                    ->columnSpanFull()
                                    ->helperText('Edit the Blade template code. Use {{ $variable }} syntax for dynamic content.')
                                    ->extraAttributes(['style' => 'font-family: monospace; font-size: 12px;']),

                                Forms\Components\Placeholder::make('variables_help')
                                    ->label('Available Variables')
                                    ->content('$lease, $tenant, $property, $unit, $landlord, $today, $qr_code')
                                    ->columnSpanFull(),
                            ]),

                        // Tab 4: Styling & Branding
                        Tabs\Tab::make('Styling & Branding')
                            ->schema([
                                Forms\Components\FileUpload::make('logo_path')
                                    ->label('Logo')
                                    ->image()
                                    ->directory('templates/logos')
                                    ->imageEditor()
                                    ->columnSpanFull(),

                                Forms\Components\ColorPicker::make('branding_config.primary_color')
                                    ->label('Primary Color')
                                    ->default('#1a365d'),

                                Forms\Components\ColorPicker::make('branding_config.secondary_color')
                                    ->label('Secondary Color')
                                    ->default('#FFD700'),

                                Forms\Components\TextInput::make('branding_config.header_text')
                                    ->label('Header Text')
                                    ->maxLength(200),

                                Forms\Components\TextInput::make('branding_config.footer_text')
                                    ->label('Footer Text')
                                    ->maxLength(200),

                                Forms\Components\KeyValue::make('css_styles')
                                    ->label('Additional CSS Styles')
                                    ->keyLabel('Property')
                                    ->valueLabel('Value')
                                    ->columnSpanFull(),
                            ])->columns(2),

                        // Tab 5: Variables
                        Tabs\Tab::make('Variables')
                            ->schema([
                                Forms\Components\TagsInput::make('available_variables')
                                    ->label('Available Variables')
                                    ->helperText('Variables detected in template (auto-populated)')
                                    ->disabled()
                                    ->columnSpanFull(),

                                Forms\Components\TagsInput::make('required_variables')
                                    ->label('Required Variables')
                                    ->helperText('Variables that MUST be present in the template')
                                    ->placeholder('Add required variable names')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('template_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'residential_major',
                        'success' => 'residential_micro',
                        'warning' => 'commercial',
                        'secondary' => 'custom',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'residential_major' => 'Residential Major',
                        'residential_micro' => 'Residential Micro',
                        'commercial' => 'Commercial',
                        default => ucfirst($state),
                    }),

                Tables\Columns\BadgeColumn::make('source_type')
                    ->label('Source')
                    ->colors([
                        'info' => 'uploaded_pdf',
                        'success' => 'custom_blade',
                        'secondary' => 'system_default',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'uploaded_pdf' => 'PDF Upload',
                        'custom_blade' => 'Custom',
                        'system_default' => 'System',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('version_number')
                    ->label('Version')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default'),

                Tables\Columns\TextColumn::make('leases_count')
                    ->counts('leases')
                    ->label('Used By')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('template_type')
                    ->options([
                        'residential_major' => 'Residential Major',
                        'residential_micro' => 'Residential Micro',
                        'commercial' => 'Commercial',
                        'custom' => 'Custom',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default'),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => static::getUrl('view', ['record' => $record])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaseTemplates::route('/'),
            'create' => Pages\CreateLeaseTemplate::route('/create'),
            'view' => Pages\ViewLeaseTemplate::route('/{record}'),
            'edit' => Pages\EditLeaseTemplate::route('/{record}/edit'),
        ];
    }
}
