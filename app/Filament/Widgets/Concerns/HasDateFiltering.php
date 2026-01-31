<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait HasDateFiltering
{
    public ?string $dateFilter = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    /**
     * Get date filter options for select dropdown
     */
    public function getDateFilterOptions(): array
    {
        return [
            null => 'All Time',
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_quarter' => 'This Quarter',
            'last_quarter' => 'Last Quarter',
            'this_year' => 'This Year',
            'last_year' => 'Last Year',
            'custom' => 'Custom Range',
        ];
    }

    /**
     * Get the date filter label for display
     */
    public function getDateFilterLabel(): string
    {
        return $this->getDateFilterOptions()[$this->dateFilter] ?? 'All Time';
    }

    /**
     * Check if custom date range is active
     */
    public function isCustomDateRange(): bool
    {
        return $this->dateFilter === 'custom';
    }

    /**
     * Reset date filters
     */
    public function resetDateFilters(): void
    {
        $this->dateFilter = null;
        $this->startDate = null;
        $this->endDate = null;
    }

    /**
     * Set date filter from request/session
     */
    public function setDateFilterFromRequest(): void
    {
        $this->dateFilter = request()->query('dateFilter', session('dashboard.dateFilter'));
        $this->startDate = request()->query('startDate', session('dashboard.startDate'));
        $this->endDate = request()->query('endDate', session('dashboard.endDate'));
    }

    /**
     * Save date filter to session
     */
    public function saveDateFilterToSession(): void
    {
        session([
            'dashboard.dateFilter' => $this->dateFilter,
            'dashboard.startDate' => $this->startDate,
            'dashboard.endDate' => $this->endDate,
        ]);
    }

    /**
     * Apply date filter to a query
     */
    protected function applyDateFilter(Builder $query, string $dateColumn = 'created_at'): Builder
    {
        if (! $this->dateFilter) {
            return $query;
        }

        // Use Carbon::now()->copy() or now() inline for each branch to avoid
        // mutation side-effects from Carbon's mutable sub*/startOf* methods.
        return match ($this->dateFilter) {
            'today' => $query->whereDate($dateColumn, now()->toDateString()),
            'yesterday' => $query->whereDate($dateColumn, now()->subDay()->toDateString()),
            'this_week' => $query->whereBetween($dateColumn, [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ]),
            'last_week' => $query->whereBetween($dateColumn, [
                now()->subWeek()->startOfWeek()->toDateString(),
                now()->copy()->subWeek()->endOfWeek()->toDateString(),
            ]),
            'this_month' => $query->whereYear($dateColumn, now()->year)
                ->whereMonth($dateColumn, now()->month),
            'last_month' => (function () use ($query, $dateColumn) {
                $lastMonth = now()->subMonth();

                return $query->whereYear($dateColumn, $lastMonth->year)
                    ->whereMonth($dateColumn, $lastMonth->month);
            })(),
            'this_quarter' => $query->whereBetween($dateColumn, [
                now()->firstOfQuarter()->toDateString(),
                now()->copy()->lastOfQuarter()->toDateString(),
            ]),
            'last_quarter' => (function () use ($query, $dateColumn) {
                $lastQuarter = now()->subQuarter();

                return $query->whereBetween($dateColumn, [
                    $lastQuarter->copy()->firstOfQuarter()->toDateString(),
                    $lastQuarter->copy()->lastOfQuarter()->toDateString(),
                ]);
            })(),
            'this_year' => $query->whereYear($dateColumn, now()->year),
            'last_year' => $query->whereYear($dateColumn, now()->subYear()->year),
            'custom' => $this->applyCustomDateRange($query, $dateColumn),
            default => $query,
        };
    }

    /**
     * Apply custom date range filter
     */
    protected function applyCustomDateRange(Builder $query, string $dateColumn): Builder
    {
        if ($this->startDate && $this->endDate) {
            return $query->whereBetween($dateColumn, [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if ($this->startDate) {
            return $query->where($dateColumn, '>=', Carbon::parse($this->startDate)->startOfDay());
        }

        if ($this->endDate) {
            return $query->where($dateColumn, '<=', Carbon::parse($this->endDate)->endOfDay());
        }

        return $query;
    }
}
