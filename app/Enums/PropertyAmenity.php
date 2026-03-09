<?php

namespace App\Enums;

enum PropertyAmenity: string
{
    case Security = 'security';
    case Elevator = 'elevator';
    case Pool = 'pool';
    case Gym = 'gym';
    case Generator = 'generator';
    case Parking = 'parking';
    case WaterReserve = 'water_reserve';

    public function label(): string
    {
        return match ($this) {
            self::Security => 'Sécurité / Gardiennage',
            self::Elevator => 'Ascenseur',
            self::Pool => 'Piscine Commune',
            self::Gym => 'Salle de Sport',
            self::Generator => 'Groupe Électrogène',
            self::Parking => 'Parking',
            self::WaterReserve => 'Forage / Réserve d\'eau',
        };
    }
}
