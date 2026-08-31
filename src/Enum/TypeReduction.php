<?php

declare(strict_types=1);

namespace App\Enum;

enum TypeReduction: string
{
    case POURCENTAGE = 'POURCENTAGE';
    case MONTANT_FIXE = 'MONTANT_FIXE';
}
