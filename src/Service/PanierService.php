<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Client;
use App\Entity\Panier;
use App\Entity\PanierItem;
use App\Entity\Produit;
use App\Repository\PanierRepository;
use Doctrine\ORM\EntityManagerInterface;

class PanierService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PanierRepository $panierRepository,
    ) {
    }

    public function obtenirPanier(Client $client): Panier
    {
        $panier = $this->panierRepository->findPourClient($client);
        if (null === $panier) {
            $panier = new Panier($client);
            $this->em->persist($panier);
            $this->em->flush();
        }

        return $panier;
    }

    /**
     * @throws StockInsuffisantException si le stock du fournisseur ne couvre pas la quantite demandee
     */
    public function ajouterProduit(Client $client, Produit $produit, string $quantite, ?string $preparation = null): Panier
    {
        $panier = $this->obtenirPanier($client);
        $quantiteTotale = number_format((float) $quantite + (float) $this->quantiteDejaAuPanier($panier, $produit, $preparation), 2, '.', '');

        if (!$produit->estDisponible($quantiteTotale)) {
            throw new StockInsuffisantException($produit, $quantiteTotale);
        }

        $panier->addItem(new PanierItem($produit, $quantite, $preparation));
        $this->em->flush();

        return $panier;
    }

    public function modifierQuantite(PanierItem $item, string $quantite): void
    {
        if ((float) $quantite <= 0) {
            $this->retirerItem($item);

            return;
        }

        if (!$item->getProduit()->estDisponible($quantite)) {
            throw new StockInsuffisantException($item->getProduit(), $quantite);
        }

        $item->setQuantite($quantite);
        $this->em->flush();
    }

    public function retirerItem(PanierItem $item): void
    {
        $panier = $item->getPanier();
        $panier?->removeItem($item);
        $this->em->remove($item);
        $this->em->flush();
    }

    public function vider(Panier $panier): void
    {
        foreach ($panier->getItems() as $item) {
            $this->em->remove($item);
        }
        $panier->vider();
        $this->em->flush();
    }

    private function quantiteDejaAuPanier(Panier $panier, Produit $produit, ?string $preparation): string
    {
        foreach ($panier->getItems() as $item) {
            if ($item->correspond($produit, $preparation)) {
                return $item->getQuantite();
            }
        }

        return '0.00';
    }
}
