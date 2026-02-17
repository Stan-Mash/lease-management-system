<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UnitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('date_time')->dateTime()->placeholder('-'),
                TextEntry::make('client.names')->label('Client')->placeholder('-'),
                TextEntry::make('property.name')->label('Property')->placeholder('-'),
                TextEntry::make('unit_type_id')->placeholder('-'),
                TextEntry::make('usage_type_id')->placeholder('-'),
                TextEntry::make('unit_code')->placeholder('-'),
                TextEntry::make('description')->placeholder('-'),
                TextEntry::make('unit_name')->placeholder('-'),
                TextEntry::make('unit_number')->placeholder('-'),
                TextEntry::make('occupancy_status_id')->placeholder('-'),
                TextEntry::make('unit_condition_id')->placeholder('-'),
                TextEntry::make('category_id')->placeholder('-'),
                TextEntry::make('rent_amount')->numeric()->placeholder('-'),
                IconEntry::make('vat_able')->boolean(),
                TextEntry::make('current_status_id')->placeholder('-'),
                TextEntry::make('initial_water_meter_reading')->numeric()->placeholder('-'),
                TextEntry::make('topology_id')->placeholder('-'),
                TextEntry::make('block_owner_tenant_id')->placeholder('-'),
                TextEntry::make('created_at')->dateTime()->placeholder('-'),
                TextEntry::make('updated_at')->dateTime()->placeholder('-'),
            ]);
    }
}
