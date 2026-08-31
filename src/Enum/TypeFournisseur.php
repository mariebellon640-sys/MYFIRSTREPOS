<?php

declare(strict_types=1);

namespace App\Enum;

enum TypeFournisseur: string
{
    case MAREYEUR = 'MAREYEUR';
    case POISSONNERIE = 'POISSONNERIE';
    case COOPERATIVE = 'COOPERATIVE';

    public function libelle(): string
    {
        return match ($this) {
            self::MAREYEUR => 'Mareyeur',
            self::POISSONNERIE => 'Poissonnerie',
            self::COOPERATIVE => 'Cooperative de pecheurs',
        };
    }
}
