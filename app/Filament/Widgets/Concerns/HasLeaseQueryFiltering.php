<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\Lease;
use Illuminate\Database\Eloquent\Builder;

trait HasLeaseQueryFiltering
{
    public ?int $zoneId = null;

    public ?int $fieldOfficerId = null;

    protected function getFilteredLeaseQuery(): Builder
    {
        $query = Lease::accessibleByUser(auth()->user());

        // Apply zone filter
        if ($this->zoneId) {
            $query->where('zone_id', $this->zoneId);
        } elseif (auth()->user()->hasZoneRestriction()) {
            $query->where('zone_id', auth()->user()->zone_id);
        }

        // Apply field officer filter
        if ($this->fieldOfficerId) {
            $query->where('assigned_field_officer_id', $this->fieldOfficerId);
        } elseif (auth()->user()->isFieldOfficer()) {
            $query->where('assigned_field_officer_id', auth()->user()->id);
        }

        // Apply date filter if the trait HasDateFiltering is used
        if (method_exists($this, 'applyDateFilter')) {
            $query = $this->applyDateFilter($query);
        }

        return $query;
    }
}
