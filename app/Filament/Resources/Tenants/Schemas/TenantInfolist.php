<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TenantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('full_name'),
                TextEntry::make('id_number')
                    ->placeholder('-'),
                TextEntry::make('phone_number'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('notification_preference'),
                TextEntry::make('kra_pin')
                    ->placeholder('-'),
                TextEntry::make('occupation')
                    ->placeholder('-'),
                TextEntry::make('employer_name')
                    ->placeholder('-'),
                TextEntry::make('next_of_kin_name')
                    ->placeholder('-'),
                TextEntry::make('next_of_kin_phone')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
