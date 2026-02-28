<?php

declare(strict_types=1);

namespace App\Filament\Resources\Leases\Widgets;

use App\Models\Lease;
use Filament\Widgets\Widget;

class LeaseAuditTimelineWidget extends Widget
{
    public ?Lease $record = null;

    protected string $view = 'filament.resources.leases.widgets.lease-audit-timeline-widget';

    protected int|string|array $columnSpan = 'full';

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
        return [
            'leaseId' => $this->record?->id ?? 0,
        ];
    }
}
