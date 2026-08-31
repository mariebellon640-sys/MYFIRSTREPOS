<?php

declare(strict_types=1);

namespace App\Enum;

enum UniteProduit: string
{
    case KG = 'KG';
    case UNITE = 'UNITE';
    case BARQUETTE = 'BARQUETTE';

    public function libelle(): string
    {
        return match ($this) {
            self::KG => 'kg',
            self::UNITE => 'unite',
            self::BARQUETTE => 'barquette',
        };
    }
}
