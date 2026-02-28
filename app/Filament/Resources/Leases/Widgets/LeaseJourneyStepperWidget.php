<?php

declare(strict_types=1);

namespace App\Filament\Resources\Leases\Widgets;

use App\Models\Lease;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

/**
 * Header widget for ViewLease: renders the Lease Journey stepper (read-only).
 * Receives the current record from the ViewRecord page (injected by Filament or resolved from parent).
 */
class LeaseJourneyStepperWidget extends Widget
{
    protected string $view = 'filament.resources.leases.widgets.lease-journey-stepper-widget';

    protected int|string|array $columnSpan = 'full';

    /** Set by Filament when rendered on a ViewRecord page, or resolved from parent. */
    public ?Lease $record = null;

    public function mount(): void
    {
        if ($this->record === null) {
            $owner = $this->getOwner();
            if ($owner !== null && method_exists($owner, 'getRecord')) {
                $this->record = $owner->getRecord();
            }
        }
    }

    public function getViewData(): array
    {
        $leaseId = $this->record?->id ?? 0;

        return [
            'leaseId' => $leaseId,
        ];
    }
}
