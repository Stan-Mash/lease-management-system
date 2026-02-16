<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateTimePicker::make('date_time'),
                TextInput::make('names'),
                TextInput::make('second_name'),
                TextInput::make('last_name'),
                TextInput::make('title'),
                TextInput::make('gender'),
                TextInput::make('address'),
                TextInput::make('address_2'),
                TextInput::make('address_3'),
                TextInput::make('vat_number'),
                TextInput::make('pin_number'),
                TextInput::make('mobile_number')->tel(),
                TextInput::make('email_address')->email(),
                TextInput::make('bank_id')->numeric(),
                TextInput::make('account_name'),
                TextInput::make('account_number'),
                TextInput::make('username'),
                TextInput::make('client_password')->password(),
                TextInput::make('uid'),
                TextInput::make('group_id')->numeric(),
                DatePicker::make('registered_date'),
                TextInput::make('reference_number'),
                TextInput::make('created_by')->numeric(),
                TextInput::make('nationality_id')->numeric(),
                TextInput::make('national_id'),
                TextInput::make('passport_number'),
                TextInput::make('client_type_id')->numeric(),
                FileUpload::make('photo')->image()->directory('client-photos'),
                Textarea::make('documents'),
                TextInput::make('current_status_id')->numeric(),
                TextInput::make('client_status_id')->numeric(),
                TextInput::make('lead_id')->numeric(),
                TextInput::make('type_id')->numeric(),
                TextInput::make('client_id')->numeric(),
                TextInput::make('prefered_messages_language_id')->numeric(),
                TextInput::make('property_id')->numeric(),
                TextInput::make('sla_id')->numeric(),
                TextInput::make('unit_id')->numeric(),
                DatePicker::make('lease_start_date'),
                TextInput::make('lease_years')->numeric(),
                TextInput::make('rent_amount')->numeric(),
                TextInput::make('escalation_rate')->numeric(),
                TextInput::make('frequency'),
                TextInput::make('promas_id'),
                Textarea::make('properties'),
                TextInput::make('overdraft_penalty')->numeric(),
            ]);
    }
}
