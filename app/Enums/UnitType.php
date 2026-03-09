<?php

namespace App\Enums;

enum UnitType: string
{
    // Résidentiel
    case Studio = 'studio';
    case Apartment = 'apartment';
    case Villa = 'villa';
    case Room = 'room';
    case EntireHouse = 'entire_house';

    // Commercial
    case Office = 'office';
    case Retail = 'retail';
    case Restaurant = 'restaurant';
    case Storage = 'storage';

    // Autre
    case Parking = 'parking';
    case Garage = 'garage';
    case Land = 'land';

    public function label(): string
    {
        return match ($this) {
            self::Studio => 'Studio',
            self::Apartment => 'Appartement',
            self::Villa => 'Villa',
            self::Room => 'Chambre (Colocation)',
            self::EntireHouse => 'Maison Entière',
            self::Office => 'Bureau',
            self::Retail => 'Commerce',
            self::Restaurant => 'Restaurant',
            self::Storage => 'Stockage',
            self::Parking => 'Parking',
            self::Garage => 'Garage',
            self::Land => 'Terrain',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Studio, self::Apartment, self::Villa, self::Room, self::EntireHouse => 'blue',
            self::Office, self::Retail, self::Restaurant => 'orange',
            self::Storage, self::Parking, self::Garage => 'zinc',
            self::Land => 'green',
        };
    }
}
