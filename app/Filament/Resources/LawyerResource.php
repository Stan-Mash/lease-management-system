<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LawyerResource\Pages;
use App\Filament\Resources\LawyerResource\RelationManagers\LawyerTrackingsRelationManager;
use App\Models\Lawyer;
use App\Models\LeaseLawyerTracking;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class LawyerResource extends Resource
{
    protected static ?string $model = Lawyer::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Lawyers';

    protected static ?string $recordTitleAttribute = 'name';

    // =========================================================================
    // FORM
    // =========================================================================

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make('Lawyer Profile')
                ->description('Basic identity and contact information for this legal professional.')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. James Kariuki'),

                    Forms\Components\TextInput::make('firm')
                        ->label('Law Firm')
                        ->maxLength(255)
                        ->placeholder('e.g. Kariuki & Associates Advocates'),

                    Forms\Components\TextInput::make('lsk_number')
                        ->label('LSK No.')
                        ->maxLength(50)
                        ->placeholder('e.g. 4521/2018')
                        ->helperText('Law Society of Kenya practicing certificate number. Appears on signed lease documents.'),

                    Forms\Components\TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->maxLength(255)
                        ->placeholder('lawyer@lawfirm.co.ke'),

                    Forms\Components\TextInput::make('phone')
                        ->label('Phone Number')
                        ->tel()
                        ->maxLength(20)
                        ->placeholder('+254 722 000 000'),

                    Forms\Components\TextInput::make('specialization')
                        ->label('Area of Specialization')
                        ->maxLength(255)
                        ->placeholder('e.g. Property Law, Commercial Leases, Contract Law')
                        ->helperText('Helps staff choose the right lawyer for each lease type.'),

                    Forms\Components\Textarea::make('address')
                        ->label('Office Address')
                        ->rows(2)
                        ->maxLength(500)
                        ->placeholder('e.g. 5th Floor, Anniversary Towers, Nairobi'),
                ])
                ->columns(2),

            Section::make('Status & Notes')
                ->description('Control whether this lawyer appears as an option when sending leases.')
                ->icon('heroicon-o-adjustments-horizontal')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active — available for lease assignments')
                        ->default(true)
                        ->inline(false)
                        ->helperText('Inactive lawyers will not appear in the "Send to Lawyer" dropdown on lease pages.'),

                    Forms\Components\Textarea::make('notes')
                        ->label('Internal Notes')
                        ->rows(3)
                        ->maxLength(1000)
                        ->placeholder('e.g. Preferred lawyer for commercial leases, typically fast turnaround...'),
                ])
                ->columns(1),
        ]);
    }

    // =========================================================================
    // INFOLIST (View page)
    // =========================================================================

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([

            // ── Profile & performance summary ──────────────────────────────────
            Section::make()
                ->schema([
                    Grid::make(1)->schema([
                        TextEntry::make('_profile_header')
                            ->label('')
                            ->state(fn ($record) => self::buildProfileHeader($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),
                ])
                ->extraAttributes([
                    'style' => 'background: linear-gradient(135deg, #f0f4ff 0%, #e8eeff 100%); '
                        . 'border: 1.5px solid rgba(99,102,241,0.3); border-left: 5px solid #6366f1; border-radius: 12px;',
                ]),

            // ── Contact & Identity ─────────────────────────────────────────────
            Section::make('Contact Information')
                ->icon('heroicon-o-phone')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('name')
                            ->label('Full Name')
                            ->weight('bold'),

                        TextEntry::make('firm')
                            ->label('Law Firm')
                            ->placeholder('—'),

                        TextEntry::make('lsk_number')
                            ->label('LSK No.')
                            ->placeholder('—'),

                        TextEntry::make('specialization')
                            ->label('Specialization')
                            ->badge()
                            ->color('indigo')
                            ->placeholder('—'),
                    ]),

                    Grid::make(3)->schema([
                        TextEntry::make('email')
                            ->label('Email')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('phone')
                            ->label('Phone')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                    ]),

                    Grid::make(1)->schema([
                        TextEntry::make('address')
                            ->label('Office Address')
                            ->placeholder('—'),
                    ]),
                ]),

            // ── Internal notes ─────────────────────────────────────────────────
            Section::make('Internal Notes')
                ->icon('heroicon-o-document-text')
                ->schema([
                    TextEntry::make('notes')
                        ->label('')
                        ->placeholder('No notes recorded for this lawyer.')
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(fn ($record) => empty($record->notes)),
        ]);
    }

    // =========================================================================
    // TABLE
    // =========================================================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record) => $record->specialization ?? ''),

                Tables\Columns\TextColumn::make('firm')
                    ->label('Law Firm')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->copyable()
                    ->placeholder('—'),

                // Live count of leases currently with this lawyer
                Tables\Columns\TextColumn::make('pending_leases_count')
                    ->label('Active Leases')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state > 3 => 'danger',
                        $state > 0 => 'warning',
                        default    => 'gray',
                    })
                    ->tooltip('Number of leases currently with this lawyer (status = sent/pending)'),

                // Overdue count — leases that have exceeded expected turnaround
                Tables\Columns\TextColumn::make('overdue_count')
                    ->label('Overdue')
                    ->state(fn ($record) => $record->lawyerTrackings()
                        ->where('status', 'sent')
                        ->whereNotNull('sent_at')
                        ->where('sent_at', '<', now()->subDays(config('lease.lawyer.expected_turnaround_days', 7)))
                        ->count()
                    )
                    ->badge()
                    ->color(fn ($state): string => $state > 0 ? 'danger' : 'gray')
                    ->tooltip('Leases that have exceeded the ' . config('lease.lawyer.expected_turnaround_days', 7) . '-day turnaround target'),

                Tables\Columns\TextColumn::make('average_turnaround_days')
                    ->label('Avg. Turnaround')
                    ->formatStateUsing(fn ($state): string => $state !== null ? "{$state} days" : 'N/A')
                    ->color(fn ($state): string => match (true) {
                        $state === null => 'gray',
                        $state <= config('lease.lawyer.expected_turnaround_days', 7) => 'success',
                        default => 'warning',
                    })
                    ->tooltip('Average days from lease sent to lease returned'),

                Tables\Columns\TextColumn::make('total_leases_count')
                    ->label('Total Handled')
                    ->state(fn ($record) => $record->lawyerTrackings()->count())
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only')
                    ->default(true),

                Tables\Filters\SelectFilter::make('firm')
                    ->label('Law Firm')
                    ->options(fn () => Lawyer::query()
                        ->whereNotNull('firm')
                        ->distinct()
                        ->orderBy('firm')
                        ->pluck('firm', 'firm')
                    ),

                Tables\Filters\Filter::make('has_active_leases')
                    ->label('Has Active Leases')
                    ->query(fn ($query) => $query->whereHas('lawyerTrackings', fn ($q) => $q->whereIn('status', ['pending', 'sent'])))
                    ->toggle(),

                Tables\Filters\Filter::make('has_overdue')
                    ->label('Has Overdue Leases')
                    ->query(fn ($query) => $query->whereHas('lawyerTrackings', fn ($q) => $q
                        ->where('status', 'sent')
                        ->whereNotNull('sent_at')
                        ->where('sent_at', '<', now()->subDays(config('lease.lawyer.expected_turnaround_days', 7)))
                    ))
                    ->toggle(),
            ])
            ->defaultSort('is_active', 'desc')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-scale')
            ->emptyStateHeading('No lawyers on record')
            ->emptyStateDescription('Add your first lawyer to start assigning leases for legal review.')
            ->emptyStateActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    // =========================================================================
    // RELATIONS
    // =========================================================================

    public static function getRelations(): array
    {
        return [
            LawyerTrackingsRelationManager::class,
        ];
    }

    // =========================================================================
    // PAGES
    // =========================================================================

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLawyers::route('/'),
            'create' => Pages\CreateLawyer::route('/create'),
            'view'   => Pages\ViewLawyer::route('/{record}'),
            'edit'   => Pages\EditLawyer::route('/{record}/edit'),
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private static function buildProfileHeader(Lawyer $lawyer): string
    {
        $pending  = $lawyer->pending_leases_count;
        $avgDays  = $lawyer->average_turnaround_days;
        $total    = $lawyer->lawyerTrackings()->count();
        $returned = $lawyer->lawyerTrackings()->where('status', 'returned')->count();
        $expected = config('lease.lawyer.expected_turnaround_days', 7);

        $overdue = $lawyer->lawyerTrackings()
            ->where('status', 'sent')
            ->whereNotNull('sent_at')
            ->where('sent_at', '<', now()->subDays($expected))
            ->count();

        $status = $lawyer->is_active
            ? '<span style="background:#d1fae5;color:#065f46;padding:2px 10px;border-radius:999px;font-size:9pt;font-weight:700;">● ACTIVE</span>'
            : '<span style="background:#fee2e2;color:#991b1b;padding:2px 10px;border-radius:999px;font-size:9pt;font-weight:700;">● INACTIVE</span>';

        $pendingBadge = $pending > 0
            ? "<span style=\"background:#fef3c7;color:#92400e;padding:2px 10px;border-radius:999px;font-size:9pt;font-weight:700;\">{$pending} Active</span>"
            : "<span style=\"background:#f3f4f6;color:#6b7280;padding:2px 10px;border-radius:999px;font-size:9pt;\">None active</span>";

        $overdueBadge = $overdue > 0
            ? "<span style=\"background:#fee2e2;color:#991b1b;padding:2px 10px;border-radius:999px;font-size:9pt;font-weight:700;\">⚠️ {$overdue} Overdue</span>"
            : '';

        $avgText = $avgDays !== null
            ? ($avgDays <= $expected
                ? "<span style=\"color:#059669;font-weight:700;\">{$avgDays} days</span> <span style=\"color:#6b7280;font-size:9pt;\">(within target)</span>"
                : "<span style=\"color:#dc2626;font-weight:700;\">{$avgDays} days</span> <span style=\"color:#6b7280;font-size:9pt;\">(exceeds {$expected}-day target)</span>"
            )
            : '<span style="color:#9ca3af;">No completed leases yet</span>';

        $specialization = $lawyer->specialization
            ? "<span style=\"color:#6366f1;font-size:10pt;\"> · {$lawyer->specialization}</span>"
            : '';

        return <<<HTML
        <div style="padding: 4px 0 8px 0;">
            <div style="display: flex; align-items: flex-start; gap: 14px; margin-bottom: 14px;">
                <div style="width: 52px; height: 52px; background: linear-gradient(135deg, #6366f1, #8b5cf6);
                    border-radius: 12px; display: flex; align-items: center; justify-content: center;
                    font-size: 22pt; flex-shrink: 0;">⚖️</div>
                <div>
                    <div style="font-size: 15pt; font-weight: 800; color: #1a365d; line-height: 1.2;">
                        {$lawyer->name}
                    </div>
                    <div style="font-size: 10.5pt; color: #4b5563; margin-top: 2px;">
                        {$lawyer->firm}{$specialization}
                    </div>
                    <div style="margin-top: 6px; display: flex; gap: 8px; flex-wrap: wrap;">
                        {$status}
                        {$pendingBadge}
                        {$overdueBadge}
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                <div style="background: white; border: 1px solid #e0e7ff; border-radius: 8px; padding: 10px 14px; text-align: center;">
                    <div style="font-size: 20pt; font-weight: 800; color: #6366f1;">{$total}</div>
                    <div style="font-size: 9pt; color: #6b7280; text-transform: uppercase; letter-spacing: 0.08em;">Total Leases</div>
                </div>
                <div style="background: white; border: 1px solid #e0e7ff; border-radius: 8px; padding: 10px 14px; text-align: center;">
                    <div style="font-size: 20pt; font-weight: 800; color: #059669;">{$returned}</div>
                    <div style="font-size: 9pt; color: #6b7280; text-transform: uppercase; letter-spacing: 0.08em;">Completed</div>
                </div>
                <div style="background: white; border: 1px solid #e0e7ff; border-radius: 8px; padding: 10px 14px; text-align: center;">
                    <div style="font-size: 12pt; font-weight: 700; color: #1a365d; margin-top: 4px;">{$avgText}</div>
                    <div style="font-size: 9pt; color: #6b7280; text-transform: uppercase; letter-spacing: 0.08em;">Avg. Turnaround</div>
                </div>
            </div>
        </div>
        HTML;
    }
}
