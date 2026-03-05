<?php

namespace App\Enums;

enum MaintenanceCategory: string
{
    case Unit = 'unit';
    case Property = 'property';

    public function label(): string
    {
        return match ($this) {
            self::Unit => 'Unité (Privatif)',
            self::Property => 'Propriété (Commun)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unit => 'blue',
            self::Property => 'indigo',
        };
    }
}
