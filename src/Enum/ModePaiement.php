<?php

declare(strict_types=1);

namespace App\Enum;

enum ModePaiement: string
{
    case MVOLA = 'MVOLA';
    case ORANGE_MONEY = 'ORANGE_MONEY';
    case AIRTEL_MONEY = 'AIRTEL_MONEY';
    case ESPECES_LIVRAISON = 'ESPECES_LIVRAISON';
    case CARTE_BANCAIRE = 'CARTE_BANCAIRE';

    public function libelle(): string
    {
        return match ($this) {
            self::MVOLA => 'MVola',
            self::ORANGE_MONEY => 'Orange Money',
            self::AIRTEL_MONEY => 'Airtel Money',
            self::ESPECES_LIVRAISON => 'Especes a la livraison',
            self::CARTE_BANCAIRE => 'Carte bancaire',
        };
    }

    public function estMobileMoney(): bool
    {
        return \in_array($this, [self::MVOLA, self::ORANGE_MONEY, self::AIRTEL_MONEY], true);
    }

    public function estEnLigne(): bool
    {
        return self::ESPECES_LIVRAISON !== $this;
    }
}
