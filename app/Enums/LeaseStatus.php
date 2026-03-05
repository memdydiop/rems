<?php

namespace App\Enums;

enum LeaseStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Terminated = 'terminated';
    case Notice = 'notice';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Active => 'Actif',
            self::Notice => 'En préavis',
            self::Expired => 'Expiré',
            self::Terminated => 'Résilié',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Active => 'green',
            self::Notice => 'orange',
            self::Expired => 'red',
            self::Terminated => 'zinc',
        };
    }
}
