<?php

declare(strict_types=1);

namespace App\Filament\Resources\Leases\Actions;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use App\Services\TenantEventService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Filament Action to cancel a disputed lease.
 *
 * Use this when a dispute cannot be resolved and the lease
 * should be cancelled entirely.
 */
class CancelDisputedLeaseAction
{
    /**
     * Create the cancel disputed lease action.
     */
    public static function make(): Action
    {
        return Action::make('cancelDisputedLease')
            ->label('Cancel Lease')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn (Lease $record): bool => $record->workflow_state === LeaseWorkflowState::DISPUTED->value)
            ->requiresConfirmation()
            ->modalHeading('Cancel Disputed Lease')
            ->modalDescription('This will permanently cancel the lease. The tenant will need to start a new lease application if they wish to proceed.')
            ->form([
                Textarea::make('cancellation_reason')
                    ->label('Reason for Cancellation')
                    ->placeholder('Explain why the lease is being cancelled...')
                    ->required()
                    ->rows(3)
                    ->maxLength(1000),
            ])
            ->modalSubmitActionLabel('Cancel Lease')
            ->action(function (array $data, Lease $record): void {
                try {
                    DB::transaction(function () use ($data, $record) {
                        // Add cancellation note to lease
                        $cancellationNote = sprintf(
                            "\n\n--- DISPUTED LEASE CANCELLED [%s] ---\nCancelled by: %s\nReason: %s\n---",
                            now()->format('Y-m-d H:i:s'),
                            Auth::user()?->name ?? 'System',
                            $data['cancellation_reason']
                        );

                        $record->update([
                            'notes' => ($record->notes ?? '') . $cancellationNote,
                        ]);

                        // Log to tenant timeline
                        TenantEventService::logLeaseEvent(
                            tenant: $record->tenant,
                            action: 'Cancelled (Dispute Unresolved)',
                            lease: $record,
                            details: [
                                'cancellation_reason' => $data['cancellation_reason'],
                                'cancelled_by' => Auth::user()?->name,
                                'cancelled_at' => now()->toIso8601String(),
                                'previous_state' => 'disputed',
                            ]
                        );

                        // Transition to CANCELLED state
                        $record->transitionTo(LeaseWorkflowState::CANCELLED);

                        Log::info('Disputed lease cancelled', [
                            'lease_id' => $record->id,
                            'reference_number' => $record->reference_number,
                            'cancelled_by' => Auth::id(),
                            'reason' => $data['cancellation_reason'],
                        ]);
                    });

                    Notification::make()
                        ->warning()
                        ->title('Lease Cancelled')
                        ->body('The disputed lease has been cancelled.')
                        ->send();

                } catch (\Exception $e) {
                    Log::error('Failed to cancel disputed lease', [
                        'lease_id' => $record->id,
                        'error' => $e->getMessage(),
                    ]);

                    Notification::make()
                        ->danger()
                        ->title('Cancellation Failed')
                        ->body('Failed to cancel the lease: ' . $e->getMessage())
                        ->send();
                }
            });
    }
}
