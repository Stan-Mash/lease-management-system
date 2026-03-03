<?php

declare(strict_types=1);

namespace App\Filament\Resources\Leases\Widgets;

use App\Models\Lease;
use App\Models\LeaseAuditLog;
use Filament\Widgets\Widget;

class LeaseAuditTimelineWidget extends Widget
{
    public ?Lease $record = null;

    protected string $view = 'filament.resources.leases.widgets.lease-audit-timeline-widget';

    protected int|string|array $columnSpan = 'full';

    public function mount(): void
    {
        if ($this->record === null) {
            $id = request()->route('record');
            if ($id) {
                $this->record = Lease::find($id);
            }
        }
    }

    public function getViewData(): array
    {
        $logs = $this->record
            ? LeaseAuditLog::where('lease_id', $this->record->id)
                ->with('user:id,name')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
            : collect();

        return ['logs' => $logs];
    }
}
