<?php

namespace App\Enums;

enum UnitStatus: string
{
    case Vacant = 'vacant';
    case Occupied = 'occupied';
    case Sold = 'sold';

    public function label(): string
    {
        return match ($this) {
            self::Vacant => 'Vacant',
            self::Occupied => 'Occupé',
            self::Sold => 'Vendu',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Vacant => 'zinc',
            self::Occupied => 'green',
            self::Sold => 'indigo',
        };
    }
}

