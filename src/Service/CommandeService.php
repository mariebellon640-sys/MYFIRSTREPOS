<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Adresse;
use App\Entity\Client;
use App\Entity\CodePromo;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\Livraison;
use App\Entity\Paiement;
use App\Entity\Panier;
use App\Enum\ModePaiement;
use App\Enum\StatutCommande;
use App\Enum\StatutLivraison;
use App\Enum\StatutPaiement;
use App\Enum\TypeNotification;
use App\Message\AffecterLivraison;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class CommandeService
{
    /** 1 point de fidelite pour 1 000 Ar depenses. */
    private const ARIARY_PAR_POINT = 1000;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PanierService $panierService,
        private readonly NotificationService $notificationService,
        private readonly MessageBusInterface $bus,
    ) {
    }

    /**
     * Transforme le panier en commande : verifie les stocks, fige les prix,
     * calcule les montants et cree la livraison et le paiement associes.
     *
     * @throws StockInsuffisantException
     */
    public function creerDepuisPanier(
        Panier $panier,
        Adresse $adresseLivraison,
        ModePaiement $modePaiement,
        ?\DateTimeImmutable $creneauDebut = null,
        ?CodePromo $codePromo = null,
    ): Commande {
        if ($panier->estVide()) {
            throw new \DomainException('Le panier est vide.');
        }

        $zone = $adresseLivraison->getZoneLivraison();
        if (null === $zone || !$zone->isEstActive()) {
            throw new \DomainException('L\'adresse de livraison choisie n\'est rattachee a aucune zone desservie.');
        }

        $commande = new Commande($panier->getClient(), $adresseLivraison, $zone);
        $commande->setFraisLivraison($zone->getFraisLivraison());

        foreach ($panier->getItems() as $item) {
            $produit = $item->getProduit();
            if (!$produit->estDisponible($item->getQuantite())) {
                throw new StockInsuffisantException($produit, $item->getQuantite());
            }
            $commande->addLigne(new LigneCommande($produit, $item->getQuantite(), $item->getPreparationChoisie()));
            $produit->decrementerStock($item->getQuantite());
        }

        $commande->recalculerMontants();

        if (null !== $codePromo && $codePromo->estUtilisable()) {
            $commande->setCodePromo($codePromo);
            $commande->setMontantReduction($codePromo->calculerReduction($commande->getMontantProduits()));
            $codePromo->incrementerUtilisation();
            $commande->recalculerMontants();
        }

        if (null !== $creneauDebut) {
            $commande->setCreneauLivraison($creneauDebut, $creneauDebut->modify('+2 hours'));
        }

        $paiement = new Paiement($commande, $modePaiement);
        $livraison = new Livraison($commande);

        $this->em->persist($commande);
        $this->em->persist($paiement);
        $this->em->persist($livraison);

        $this->panierService->vider($panier);
        $this->em->flush();

        $this->notificationService->notifier(
            $panier->getClient()->getUtilisateur(),
            TypeNotification::COMMANDE,
            'Commande '.$commande->getNumeroCommande().' enregistree',
            sprintf('Votre commande d\'un montant de %s Ar a bien ete enregistree.', $commande->getMontantTotal()),
        );

        return $commande;
    }

    /**
     * Regle de gestion 5 : le paiement en ligne doit etre confirme avant de
     * passer la commande au statut « validee ». Le paiement a la livraison
     * fait passer la commande en validee immediatement.
     */
    public function confirmerApresPaiement(Commande $commande): void
    {
        $paiement = $commande->getPaiement();
        if (null === $paiement) {
            throw new \DomainException('Aucun paiement rattache a cette commande.');
        }

        $paiementEnLigneEnAttente = $paiement->getModePaiement()->estEnLigne()
            && StatutPaiement::CONFIRME !== $paiement->getStatut();

        if ($paiementEnLigneEnAttente) {
            return;
        }

        $this->changerStatut($commande, StatutCommande::VALIDEE);

        $identifiant = $commande->getId();
        if (null !== $identifiant) {
            $this->bus->dispatch(new AffecterLivraison($identifiant));
        }
    }

    public function changerStatut(Commande $commande, StatutCommande $statut): void
    {
        $commande->setStatut($statut);

        if (StatutCommande::LIVREE === $statut) {
            $client = $commande->getClient();
            $client->ajouterPointsFidelite((int) floor((float) $commande->getMontantTotal() / self::ARIARY_PAR_POINT));
        }

        $this->em->flush();

        $this->notificationService->notifier(
            $commande->getClient()->getUtilisateur(),
            TypeNotification::COMMANDE,
            'Commande '.$commande->getNumeroCommande().' : '.$statut->libelle(),
            sprintf('Le statut de votre commande est desormais « %s ».', $statut->libelle()),
        );
    }

    /** Regle de gestion 3 : annulation possible tant que la commande n'est pas en preparation. */
    public function annulerParClient(Commande $commande, Client $client): void
    {
        if ($commande->getClient()->getId() !== $client->getId()) {
            throw new \DomainException('Cette commande n\'appartient pas a ce client.');
        }
        if (!$commande->estAnnulableParClient()) {
            throw new \DomainException('Cette commande ne peut plus etre annulee : elle est deja en preparation.');
        }

        $this->annuler($commande);
    }

    public function annuler(Commande $commande): void
    {
        foreach ($commande->getLignes() as $ligne) {
            $ligne->getProduit()->incrementerStock($ligne->getQuantite());
        }

        $paiement = $commande->getPaiement();
        if (null !== $paiement && StatutPaiement::CONFIRME === $paiement->getStatut()) {
            $paiement->setStatut(StatutPaiement::REMBOURSE);
        }

        $livraison = $commande->getLivraison();
        if (null !== $livraison && !$livraison->getStatut()->estCloturee()) {
            $livraison->setStatut(StatutLivraison::ECHOUEE);
        }

        $this->changerStatut($commande, StatutCommande::ANNULEE);
    }

    /** Re-commande rapide : recharge le panier avec les lignes d'une commande passee. */
    public function recommander(Commande $commande, Client $client): Panier
    {
        $panier = $this->panierService->obtenirPanier($client);

        foreach ($commande->getLignes() as $ligne) {
            $produit = $ligne->getProduit();
            if ($produit->estDisponible($ligne->getQuantite())) {
                $this->panierService->ajouterProduit($client, $produit, $ligne->getQuantite(), $ligne->getPreparationChoisie());
            }
        }

        return $panier;
    }
}
