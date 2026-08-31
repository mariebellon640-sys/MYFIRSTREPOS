<?php

declare(strict_types=1);

namespace App\Enum;

enum StatutLivraison: string
{
    case EN_ATTENTE_AFFECTATION = 'EN_ATTENTE_AFFECTATION';
    case AFFECTEE = 'AFFECTEE';
    case EN_COURS = 'EN_COURS';
    case LIVREE = 'LIVREE';
    case ECHOUEE = 'ECHOUEE';

    public function libelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE_AFFECTATION => 'En attente d\'affectation',
            self::AFFECTEE => 'Affectee',
            self::EN_COURS => 'En cours',
            self::LIVREE => 'Livree',
            self::ECHOUEE => 'Echouee',
        };
    }

    public function estCloturee(): bool
    {
        return \in_array($this, [self::LIVREE, self::ECHOUEE], true);
    }
}
