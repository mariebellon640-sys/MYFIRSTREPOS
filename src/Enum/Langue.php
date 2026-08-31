<?php

declare(strict_types=1);

namespace App\Enum;

enum Langue: string
{
    case FR = 'FR';
    case MG = 'MG';

    public function locale(): string
    {
        return match ($this) {
            self::FR => 'fr',
            self::MG => 'mg',
        };
    }
}
