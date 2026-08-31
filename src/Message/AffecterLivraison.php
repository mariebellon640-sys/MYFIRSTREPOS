<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Demande d'affectation d'un livreur pour une commande validee.
 * Traitee de maniere asynchrone afin de ne pas ralentir le tunnel de paiement.
 */
final readonly class AffecterLivraison
{
    public function __construct(public int $commandeId)
    {
    }
}
