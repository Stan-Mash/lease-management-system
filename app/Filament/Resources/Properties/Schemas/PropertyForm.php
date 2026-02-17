<?php

namespace App\Filament\Resources\Properties\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PropertyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateTimePicker::make('date_time'),
                TextInput::make('client_id')->numeric(),
                TextInput::make('reference_number'),
                Textarea::make('description'),
                TextInput::make('lat_long'),
                Textarea::make('photos_and_documents'),
                TextInput::make('zone_id')->numeric(),
                TextInput::make('zone_area_id')->numeric(),
                TextInput::make('name'),
                TextInput::make('lr_number'),
                TextInput::make('usage_type_id')->numeric(),
                TextInput::make('current_status_id')->numeric(),
                DatePicker::make('acquisition_date'),
                TextInput::make('created_by')->numeric(),
                TextInput::make('bank_account_id')->numeric(),
                TextInput::make('field_officer_id')->numeric(),
                TextInput::make('zone_supervisor_id')->numeric(),
                TextInput::make('zone_manager_id')->numeric(),
                TextInput::make('parent_property_id')->numeric(),
            ]);
    }
}
