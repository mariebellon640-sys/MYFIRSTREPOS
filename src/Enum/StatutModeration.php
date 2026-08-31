<?php

declare(strict_types=1);

namespace App\Enum;

enum StatutModeration: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case PUBLIE = 'PUBLIE';
    case REJETE = 'REJETE';

    public function libelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente de moderation',
            self::PUBLIE => 'Publie',
            self::REJETE => 'Rejete',
        };
    }
}
