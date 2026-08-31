<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Client;
use App\Entity\Utilisateur;
use App\Enum\ModePaiement;
use App\Enum\StatutCommande;
use App\Enum\StatutPaiement;
use App\Repository\ProduitRepository;
use App\Repository\UtilisateurRepository;
use App\Service\CommandeService;
use App\Service\PaiementService;
use App\Service\PanierService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Verifie le cycle complet panier -> commande -> paiement -> livraison.
 */
class ParcoursCommandeTest extends KernelTestCase
{
    public function testCyclePanierCommandePaiement(): void
    {
        self::bootKernel();
        $conteneur = static::getContainer();

        $panierService = $conteneur->get(PanierService::class);
        $commandeService = $conteneur->get(CommandeService::class);
        $paiementService = $conteneur->get(PaiementService::class);

        $client = $this->client($conteneur->get(UtilisateurRepository::class)->findOneBy(['email' => 'nirina@example.mg']));
        $produit = $conteneur->get(ProduitRepository::class)->findOneBy(['estActif' => true]);
        self::assertNotNull($produit);

        $stockInitial = (float) $produit->getStockDisponible();
        $panier = $panierService->ajouterProduit($client, $produit, '2.00', null);
        self::assertSame(1, $panier->getNombreArticles());
        self::assertFalse($panier->estVide());

        $adresse = $client->getUtilisateur()->getAdressePrincipale();
        self::assertNotNull($adresse);

        $commande = $commandeService->creerDepuisPanier($panier, $adresse, ModePaiement::MVOLA);

        self::assertSame(StatutCommande::EN_ATTENTE, $commande->getStatut());
        self::assertTrue($panier->estVide(), 'Le panier doit etre vide apres la commande.');
        self::assertSame($stockInitial - 2.0, (float) $produit->getStockDisponible(), 'Le stock doit etre decremente.');
        self::assertNotNull($commande->getLivraison());

        $paiement = $commande->getPaiement();
        self::assertNotNull($paiement);

        // Regle 5 : la commande n'est validee qu'une fois le paiement en ligne confirme.
        $paiementService->initier($paiement, '0341234567');
        $paiementService->confirmer($paiement);
        self::assertSame(StatutPaiement::CONFIRME, $paiement->getStatut());

        $commandeService->confirmerApresPaiement($commande);
        self::assertSame(StatutCommande::VALIDEE, $commande->getStatut());

        // Regle 3 : annulation possible tant que la preparation n'a pas commence.
        self::assertTrue($commande->getStatut()->estAnnulableParClient());
        $commandeService->annuler($commande);
        self::assertSame(StatutCommande::ANNULEE, $commande->getStatut());
        self::assertSame($stockInitial, (float) $produit->getStockDisponible(), 'Le stock doit etre restitue.');
    }

    private function client(?Utilisateur $utilisateur): Client
    {
        self::assertNotNull($utilisateur);
        $client = $utilisateur->getClient();
        self::assertNotNull($client);

        return $client;
    }
}
