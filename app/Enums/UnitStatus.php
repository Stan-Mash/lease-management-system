<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum UnitStatus: string implements HasLabel, HasColor
{
    case Vacant = 'VACANT';
    case Occupied = 'OCCUPIED';
    case Maintenance = 'MAINTENANCE';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Vacant => 'Vacant',
            self::Occupied => 'Occupied',
            self::Maintenance => 'Maintenance',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Vacant => 'success',
            self::Occupied => 'danger',
            self::Maintenance => 'warning',
        };
    }
}
