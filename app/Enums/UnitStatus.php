<?php

namespace App\Enums;

enum UnitStatus: string
{
    case Available = 'available';
    case Vacant = 'vacant';
    case Occupied = 'occupied';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Available, self::Vacant => 'Disponible',
            self::Occupied => 'Occupé',
            self::Maintenance => 'Maintenance',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available, self::Vacant => 'zinc',
            self::Occupied => 'green',
            self::Maintenance => 'orange',
        };
    }
}

