<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\LeaseAuditLog;
use Livewire\Component;

/**
 * Vertical timeline of lease audit log entries for ViewLease.
 */
class LeaseAuditTimeline extends Component
{
    public int $leaseId;

    public int $limit = 50;

    public function mount(int $leaseId, int $limit = 50): void
    {
        $this->leaseId = $leaseId;
        $this->limit = $limit;
    }

    public function getLogsProperty()
    {
        return LeaseAuditLog::where('lease_id', $this->leaseId)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit($this->limit)
            ->get();
    }

    public function render()
    {
        return view('livewire.lease-audit-timeline');
    }
}
