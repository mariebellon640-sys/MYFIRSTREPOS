<?php

declare(strict_types=1);

namespace App\Enum;

enum TypeVehicule: string
{
    case MOTO = 'MOTO';
    case VELO = 'VELO';
    case VOITURE = 'VOITURE';
    case A_PIED = 'A_PIED';

    public function libelle(): string
    {
        return match ($this) {
            self::MOTO => 'Moto',
            self::VELO => 'Velo',
            self::VOITURE => 'Voiture',
            self::A_PIED => 'A pied',
        };
    }
}
