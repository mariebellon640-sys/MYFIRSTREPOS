<?php

declare(strict_types=1);

namespace App\Enum;

enum StatutDisponibilite: string
{
    case DISPONIBLE = 'DISPONIBLE';
    case EN_COURSE = 'EN_COURSE';
    case HORS_LIGNE = 'HORS_LIGNE';

    public function libelle(): string
    {
        return match ($this) {
            self::DISPONIBLE => 'Disponible',
            self::EN_COURSE => 'En course',
            self::HORS_LIGNE => 'Hors ligne',
        };
    }
}
