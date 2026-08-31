<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Produit;

class StockInsuffisantException extends \RuntimeException
{
    public function __construct(private readonly Produit $produit, private readonly string $quantiteDemandee)
    {
        parent::__construct(sprintf(
            'Stock insuffisant pour "%s" : %s %s demandes, %s disponibles.',
            $produit->getNom(),
            $quantiteDemandee,
            $produit->getUnite()->libelle(),
            $produit->getStockDisponible(),
        ));
    }

    public function getProduit(): Produit
    {
        return $this->produit;
    }

    public function getQuantiteDemandee(): string
    {
        return $this->quantiteDemandee;
    }
}
