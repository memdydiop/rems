<?php

namespace App\Enums;

enum UnitAmenity: string
{
    case Wifi = 'wifi';
    case AC = 'ac';
    case Balcony = 'balcony';
    case Furnished = 'furnished';
    case Kitchen = 'kitchen';
    case WashingMachine = 'washing_machine';
    case WaterHeater = 'water_heater';
    case PrivateParking = 'private_parking';

    public function label(): string
    {
        return match ($this) {
            self::Wifi => 'Wifi',
            self::AC => 'Climatisation',
            self::Balcony => 'Balcon / Terrasse',
            self::Furnished => 'Meublé',
            self::Kitchen => 'Cuisine Équipée',
            self::WashingMachine => 'Machine à laver',
            self::WaterHeater => 'Chauffe-eau',
            self::PrivateParking => 'Parking Privé',
        };
    }
}
