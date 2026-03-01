<?php

namespace App\Enums;

enum PropertyType: string
{

    // Résidentiel Detailed
    case House = 'house';
    case Apartment = 'apartment';
    case Studio = 'studio';
    case DuplexTriplex = 'duplex_triplex';
    case MultiFamily = 'multi_family';
    case Villa = 'villa';

    // Commercial
    case Office = 'office';
    case Retail = 'retail';
    case Restaurant = 'restaurant';
    case Hotel = 'hotel';

    // Industriel
    case Warehouse = 'warehouse';
    case Factory = 'factory';
    case IndustrialSpace = 'industrial_space';

    // Spécial
    case Land = 'land';
    case Parking = 'parking';

    public function label(): string
    {
        return match ($this) {
            self::House => 'Maison',
            self::Apartment => 'Appartement',
            self::Studio => 'Studio',
            self::DuplexTriplex => 'Duplex/Triplex',
            self::MultiFamily => 'Immeuble',
            self::Villa => 'Villa',
            self::Office => 'Bureau',
            self::Retail => 'Commerce',
            self::Restaurant => 'Restaurant',
            self::Hotel => 'Hôtel',
            self::Warehouse => 'Entrepôt',
            self::Factory => 'Usine',
            self::IndustrialSpace => 'Local d\'activité',
            self::Land => 'Terrain',
            self::Parking => 'Parking',
        };
    }
    public function color(): string
    {
        return match ($this) {
            self::House, self::Apartment, self::Studio, self::DuplexTriplex, self::Villa => 'blue',
            self::MultiFamily => 'indigo',
            self::Office, self::Retail, self::Restaurant, self::Hotel => 'orange',
            self::Warehouse, self::Factory, self::IndustrialSpace => 'zinc',
            self::Land => 'green',
            self::Parking => 'gray',
        };
    }
}
