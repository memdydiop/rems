<?php

namespace App\Enums;

enum TransactionType: string
{
    case Rental = 'rental';
    case Sale = 'sale';

    public function label(): string
    {
        return match ($this) {
            self::Rental => 'Location',
            self::Sale => 'Vente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Rental => 'blue',
            self::Sale => 'purple',
        };
    }
}
