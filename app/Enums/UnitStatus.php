<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UnitStatus: string implements HasColor, HasLabel
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
