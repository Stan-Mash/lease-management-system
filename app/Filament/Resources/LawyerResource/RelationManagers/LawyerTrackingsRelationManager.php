<?php

declare(strict_types=1);

namespace App\Filament\Resources\LawyerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LawyerTrackingsRelationManager extends RelationManager
{
    protected static string $relationship = 'lawyerTrackings';

    protected static ?string $title = 'Lease Tracking History';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('sent_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('lease.reference_number')
                    ->label('Lease Reference')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->url(fn ($record) => $record->lease_id
                        ? \App\Filament\Resources\Leases\LeaseResource::getUrl('view', ['record' => $record->lease_id])
                        : null
                    ),

                Tables\Columns\TextColumn::make('lease.tenant.names')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('lease.workflow_state')
                    ->label('Lease Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucwords(str_replace('_', ' ', $state ?? '—')))
                    ->color(fn (?string $state): string => match ($state) {
                        'active'           => 'success',
                        'with_lawyer'      => 'info',
                        'pending_deposit'  => 'warning',
                        'pending_upload'   => 'warning',
                        'cancelled'        => 'danger',
                        'terminated'       => 'danger',
                        default            => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Tracking Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'   => 'Pending',
                        'sent'      => 'With Lawyer',
                        'returned'  => 'Returned',
                        'cancelled' => 'Cancelled',
                        default     => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'sent'      => 'info',
                        'returned'  => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'sent'      => 'heroicon-o-clock',
                        'returned'  => 'heroicon-o-check-circle',
                        'cancelled' => 'heroicon-o-x-circle',
                        default     => 'heroicon-o-ellipsis-horizontal',
                    }),

                Tables\Columns\TextColumn::make('sent_method')
                    ->label('Sent Via')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'email'    => '✉️ Email',
                        'physical' => '🏢 Physical',
                        default    => '—',
                    })
                    ->color('gray'),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent On')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->sent_at?->format('d M Y, H:i')),

                Tables\Columns\TextColumn::make('returned_at')
                    ->label('Returned On')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder('Still with lawyer')
                    ->color(fn ($record) => $record->returned_at ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('turnaround_days')
                    ->label('Turnaround')
                    ->formatStateUsing(fn (?int $days, $record): string => match (true) {
                        $days !== null                 => "{$days} day" . ($days !== 1 ? 's' : ''),
                        $record->status === 'sent'     => self::computeLiveDays($record) . ' days (ongoing)',
                        default                        => '—',
                    })
                    ->color(fn (?int $days, $record): string => match (true) {
                        $days !== null && $days <= config('lease.lawyer.expected_turnaround_days', 7) => 'success',
                        $days !== null                 => 'danger',
                        $record->status === 'sent' && $record->sent_at?->addDays(config('lease.lawyer.expected_turnaround_days', 7))->isPast() => 'danger',
                        default                        => 'gray',
                    })
                    ->tooltip(fn (?int $days, $record): ?string => $days !== null && $days > config('lease.lawyer.expected_turnaround_days', 7)
                        ? "Exceeded expected " . config('lease.lawyer.expected_turnaround_days', 7) . "-day turnaround by " . ($days - config('lease.lawyer.expected_turnaround_days', 7)) . " day(s)"
                        : null
                    ),

                Tables\Columns\TextColumn::make('certification_type')
                    ->label('Advocate Certified')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'review'       => '📋 Review Only',
                        'witness'      => '✍️ Witness',
                        'attestation'  => '🔏 Attestation',
                        'registration' => '🏛️ Registration',
                        default        => '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'review'       => 'gray',
                        'witness'      => 'info',
                        'attestation'  => 'success',
                        'registration' => 'success',
                        default        => 'gray',
                    })
                    ->placeholder('Not yet certified')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('advocate_lsk_number')
                    ->label('LSK No.')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('physical_copy_uploaded')
                    ->label('Physical Copy')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('sentByUser.name')
                    ->label('Sent By')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('receivedByUser.name')
                    ->label('Received By')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'sent'      => 'With Lawyer (Active)',
                        'returned'  => 'Returned',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Only')
                    ->query(fn (Builder $query) => $query
                        ->where('status', 'sent')
                        ->whereNotNull('sent_at')
                        ->where('sent_at', '<', now()->subDays(config('lease.lawyer.expected_turnaround_days', 7)))
                    )
                    ->toggle(),
            ])
            ->emptyStateIcon('heroicon-o-scale')
            ->emptyStateHeading('No leases sent to this lawyer yet')
            ->emptyStateDescription('Leases assigned to this lawyer for review will appear here.');
    }

    private static function computeLiveDays($record): int
    {
        return (int) ($record->sent_at?->diffInDays(now()) ?? 0);
    }
}
