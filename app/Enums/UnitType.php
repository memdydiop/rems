<?php

namespace App\Enums;

enum UnitType: string
{
    // Résidentiel
    case Studio = 'studio';
    case F1 = 'f1';
    case F2 = 'f2';
    case F3 = 'f3';
    case F4 = 'f4';
    case F5Plus = 'f5_plus';
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
            self::F1 => 'Appartement F1 (1 pièce)',
            self::F2 => 'Appartement F2 (2 pièces)',
            self::F3 => 'Appartement F3 (3 pièces)',
            self::F4 => 'Appartement F4 (4 pièces)',
            self::F5Plus => 'Appartement F5+ (5 pièces et plus)',
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
            self::Studio, self::F1, self::F2, self::F3, self::F4, self::F5Plus, self::Room, self::EntireHouse => 'blue',
            self::Office, self::Retail, self::Restaurant => 'orange',
            self::Storage, self::Parking, self::Garage => 'zinc',
            self::Land => 'green',
        };
    }
}
