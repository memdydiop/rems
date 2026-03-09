<?php

namespace App\Enums;

enum DepositType: string
{
    case Security = 'security';
    case Advance = 'advance';

    public function label(): string
    {
        return match ($this) {
            self::Security => 'Caution',
            self::Advance => 'Avance',
        };
    }
}
