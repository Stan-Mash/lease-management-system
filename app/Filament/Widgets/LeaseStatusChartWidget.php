<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDateFiltering;
use App\Models\Lease;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class LeaseStatusChartWidget extends ChartWidget
{
    use HasDateFiltering;

    protected static ?int $sort = 2;

    public ?string $heading = 'Lease Status Distribution';
    public ?string $maxHeight = '300px';

    public ?int $zoneId = null;
    public ?int $fieldOfficerId = null;

    protected ?string $pollingInterval = null;

    public function mount(): void
    {
        $this->setDateFilterFromRequest();
    }

    #[On('dateFilterUpdated')]
    public function updateDateFilter($dateFilter = null, $startDate = null, $endDate = null): void
    {
        $this->dateFilter = $dateFilter;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    protected function getData(): array
    {
        // Get base query with filters
        $query = $this->getFilteredQuery();

        // Get count by workflow state
        $statusCounts = (clone $query)
            ->select('workflow_state', DB::raw('count(*) as count'))
            ->groupBy('workflow_state')
            ->get()
            ->pluck('count', 'workflow_state');

        // Define colors for each status
        $colors = [
            'draft' => '#94a3b8',
            'approved' => '#3b82f6',
            'active' => '#10b981',
            'pending_tenant_signature' => '#f59e0b',
            'pending_deposit' => '#f59e0b',
            'expired' => '#ef4444',
            'terminated' => '#dc2626',
            'cancelled' => '#64748b',
            'archived' => '#475569',
        ];

        $labels = [];
        $data = [];
        $backgroundColor = [];

        foreach ($statusCounts as $status => $count) {
            $labels[] = ucwords(str_replace('_', ' ', $status));
            $data[] = $count;
            $backgroundColor[] = $colors[$status] ?? '#6b7280';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Leases',
                    'data' => $data,
                    'backgroundColor' => $backgroundColor,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'onClick' => 'function(event, array) {
                if (array.length > 0) {
                    const index = array[0].index;
                    const label = this.data.labels[index];
                    const workflowState = label.toLowerCase().replace(/ /g, "_");
                    window.location.href = "' . route('filament.admin.resources.leases.index') . '?tableFilters[workflow_state][values][0]=" + workflowState;
                }
            }',
        ];
    }

    protected function getFilteredQuery()
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

        // Apply date filter
        $query = $this->applyDateFilter($query);

        return $query;
    }
}
