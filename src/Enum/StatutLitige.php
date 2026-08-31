<?php

declare(strict_types=1);

namespace App\Enum;

enum StatutLitige: string
{
    case OUVERT = 'OUVERT';
    case EN_COURS = 'EN_COURS';
    case RESOLU = 'RESOLU';
    case REJETE = 'REJETE';

    public function libelle(): string
    {
        return match ($this) {
            self::OUVERT => 'Ouvert',
            self::EN_COURS => 'En cours de traitement',
            self::RESOLU => 'Resolu',
            self::REJETE => 'Rejete',
        };
    }
}
