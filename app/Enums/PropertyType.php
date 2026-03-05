<?php

namespace App\Enums;

enum PropertyType: string
{
    // Résidentiel
    case ResidentialBuilding = 'residential_building';
    case Villa = 'villa';
    case House = 'house';
    case Compound = 'compound';

    // Commercial
    case CommercialBuilding = 'commercial_building';
    case ShoppingCenter = 'shopping_center';
    case Hotel = 'hotel';

    // Mixte
    case MixedUse = 'mixed_use';

    // Industriel
    case Warehouse = 'warehouse';
    case Factory = 'factory';
    case IndustrialComplex = 'industrial_complex';

    // Terrain
    case Land = 'land';

    public function label(): string
    {
        return match ($this) {
            self::ResidentialBuilding => 'Immeuble résidentiel',
            self::Villa => 'Villa',
            self::House => 'Maison',
            self::Compound => 'Concession',
            self::CommercialBuilding => 'Immeuble commercial',
            self::ShoppingCenter => 'Centre commercial',
            self::Hotel => 'Hôtel',
            self::MixedUse => 'Immeuble mixte',
            self::Warehouse => 'Entrepôt',
            self::Factory => 'Usine',
            self::IndustrialComplex => 'Complexe industriel',
            self::Land => 'Terrain',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ResidentialBuilding, self::Villa, self::House, self::Compound => 'blue',
            self::CommercialBuilding, self::ShoppingCenter, self::Hotel => 'orange',
            self::MixedUse => 'violet',
            self::Warehouse, self::Factory, self::IndustrialComplex => 'zinc',
            self::Land => 'green',
        };
    }
}
