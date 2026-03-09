<?php

namespace App\Enums;

enum ClientStatus: string
{
    case Active = 'active';
    case Lead = 'lead';
    case Past = 'past';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Lead => 'Prospect',
            self::Past => 'Ancien',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'emerald',
            self::Lead => 'blue',
            self::Past => 'zinc',
        };
    }
}
