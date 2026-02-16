<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->weight('bold'),
            TextEntry::make('username')->placeholder('-'),
            TextEntry::make('email')->copyable(),
            IconEntry::make('block')->label('Blocked')->boolean(),
            IconEntry::make('sendEmail')->label('Send Email')->boolean(),
            TextEntry::make('registerDate')->label('Register Date')->dateTime()->placeholder('-'),
            TextEntry::make('lastvisitDate')->label('Last Visit')->dateTime()->placeholder('-'),
            TextEntry::make('activation')->placeholder('-'),
            TextEntry::make('lastResetTime')->label('Last Reset Time')->dateTime()->placeholder('-'),
            TextEntry::make('resetCount')->label('Reset Count')->placeholder('-'),
            IconEntry::make('requireReset')->label('Require Reset')->boolean(),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }
}
