<?php

namespace App\Filament\Resources\Leases\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Lease Overview')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('reference_number')
                            ->label('Reference Number')
                            ->weight('bold'),

                        TextEntry::make('workflow_state')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match (strtolower($state)) {
                                'draft' => 'gray',
                                'pending_landlord_approval' => 'warning',
                                'approved' => 'info',
                                'active' => 'success',
                                'terminated' => 'danger',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('lease_type')
                            ->label('Type'),
                    ]),
                ]),

            Section::make('Property & Tenant Details')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('tenant.names')->label('Tenant Name'),
                        TextEntry::make('unit.unit_number')->label('Unit Number'),
                        TextEntry::make('property.name')->label('Property'),
                        TextEntry::make('client.names')->label('Client'),
                    ]),
                ]),

            Section::make('Approval Information')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('approvals_status')
                            ->label('Approval Status')
                            ->badge()
                            ->state(function ($record) {
                                if ($record->hasBeenApproved()) {
                                    return 'Approved';
                                }
                                if ($record->hasBeenRejected()) {
                                    return 'Rejected';
                                }
                                if ($record->hasPendingApproval()) {
                                    return 'Pending';
                                }

                                return 'Not Requested';
                            })
                            ->color(function ($record) {
                                if ($record->hasBeenApproved()) {
                                    return 'success';
                                }
                                if ($record->hasBeenRejected()) {
                                    return 'danger';
                                }
                                if ($record->hasPendingApproval()) {
                                    return 'warning';
                                }

                                return 'gray';
                            }),

                        TextEntry::make('latest_approval.reviewer.name')
                            ->label('Reviewed By')
                            ->default('—')
                            ->visible(fn ($record) => $record->getLatestApproval() !== null),

                        TextEntry::make('latest_approval.reviewed_at')
                            ->label('Reviewed On')
                            ->dateTime()
                            ->visible(fn ($record) => $record->getLatestApproval() !== null),
                    ]),

                    TextEntry::make('latest_approval.comments')
                        ->label('Approval Comments')
                        ->default('No comments provided')
                        ->visible(fn ($record) => $record->getLatestApproval()?->comments !== null)
                        ->columnSpanFull(),

                    TextEntry::make('latest_approval.rejection_reason')
                        ->label('Rejection Reason')
                        ->default('—')
                        ->visible(fn ($record) => $record->getLatestApproval()?->rejection_reason !== null)
                        ->color('danger')
                        ->weight('bold')
                        ->columnSpanFull(),

                    TextEntry::make('latest_approval.comments')
                        ->label('Additional Comments')
                        ->default('No additional comments')
                        ->visible(
                            fn ($record) => $record->getLatestApproval()?->isRejected() &&
                            $record->getLatestApproval()?->comments !== null,
                        )
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => $record->client_id !== null)
                ->collapsible(),

            Section::make('Scanned Physical Leases & Documents')
                ->description('Historical signed leases and supporting documents uploaded for retrieval')
                ->schema([
                    RepeatableEntry::make('documents')
                        ->schema([
                            Grid::make(5)->schema([
                                TextEntry::make('title')
                                    ->label('Title')
                                    ->weight('bold'),
                                TextEntry::make('document_type')
                                    ->label('Type')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'signed_physical_lease' => 'Signed Lease',
                                        'original_signed' => 'Original Signed',
                                        'amendment' => 'Amendment',
                                        'addendum' => 'Addendum',
                                        'notice' => 'Notice',
                                        'id_copy' => 'ID Copy',
                                        'deposit_receipt' => 'Deposit Receipt',
                                        'other' => 'Other',
                                        default => $state,
                                    }),
                                TextEntry::make('document_date')
                                    ->label('Doc Date')
                                    ->date('d/m/Y'),
                                TextEntry::make('file_size_for_humans')
                                    ->label('Size'),
                                TextEntry::make('created_at')
                                    ->label('Uploaded')
                                    ->dateTime('d/m/Y'),
                            ]),
                        ])
                        ->contained(false),
                ])
                ->visible(fn ($record) => $record->documents->count() > 0)
                ->collapsible(),
        ]);
    }
}
