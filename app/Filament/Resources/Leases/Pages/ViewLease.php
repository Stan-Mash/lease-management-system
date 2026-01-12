<?php

namespace App\Filament\Resources\Leases\Pages;

use App\Filament\Resources\Leases\LeaseResource;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewLease extends ViewRecord
{
    protected static string $resource = LeaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => in_array($this->record->workflow_state, ['draft', 'received'])),

            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->workflow_state === 'draft')
                ->requiresConfirmation()
                ->action(fn () => $this->record->transitionTo('approved')),

            Action::make('sendDigital')
                ->label('Send Digital Link')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () =>
                    $this->record->workflow_state === 'approved' &&
                    $this->record->signing_mode === 'digital'
                )
                ->requiresConfirmation()
                ->action(fn () => $this->record->sendDigitalSigningLink()),

            Action::make('print')
                ->label('Print Lease')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->visible(fn () =>
                    $this->record->workflow_state === 'approved' &&
                    $this->record->signing_mode === 'physical'
                )
                ->action(fn () => $this->record->markAsPrinted()),

            Action::make('generatePdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn () => route('leases.pdf', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
