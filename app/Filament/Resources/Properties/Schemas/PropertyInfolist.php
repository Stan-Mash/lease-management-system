<?php

namespace App\Filament\Resources\Properties\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PropertyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('date_time')->dateTime()->placeholder('-'),
                TextEntry::make('client.names')->label('Client')->placeholder('-'),
                TextEntry::make('reference_number')->placeholder('-'),
                TextEntry::make('description')->placeholder('-'),
                TextEntry::make('lat_long')->placeholder('-'),
                TextEntry::make('name')->placeholder('-'),
                TextEntry::make('lr_number')->placeholder('-'),
                TextEntry::make('usage_type_id')->placeholder('-'),
                TextEntry::make('current_status_id')->placeholder('-'),
                TextEntry::make('acquisition_date')->date()->placeholder('-'),
                TextEntry::make('bank_account_id')->placeholder('-'),
                TextEntry::make('fieldOfficer.name')->label('Field Officer')->placeholder('-'),
                TextEntry::make('zoneSupervisor.name')->label('Zone Supervisor')->placeholder('-'),
                TextEntry::make('zoneManager.name')->label('Zone Manager')->placeholder('-'),
                TextEntry::make('parent_property_id')->placeholder('-'),
                TextEntry::make('created_at')->dateTime()->placeholder('-'),
                TextEntry::make('updated_at')->dateTime()->placeholder('-'),
            ]);
    }
}
