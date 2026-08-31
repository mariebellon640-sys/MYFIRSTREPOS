<?php

declare(strict_types=1);

namespace App\Enum;

enum StatutCommande: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case VALIDEE = 'VALIDEE';
    case EN_PREPARATION = 'EN_PREPARATION';
    case EN_LIVRAISON = 'EN_LIVRAISON';
    case LIVREE = 'LIVREE';
    case ANNULEE = 'ANNULEE';

    public function libelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::VALIDEE => 'Validee',
            self::EN_PREPARATION => 'En preparation',
            self::EN_LIVRAISON => 'En livraison',
            self::LIVREE => 'Livree',
            self::ANNULEE => 'Annulee',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'secondary',
            self::VALIDEE => 'info',
            self::EN_PREPARATION => 'warning',
            self::EN_LIVRAISON => 'primary',
            self::LIVREE => 'success',
            self::ANNULEE => 'danger',
        };
    }

    /**
     * Regle de gestion 3 : le client ne peut annuler que tant que la commande
     * n'est pas passee en preparation.
     */
    public function estAnnulableParClient(): bool
    {
        return \in_array($this, [self::EN_ATTENTE, self::VALIDEE], true);
    }
}
