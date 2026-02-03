<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Enums\TenantEventType;
use App\Models\TenantEvent;
use App\Services\TenantEventService;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\IconEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * EventsRelationManager - Timeline view for Tenant 360 CRM.
 *
 * Displays all tenant events in a chronological activity feed with
 * color-coded event types and quick actions.
 */
class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Activity Timeline';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedClock;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('event_type')
                ->label('Event Type')
                ->options(TenantEventType::options())
                ->required()
                ->native(false)
                ->default(TenantEventType::NOTE->value),

            TextInput::make('title')
                ->label('Title')
                ->required()
                ->maxLength(255)
                ->placeholder('Brief description of the event'),

            Textarea::make('body.content')
                ->label('Details')
                ->rows(4)
                ->placeholder('Full event details...')
                ->columnSpanFull(),

            DateTimePicker::make('happened_at')
                ->label('Event Date/Time')
                ->default(now())
                ->required()
                ->native(false)
                ->seconds(false),

            Toggle::make('is_internal')
                ->label('Internal Note')
                ->helperText('Internal notes are only visible to staff')
                ->default(true),

            Toggle::make('is_pinned')
                ->label('Pin to Top')
                ->helperText('Pinned events appear at the top of the timeline'),

            Toggle::make('requires_follow_up')
                ->label('Requires Follow-up')
                ->reactive(),

            DateTimePicker::make('follow_up_at')
                ->label('Follow-up Date')
                ->native(false)
                ->seconds(false)
                ->visible(fn (callable $get): bool => $get('requires_follow_up') === true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('happened_at', 'desc')
            ->poll('60s') // Auto-refresh every 60 seconds
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ])
            ->columns([
                Stack::make([
                    Split::make([
                        // Event type icon with color
                        IconColumn::make('event_type')
                            ->icon(fn (TenantEvent $record): string => $record->event_type->getIcon())
                            ->color(fn (TenantEvent $record): string => $record->event_type->getColor())
                            ->size(IconSize::Large)
                            ->grow(false),

                        // Main content stack
                        Stack::make([
                            // Title with pinned indicator
                            TextColumn::make('title')
                                ->weight(FontWeight::SemiBold)
                                ->size(TextSize::Medium)
                                ->icon(fn (TenantEvent $record): ?string => $record->is_pinned ? 'heroicon-s-bookmark' : null)
                                ->iconColor('warning')
                                ->iconPosition('after')
                                ->searchable(),

                            // Event type badge and timestamp
                            Split::make([
                                TextColumn::make('event_type')
                                    ->badge()
                                    ->formatStateUsing(fn (TenantEventType $state): string => $state->getLabel())
                                    ->color(fn (TenantEventType $state): string => $state->getColor()),

                                TextColumn::make('happened_at')
                                    ->dateTime('M j, Y g:i A')
                                    ->color('gray')
                                    ->size(TextSize::Small),
                            ])->from('md'),

                            // Body summary
                            TextColumn::make('body_summary')
                                ->label('Details')
                                ->limit(150)
                                ->color('gray')
                                ->size(TextSize::Small)
                                ->wrap(),

                            // Performer info
                            TextColumn::make('performer.name')
                                ->label('By')
                                ->prefix('By: ')
                                ->color('gray')
                                ->size(TextSize::ExtraSmall)
                                ->placeholder('System')
                                ->visible(fn (?TenantEvent $record): bool => $record?->performed_by !== null),
                        ])->space(1),

                        // Status indicators (right side)
                        Stack::make([
                            IconColumn::make('requires_follow_up')
                                ->boolean()
                                ->trueIcon('heroicon-o-bell-alert')
                                ->falseIcon('')
                                ->trueColor('danger')
                                ->label(''),

                            IconColumn::make('is_internal')
                                ->boolean()
                                ->trueIcon('heroicon-o-lock-closed')
                                ->falseIcon('')
                                ->trueColor('gray')
                                ->label(''),
                        ])->grow(false)->alignment('end'),
                    ])->from('md'),
                ])->space(2),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Event Type')
                    ->options(TenantEventType::options())
                    ->multiple()
                    ->preload(),

                TernaryFilter::make('is_pinned')
                    ->label('Pinned')
                    ->placeholder('All')
                    ->trueLabel('Pinned only')
                    ->falseLabel('Not pinned'),

                TernaryFilter::make('requires_follow_up')
                    ->label('Follow-up')
                    ->placeholder('All')
                    ->trueLabel('Needs follow-up')
                    ->falseLabel('No follow-up')
                    ->queries(
                        true: fn (Builder $query) => $query->where('requires_follow_up', true)->whereNull('resolved_at'),
                        false: fn (Builder $query) => $query->where(function ($q) {
                            $q->where('requires_follow_up', false)->orWhereNotNull('resolved_at');
                        }),
                    ),

                TernaryFilter::make('is_internal')
                    ->label('Visibility')
                    ->placeholder('All')
                    ->trueLabel('Internal only')
                    ->falseLabel('Customer-facing'),

                SelectFilter::make('happened_at')
                    ->label('Time Period')
                    ->options([
                        'today' => 'Today',
                        'week' => 'This Week',
                        'month' => 'This Month',
                        'quarter' => 'This Quarter',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'today' => $query->whereDate('happened_at', today()),
                            'week' => $query->where('happened_at', '>=', now()->startOfWeek()),
                            'month' => $query->where('happened_at', '>=', now()->startOfMonth()),
                            'quarter' => $query->where('happened_at', '>=', now()->startOfQuarter()),
                            default => $query,
                        };
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Note')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Add Timeline Event')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['performed_by'] = auth()->id();
                        $data['body'] = ['content' => $data['body']['content'] ?? null];

                        return $data;
                    }),

                Action::make('quick_note')
                    ->label('Quick Note')
                    ->icon('heroicon-o-pencil')
                    ->color('gray')
                    ->form([
                        Textarea::make('note')
                            ->label('Note')
                            ->required()
                            ->rows(3)
                            ->placeholder('Type a quick internal note...'),
                    ])
                    ->action(function (array $data): void {
                        TenantEventService::logNote(
                            tenant: $this->ownerRecord,
                            title: 'Internal Note',
                            content: $data['note'],
                            isInternal: true
                        );
                    }),

                Action::make('log_call')
                    ->label('Log Call')
                    ->icon('heroicon-o-phone')
                    ->color('info')
                    ->form([
                        Select::make('direction')
                            ->label('Call Direction')
                            ->options([
                                'outbound' => 'Outbound (You called tenant)',
                                'inbound' => 'Inbound (Tenant called you)',
                            ])
                            ->default('outbound')
                            ->required(),

                        TextInput::make('duration')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Optional'),

                        Textarea::make('summary')
                            ->label('Call Summary')
                            ->required()
                            ->rows(3)
                            ->placeholder('What was discussed?'),

                        Toggle::make('needs_follow_up')
                            ->label('Requires Follow-up'),

                        DateTimePicker::make('follow_up_at')
                            ->label('Follow-up Date')
                            ->native(false)
                            ->visible(fn (callable $get): bool => $get('needs_follow_up') === true),
                    ])
                    ->action(function (array $data): void {
                        TenantEventService::logCall(
                            tenant: $this->ownerRecord,
                            summary: $data['summary'],
                            direction: $data['direction'],
                            durationSeconds: isset($data['duration']) ? (int) $data['duration'] * 60 : null,
                            followUpAt: $data['follow_up_at'] ?? null
                        );
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading(fn (TenantEvent $record): string => $record->title)
                    ->infolist(fn (Schema $schema): Schema => $this->eventInfolist($schema)),

                Action::make('pin')
                    ->icon(fn (TenantEvent $record): string => $record->is_pinned ? 'heroicon-s-bookmark' : 'heroicon-o-bookmark')
                    ->color(fn (TenantEvent $record): string => $record->is_pinned ? 'warning' : 'gray')
                    ->tooltip(fn (TenantEvent $record): string => $record->is_pinned ? 'Unpin' : 'Pin to top')
                    ->action(fn (TenantEvent $record) => $record->is_pinned ? $record->unpin() : $record->pin()),

                Action::make('resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->tooltip('Mark as Resolved')
                    ->visible(fn (TenantEvent $record): bool => $record->requires_follow_up && ! $record->resolved_at)
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Resolved?')
                    ->modalDescription('This will mark the follow-up as complete.')
                    ->action(fn (TenantEvent $record) => $record->markResolved()),

                DeleteAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No activity yet')
            ->emptyStateDescription('Events will appear here as you interact with this tenant.')
            ->emptyStateIcon('heroicon-o-clock')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['performer'])
                ->orderByDesc('is_pinned')
                ->orderByDesc('happened_at')
            );
    }

    /**
     * Infolist for viewing event details.
     */
    protected function eventInfolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('event_type')
                                ->label('Type')
                                ->badge()
                                ->formatStateUsing(fn (TenantEventType $state): string => $state->getLabel())
                                ->color(fn (TenantEventType $state): string => $state->getColor()),

                            TextEntry::make('happened_at')
                                ->label('When')
                                ->dateTime('F j, Y \a\t g:i A'),
                        ]),

                    TextEntry::make('title')
                        ->label('Title')
                        ->weight(FontWeight::SemiBold),

                    TextEntry::make('body_summary')
                        ->label('Details')
                        ->prose()
                        ->markdown(),

                    Grid::make(3)
                        ->schema([
                            IconEntry::make('is_internal')
                                ->label('Internal')
                                ->boolean(),

                            IconEntry::make('is_pinned')
                                ->label('Pinned')
                                ->boolean(),

                            IconEntry::make('requires_follow_up')
                                ->label('Follow-up Required')
                                ->boolean()
                                ->trueColor('danger'),
                        ]),

                    TextEntry::make('follow_up_at')
                        ->label('Follow-up Date')
                        ->dateTime()
                        ->visible(fn (?Model $record): bool => $record?->requires_follow_up ?? false),

                    TextEntry::make('resolved_at')
                        ->label('Resolved At')
                        ->dateTime()
                        ->visible(fn (?Model $record): bool => $record?->resolved_at !== null),

                    TextEntry::make('performer.name')
                        ->label('Recorded By')
                        ->placeholder('System'),

                    TextEntry::make('external_reference')
                        ->label('External Reference')
                        ->visible(fn (?Model $record): bool => filled($record?->external_reference)),

                    TextEntry::make('channel')
                        ->label('Channel')
                        ->badge()
                        ->visible(fn (?Model $record): bool => filled($record?->channel)),
                ]),
        ]);
    }

    /**
     * Get the badge count showing pending follow-ups.
     */
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->events()
            ->where('requires_follow_up', true)
            ->whereNull('resolved_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Badge color - red if overdue follow-ups exist.
     */
    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        $hasOverdue = $ownerRecord->events()
            ->where('requires_follow_up', true)
            ->whereNull('resolved_at')
            ->where('follow_up_at', '<', now())
            ->exists();

        return $hasOverdue ? 'danger' : 'warning';
    }
}
