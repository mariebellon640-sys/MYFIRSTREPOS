<?php

declare(strict_types=1);

namespace App\Enum;

enum TypeLitige: string
{
    case PRODUIT_NON_CONFORME = 'PRODUIT_NON_CONFORME';
    case RETARD_LIVRAISON = 'RETARD_LIVRAISON';
    case PROBLEME_PAIEMENT = 'PROBLEME_PAIEMENT';
    case AUTRE = 'AUTRE';

    public function libelle(): string
    {
        return match ($this) {
            self::PRODUIT_NON_CONFORME => 'Produit non conforme',
            self::RETARD_LIVRAISON => 'Retard de livraison',
            self::PROBLEME_PAIEMENT => 'Probleme de paiement',
            self::AUTRE => 'Autre',
        };
    }
}
