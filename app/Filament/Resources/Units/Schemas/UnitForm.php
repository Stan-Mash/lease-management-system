<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateTimePicker::make('date_time'),
                TextInput::make('client_id')->numeric(),
                TextInput::make('property_id')->numeric(),
                TextInput::make('unit_type_id')->numeric(),
                TextInput::make('usage_type_id')->numeric(),
                TextInput::make('unit_code'),
                Textarea::make('description'),
                TextInput::make('created_by_id')->numeric(),
                Textarea::make('unit_uploads'),
                TextInput::make('zone_id')->numeric(),
                TextInput::make('occupancy_status_id')->numeric(),
                TextInput::make('unit_name'),
                TextInput::make('unit_condition_id')->numeric(),
                TextInput::make('category_id')->numeric(),
                TextInput::make('rent_amount')->numeric(),
                Toggle::make('vat_able'),
                TextInput::make('current_status_id')->numeric(),
                TextInput::make('unit_number'),
                TextInput::make('initial_water_meter_reading')->numeric(),
                TextInput::make('topology_id')->numeric(),
                TextInput::make('block_owner_tenant_id')->numeric(),
            ]);
    }
}
