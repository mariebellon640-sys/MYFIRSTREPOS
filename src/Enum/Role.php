<?php

declare(strict_types=1);

namespace App\Enum;

enum Role: string
{
    case CLIENT = 'CLIENT';
    case FOURNISSEUR = 'FOURNISSEUR';
    case LIVREUR = 'LIVREUR';
    case ADMIN = 'ADMIN';
    case SUPPORT = 'SUPPORT';

    public function securityRole(): string
    {
        return 'ROLE_'.$this->value;
    }

    public function libelle(): string
    {
        return match ($this) {
            self::CLIENT => 'Client',
            self::FOURNISSEUR => 'Fournisseur',
            self::LIVREUR => 'Livreur',
            self::ADMIN => 'Administrateur',
            self::SUPPORT => 'Support client',
        };
    }
}
