<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Models\User;
use App\Services\RoleService;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 100;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationLabel(): string
    {
        return 'Users';
    }

    public static function getModelLabel(): string
    {
        return 'User';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Users';
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date_created')
                    ->label('Date Created')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('employee_number')
                    ->label('Emp No.')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => RoleService::getRoleColor($state))
                    ->formatStateUsing(fn (string $state): string => RoleService::getRoleName($state))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('zone.name')
                    ->label('Zone')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('department')
                    ->label('Department')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('availability_status')
                    ->label('Availability')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'available'))
                    ->color(fn (?string $state): string => match ($state) {
                        'available' => 'success',
                        'on_leave' => 'warning',
                        'away' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('System Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                        'property_manager' => 'Property Manager',
                        'asst_property_manager' => 'Asst. Property Manager',
                        'accountant' => 'Accountant',
                        'auditor' => 'Auditor',
                        'office_administrator' => 'Office Administrator',
                        'office_admin_assistant' => 'Office Admin Assistant',
                        'office_assistant' => 'Office Assistant',
                        'zone_manager' => 'Zone Manager',
                        'senior_field_officer' => 'Senior Field Officer',
                        'field_officer' => 'Field Officer',
                        'internal_auditor' => 'Internal Auditor',
                    ])
                    ->multiple()
                    ->searchable(),

                Filter::make('employee_number')
                    ->label('Employee Number')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('employee_number')
                            ->label('Employee Number')
                            ->placeholder('Search by employee number...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['employee_number'],
                            fn (Builder $q, $value) => $q->where('employee_number', 'ilike', "%{$value}%"),
                        );
                    }),

                SelectFilter::make('zone_id')
                    ->label('Zone')
                    ->relationship('zone', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),

                Filter::make('field_officers_only')
                    ->label('Show Field Officers Only')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereIn('role', ['field_officer', 'senior_field_officer'])),

                Filter::make('zone_managers_only')
                    ->label('Show Zone Managers Only')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->where('role', 'zone_manager')),

                SelectFilter::make('availability_status')
                    ->label('Availability')
                    ->options([
                        'available' => 'Available',
                        'on_leave' => 'On Leave',
                        'away' => 'Away',
                    ]),

                Filter::make('date_created')
                    ->form([
                        DatePicker::make('from')
                            ->label('Date Created From'),
                        DatePicker::make('until')
                            ->label('Date Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('date_created', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('date_created', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'From ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Until ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->filtersFormColumns(3)
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('name', 'asc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
