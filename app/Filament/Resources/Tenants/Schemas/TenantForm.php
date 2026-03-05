<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateTimePicker::make('date_time')
                    ->label('Captured At')
                    ->native(false),

                TextInput::make('names')
                    ->label('First Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('second_name')
                    ->label('Middle Name')
                    ->maxLength(255),

                TextInput::make('last_name')
                    ->label('Last Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('title')
                    ->maxLength(50),

                TextInput::make('gender')
                    ->maxLength(20),

                TextInput::make('mobile_number')
                    ->label('Mobile')
                    ->tel()
                    ->required()
                    ->maxLength(50),

                TextInput::make('email_address')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),

                TextInput::make('address')
                    ->label('Address line 1')
                    ->maxLength(255),

                TextInput::make('address_2')
                    ->label('Address line 2')
                    ->maxLength(255),

                TextInput::make('address_3')
                    ->label('Address line 3')
                    ->maxLength(255),

                TextInput::make('po_box')
                    ->label('PO Box (optional)')
                    ->placeholder('e.g. 1234')
                    ->maxLength(100)
                    ->helperText('Leave blank if not applicable — the lease will leave this space empty.'),

                TextInput::make('national_id')
                    ->label('National ID')
                    ->maxLength(50),

                TextInput::make('passport_number')
                    ->label('Passport Number')
                    ->maxLength(50),

                TextInput::make('pin_number')
                    ->label('KRA PIN')
                    ->maxLength(50),

                TextInput::make('vat_number')
                    ->label('VAT Number')
                    ->maxLength(100),

                TextInput::make('bank_id')
                    ->label('Bank ID')
                    ->numeric(),

                TextInput::make('account_name')
                    ->label('Account Name')
                    ->maxLength(255),

                TextInput::make('account_number')
                    ->label('Account Number')
                    ->maxLength(255),

                DatePicker::make('registered_date')
                    ->label('Registered Date'),

                TextInput::make('reference_number')
                    ->label('Reference Number')
                    ->maxLength(255),

                TextInput::make('nationality_id')
                    ->numeric()
                    ->label('Nationality ID'),

                DatePicker::make('lease_start_date')
                    ->label('Lease Start Date'),

                TextInput::make('lease_years')
                    ->numeric()
                    ->label('Lease Years'),

                TextInput::make('rent_amount')
                    ->numeric()
                    ->label('Rent Amount'),

                TextInput::make('escalation_rate')
                    ->numeric()
                    ->label('Escalation Rate'),

                TextInput::make('frequency')
                    ->label('Payment Frequency')
                    ->maxLength(50),

                FileUpload::make('photo')
                    ->image()
                    ->directory('tenant-photos'),

                Textarea::make('documents')
                    ->label('Documents / Notes'),

                // Internal / legacy fields kept hidden to avoid breaking schema
                TextInput::make('created_by')->numeric()->hidden(),
                TextInput::make('client_type_id')->numeric()->hidden(),
                TextInput::make('current_status_id')->numeric()->hidden(),
                TextInput::make('client_status_id')->numeric()->hidden(),
                TextInput::make('lead_id')->numeric()->hidden(),
                TextInput::make('type_id')->numeric()->hidden(),
                TextInput::make('prefered_messages_language_id')->numeric()->hidden(),
                TextInput::make('property_id')->numeric()->hidden(),
                TextInput::make('sla_id')->numeric()->hidden(),
                TextInput::make('unit_id')->numeric()->hidden(),
                TextInput::make('promas_id')->hidden(),
                Textarea::make('properties')->hidden(),
                TextInput::make('overdraft_penalty')->numeric()->hidden(),
            ]);
    }
}
