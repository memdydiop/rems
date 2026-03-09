<?php

namespace App\Enums;

enum LeaseType: string
{
    case Habitation = 'habitation';
    case Commercial = 'commercial';
    case Professional = 'professional';
    case Seasonal = 'seasonal';

    public function label(): string
    {
        return match ($this) {
            self::Habitation => 'Bail d\'habitation',
            self::Commercial => 'Bail commercial',
            self::Professional => 'Bail professionnel',
            self::Seasonal => 'Location saisonnière',
        };
    }
}
