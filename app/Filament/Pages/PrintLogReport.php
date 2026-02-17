<?php

namespace App\Filament\Pages;

use App\Models\LeasePrintLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use UnitEnum;

class PrintLogReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-printer';

    protected string $view = 'filament.pages.print-log-report';

    protected static UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'Print Log Report';

    public function mount(): void
    {
        $this->dateFrom = now()->subMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LeasePrintLog::query()
                    ->when($this->dateFrom, fn ($q) => $q->whereDate('printed_at', '>=', $this->dateFrom))
                    ->when($this->dateTo, fn ($q) => $q->whereDate('printed_at', '<=', $this->dateTo))
                    ->with(['lease', 'user'])
                    ->orderByDesc('printed_at'),
            )
            ->columns([
                Tables\Columns\TextColumn::make('printed_at')
                    ->label('Date/Time')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('lease.reference_number')
                    ->label('Lease Reference')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Printed By')
                    ->searchable(),

                Tables\Columns\TextColumn::make('workstation')
                    ->label('Workstation')
                    ->searchable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address'),

                Tables\Columns\TextColumn::make('copies_printed')
                    ->label('Copies')
                    ->badge(),

                Tables\Columns\TextColumn::make('print_reason')
                    ->label('Reason')
                    ->limit(30),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('printed_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->default(now()->subMonth()),
                        Forms\Components\DatePicker::make('until')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('printed_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('printed_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Action::make('view_lease')
                    ->label('View Lease')
                    ->url(fn (LeasePrintLog $record) => route('filament.admin.resources.leases.view', $record->lease_id))
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([])
            ->defaultSort('printed_at', 'desc');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_audit_logs') ?? false;
    }
}
