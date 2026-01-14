<?php

namespace App\Filament\Resources\Leases\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;

class LeaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Lease Overview')
                ->schema([
                    Grid::make(3)->schema([
                        Text::make('reference_number')
                            ->label('Reference Number')
                            ->weight('bold'),

                        Text::make('workflow_state')
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

                        Text::make('lease_type')
                            ->label('Type'),
                    ]),
                ]),

            Section::make('Property & Tenant Details')
                ->schema([
                    Grid::make(2)->schema([
                        Text::make('tenant.full_name')->label('Tenant Name'),
                        Text::make('unit.unit_number')->label('Unit Number'),
                        Text::make('property.name')->label('Property'),
                        Text::make('landlord.name')->label('Landlord'),
                    ]),
                ]),

            Section::make('Approval Information')
                ->schema([
                    Grid::make(3)->schema([
                        Text::make('approvals_status')
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

                        Text::make('latest_approval.reviewer.name')
                            ->label('Reviewed By')
                            ->default('—')
                            ->visible(fn ($record) => $record->getLatestApproval() !== null),

                        Text::make('latest_approval.reviewed_at')
                            ->label('Reviewed On')
                            ->dateTime()
                            ->default('—')
                            ->visible(fn ($record) => $record->getLatestApproval() !== null),
                    ]),

                    Text::make('latest_approval.comments')
                        ->label('Approval Comments')
                        ->default('No comments provided')
                        ->visible(fn ($record) => $record->getLatestApproval()?->comments !== null)
                        ->columnSpanFull(),

                    Text::make('latest_approval.rejection_reason')
                        ->label('Rejection Reason')
                        ->default('—')
                        ->visible(fn ($record) => $record->getLatestApproval()?->rejection_reason !== null)
                        ->color('danger')
                        ->weight('bold')
                        ->columnSpanFull(),

                    Text::make('latest_approval.comments')
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
