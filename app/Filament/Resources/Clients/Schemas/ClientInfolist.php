<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ClientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('date_time')->dateTime()->placeholder('-'),
                TextEntry::make('names')->placeholder('-'),
                TextEntry::make('second_name')->placeholder('-'),
                TextEntry::make('last_name')->placeholder('-'),
                TextEntry::make('title')->placeholder('-'),
                TextEntry::make('gender')->placeholder('-'),
                TextEntry::make('address')->placeholder('-'),
                TextEntry::make('address_2')->placeholder('-'),
                TextEntry::make('address_3')->placeholder('-'),
                TextEntry::make('vat_number')->placeholder('-'),
                TextEntry::make('pin_number')->placeholder('-'),
                TextEntry::make('mobile_number')->placeholder('-'),
                TextEntry::make('email_address')->placeholder('-'),
                TextEntry::make('bank_id')->placeholder('-'),
                TextEntry::make('account_name')->placeholder('-'),
                TextEntry::make('account_number')->placeholder('-'),
                TextEntry::make('username')->placeholder('-'),
                TextEntry::make('reference_number')->placeholder('-'),
                TextEntry::make('national_id')->placeholder('-'),
                TextEntry::make('passport_number')->placeholder('-'),
                TextEntry::make('client_type_id')->placeholder('-'),
                TextEntry::make('registered_date')->date()->placeholder('-'),
                TextEntry::make('lease_start_date')->date()->placeholder('-'),
                TextEntry::make('lease_years')->placeholder('-'),
                TextEntry::make('rent_amount')->numeric()->placeholder('-'),
                TextEntry::make('escalation_rate')->placeholder('-'),
                TextEntry::make('frequency')->placeholder('-'),
                TextEntry::make('overdraft_penalty')->numeric()->placeholder('-'),
                TextEntry::make('promas_id')->placeholder('-'),
                TextEntry::make('created_at')->dateTime()->placeholder('-'),
                TextEntry::make('updated_at')->dateTime()->placeholder('-'),
            ]);
    }
}
