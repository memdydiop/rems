<?php

namespace App\Enums;

enum PropertyStatus: string
{
    case Active = 'active';
    case Maintenance = 'maintenance';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Opérationnel',
            self::Maintenance => 'Maintenance',
            self::Archived => 'Archivé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Maintenance => 'orange',
            self::Archived => 'zinc',
        };
    }
}
