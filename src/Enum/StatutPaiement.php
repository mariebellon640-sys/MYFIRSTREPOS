<?php

declare(strict_types=1);

namespace App\Enum;

enum StatutPaiement: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case CONFIRME = 'CONFIRME';
    case ECHOUE = 'ECHOUE';
    case REMBOURSE = 'REMBOURSE';

    public function libelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::CONFIRME => 'Confirme',
            self::ECHOUE => 'Echoue',
            self::REMBOURSE => 'Rembourse',
        };
    }
}
