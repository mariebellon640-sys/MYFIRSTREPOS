<?php

declare(strict_types=1);

namespace App\Enum;

enum StatutValidation: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case VALIDE = 'VALIDE';
    case SUSPENDU = 'SUSPENDU';

    public function libelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente de validation',
            self::VALIDE => 'Valide',
            self::SUSPENDU => 'Suspendu',
        };
    }
}
