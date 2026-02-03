<?php

declare(strict_types=1);

namespace App\Filament\Resources\Leases\Actions;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use App\Services\TenantEventService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Filament Action to resolve a disputed lease.
 *
 * This action allows admins/zone managers to:
 * 1. Edit lease terms (rent, dates) in a modal
 * 2. Add resolution notes
 * 3. Transition the lease back to SENT_DIGITAL state for re-signing
 */
class ResolveDisputeAction
{
    /**
     * Create the resolve dispute action.
     */
    public static function make(): Action
    {
        return Action::make('resolveDispute')
            ->label('Resolve Dispute')
            ->icon('heroicon-o-check-badge')
            ->color('warning')
            ->visible(fn (Lease $record): bool => $record->workflow_state === LeaseWorkflowState::DISPUTED->value)
            ->modalHeading('Resolve Lease Dispute')
            ->modalDescription(fn (Lease $record): string => sprintf(
                'Resolve the dispute for lease %s. You can adjust the lease terms below. The lease will be re-sent to the tenant for digital signing.',
                $record->reference_number
            ))
            ->modalWidth('xl')
            ->form(fn (Lease $record): array => [
                Section::make('Dispute Information')
                    ->description('Review the tenant\'s dispute reason before making changes.')
                    ->schema([
                        TextInput::make('dispute_info')
                            ->label('Dispute Notes')
                            ->default(self::extractDisputeInfo($record))
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Lease Terms')
                    ->description('Adjust the lease terms as needed to resolve the dispute.')
                    ->schema([
                        TextInput::make('monthly_rent')
                            ->label('Monthly Rent (KES)')
                            ->numeric()
                            ->prefix('KES')
                            ->required()
                            ->default($record->monthly_rent)
                            ->minValue(0)
                            ->step(100),

                        TextInput::make('deposit_amount')
                            ->label('Deposit Amount (KES)')
                            ->numeric()
                            ->prefix('KES')
                            ->default($record->deposit_amount)
                            ->minValue(0)
                            ->step(100),

                        DatePicker::make('start_date')
                            ->label('Lease Start Date')
                            ->required()
                            ->default($record->start_date)
                            ->native(false),

                        DatePicker::make('end_date')
                            ->label('Lease End Date')
                            ->required()
                            ->default($record->end_date)
                            ->native(false)
                            ->after('start_date'),
                    ])
                    ->columns(2),

                Section::make('Resolution Details')
                    ->schema([
                        Textarea::make('resolution_notes')
                            ->label('Resolution Notes')
                            ->placeholder('Explain what changes were made and why...')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('These notes will be added to the lease record and visible in the audit trail.'),

                        Textarea::make('message_to_tenant')
                            ->label('Message to Tenant (Optional)')
                            ->placeholder('Optional message to include when re-sending the lease...')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('This message will be included in the notification to the tenant.'),
                    ]),
            ])
            ->modalSubmitActionLabel('Resolve & Re-send to Tenant')
            ->action(function (array $data, Lease $record): void {
                try {
                    DB::transaction(function () use ($data, $record) {
                        // Track original values for audit
                        $originalValues = [
                            'monthly_rent' => $record->monthly_rent,
                            'deposit_amount' => $record->deposit_amount,
                            'start_date' => $record->start_date?->format('Y-m-d'),
                            'end_date' => $record->end_date?->format('Y-m-d'),
                        ];

                        // Update lease terms
                        $record->update([
                            'monthly_rent' => $data['monthly_rent'],
                            'deposit_amount' => $data['deposit_amount'],
                            'start_date' => $data['start_date'],
                            'end_date' => $data['end_date'],
                        ]);

                        // Add resolution notes to lease
                        $resolutionNote = sprintf(
                            "\n\n--- DISPUTE RESOLVED [%s] ---\nResolved by: %s\nResolution: %s\nChanges: Rent %s→%s, Deposit %s→%s, Start %s→%s, End %s→%s\n---",
                            now()->format('Y-m-d H:i:s'),
                            Auth::user()?->name ?? 'System',
                            $data['resolution_notes'],
                            number_format((float) $originalValues['monthly_rent'], 2),
                            number_format((float) $data['monthly_rent'], 2),
                            number_format((float) $originalValues['deposit_amount'], 2),
                            number_format((float) $data['deposit_amount'], 2),
                            $originalValues['start_date'] ?? 'N/A',
                            $data['start_date'],
                            $originalValues['end_date'] ?? 'N/A',
                            $data['end_date']
                        );

                        $record->update([
                            'notes' => ($record->notes ?? '') . $resolutionNote,
                        ]);

                        // Log to tenant timeline
                        TenantEventService::logLeaseEvent(
                            tenant: $record->tenant,
                            action: 'Dispute Resolved',
                            lease: $record,
                            details: [
                                'resolution_notes' => $data['resolution_notes'],
                                'changes' => [
                                    'monthly_rent' => [
                                        'from' => $originalValues['monthly_rent'],
                                        'to' => $data['monthly_rent'],
                                    ],
                                    'deposit_amount' => [
                                        'from' => $originalValues['deposit_amount'],
                                        'to' => $data['deposit_amount'],
                                    ],
                                    'start_date' => [
                                        'from' => $originalValues['start_date'],
                                        'to' => $data['start_date'],
                                    ],
                                    'end_date' => [
                                        'from' => $originalValues['end_date'],
                                        'to' => $data['end_date'],
                                    ],
                                ],
                                'resolved_by' => Auth::user()?->name,
                                'resolved_at' => now()->toIso8601String(),
                            ]
                        );

                        // Transition back to SENT_DIGITAL (which will trigger re-sending)
                        $record->transitionTo(LeaseWorkflowState::SENT_DIGITAL);

                        // Re-send digital signing link to tenant
                        if (method_exists($record, 'sendDigitalSigningLink')) {
                            $record->sendDigitalSigningLink($data['message_to_tenant'] ?? null);
                        }

                        Log::info('Lease dispute resolved', [
                            'lease_id' => $record->id,
                            'reference_number' => $record->reference_number,
                            'resolved_by' => Auth::id(),
                            'changes' => [
                                'monthly_rent' => [$originalValues['monthly_rent'], $data['monthly_rent']],
                                'deposit_amount' => [$originalValues['deposit_amount'], $data['deposit_amount']],
                            ],
                        ]);
                    });

                    Notification::make()
                        ->success()
                        ->title('Dispute Resolved')
                        ->body('The lease has been updated and re-sent to the tenant for signing.')
                        ->persistent()
                        ->send();

                } catch (\Exception $e) {
                    Log::error('Failed to resolve lease dispute', [
                        'lease_id' => $record->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    Notification::make()
                        ->danger()
                        ->title('Resolution Failed')
                        ->body('Failed to resolve the dispute: ' . $e->getMessage())
                        ->send();
                }
            });
    }

    /**
     * Extract dispute information from lease notes.
     */
    protected static function extractDisputeInfo(Lease $record): string
    {
        $notes = $record->notes ?? '';

        // Try to extract the dispute section from notes
        if (preg_match('/--- DISPUTE RAISED.*?---/s', $notes, $matches)) {
            return trim($matches[0]);
        }

        return 'No dispute details recorded.';
    }
}
