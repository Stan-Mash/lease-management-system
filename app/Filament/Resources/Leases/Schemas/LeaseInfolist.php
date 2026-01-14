<?php

namespace App\Filament\Resources\Leases\Schemas;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;

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
                        TextEntry::make('tenant.full_name')->label('Tenant Name'),
                        TextEntry::make('unit.unit_number')->label('Unit Number'),
                        TextEntry::make('property.name')->label('Property'),
                        TextEntry::make('landlord.name')->label('Landlord'),
                    ]),
                ]),

            Section::make('Approval Information')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('approvals_status')
                            ->label('Approval Status')
                            ->badge()
                            ->state(function ($record) {
                                if ($record->hasBeenApproved()) return 'Approved';
                                if ($record->hasBeenRejected()) return 'Rejected';
                                if ($record->hasPendingApproval()) return 'Pending';
                                return 'Not Requested';
                            })
                            ->color(function ($record) {
                                if ($record->hasBeenApproved()) return 'success';
                                if ($record->hasBeenRejected()) return 'danger';
                                if ($record->hasPendingApproval()) return 'warning';
                                return 'gray';
                            }),

                        TextEntry::make('latest_approval.reviewer.name')
                            ->label('Reviewed By')
                            ->default('—')
                            ->visible(fn ($record) => $record->getLatestApproval() !== null),

                        TextEntry::make('latest_approval.reviewed_at')
                            ->label('Reviewed On')
                            ->dateTime()
                            ->default('—')
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
                        ->visible(fn ($record) =>
                            $record->getLatestApproval()?->isRejected() &&
                            $record->getLatestApproval()?->comments !== null
                        )
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => $record->landlord_id !== null)
                ->collapsible(),
        ]);
    }
}
