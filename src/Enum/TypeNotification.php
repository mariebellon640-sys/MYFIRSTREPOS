<?php

declare(strict_types=1);

namespace App\Enum;

enum TypeNotification: string
{
    case COMMANDE = 'COMMANDE';
    case LIVRAISON = 'LIVRAISON';
    case PROMOTION = 'PROMOTION';
    case STOCK = 'STOCK';
    case SYSTEME = 'SYSTEME';

    public function libelle(): string
    {
        return match ($this) {
            self::COMMANDE => 'Commande',
            self::LIVRAISON => 'Livraison',
            self::PROMOTION => 'Promotion',
            self::STOCK => 'Stock',
            self::SYSTEME => 'Systeme',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::COMMANDE => 'bag-check',
            self::LIVRAISON => 'truck',
            self::PROMOTION => 'tag',
            self::STOCK => 'box-seam',
            self::SYSTEME => 'info-circle',
        };
    }
}
