<?php

namespace App\Filament\Widgets;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class DateRangeFilterWidget extends Widget implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament.widgets.date-range-filter-widget';

    protected int | string | array $columnSpan = 'full';

    public ?string $dateFilter = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount(): void
    {
        $this->schema->fill([
            'dateFilter' => session('dashboard.dateFilter'),
            'startDate' => session('dashboard.startDate'),
            'endDate' => session('dashboard.endDate'),
        ]);
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('dateFilter')
                                    ->label('Date Range')
                                    ->options([
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
                                    ])
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state !== 'custom') {
                                            $set('startDate', null);
                                            $set('endDate', null);
                                        }
                                    }),

                                DatePicker::make('startDate')
                                    ->label('Start Date')
                                    ->visible(fn (callable $get) => $get('dateFilter') === 'custom')
                                    ->maxDate(fn (callable $get) => $get('endDate') ?: now()),

                                DatePicker::make('endDate')
                                    ->label('End Date')
                                    ->visible(fn (callable $get) => $get('dateFilter') === 'custom')
                                    ->minDate(fn (callable $get) => $get('startDate'))
                                    ->maxDate(now()),
                            ]),
                    ])
                    ->compact()
                    ->collapsible()
                    ->persistCollapsed(),
            ])
            ->statePath('data');
    }

    public function applyFilters(): void
    {
        $data = $this->schema->getState();

        session([
            'dashboard.dateFilter' => $data['dateFilter'] ?? null,
            'dashboard.startDate' => $data['startDate'] ?? null,
            'dashboard.endDate' => $data['endDate'] ?? null,
        ]);

        $this->dispatch('dateFilterUpdated', [
            'dateFilter' => $data['dateFilter'] ?? null,
            'startDate' => $data['startDate'] ?? null,
            'endDate' => $data['endDate'] ?? null,
        ]);

        $this->dispatch('$refresh');
    }

    public function resetFilters(): void
    {
        session()->forget([
            'dashboard.dateFilter',
            'dashboard.startDate',
            'dashboard.endDate',
        ]);

        $this->schema->fill([
            'dateFilter' => null,
            'startDate' => null,
            'endDate' => null,
        ]);

        $this->dispatch('dateFilterUpdated', [
            'dateFilter' => null,
            'startDate' => null,
            'endDate' => null,
        ]);

        $this->dispatch('$refresh');
    }
}
