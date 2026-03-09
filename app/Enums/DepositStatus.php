<?php

namespace App\Enums;

enum DepositStatus: string
{
    case Pending = 'pending';
    case Held = 'held';
    case PartialReturn = 'partial_return';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Held => 'Détenue',
            self::PartialReturn => 'Partiellement remboursée',
            self::Returned => 'Remboursée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Held => 'blue',
            self::PartialReturn => 'orange',
            self::Returned => 'green',
        };
    }
}
