<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Repository\ProduitRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ParcoursPublicTest extends WebTestCase
{
    public function testPagesPubliquesAccessibles(): void
    {
        $client = static::createClient();

        foreach (['/', '/catalogue', '/connexion', '/inscription', '/mentions-legales'] as $url) {
            $client->request('GET', $url);
            self::assertResponseIsSuccessful(sprintf('La page %s doit repondre en 200.', $url));
        }
    }

    public function testFicheProduitEtTracabilite(): void
    {
        $client = static::createClient();
        $produit = static::getContainer()->get(ProduitRepository::class)->findOneBy(['estActif' => true]);
        self::assertNotNull($produit);

        $client->request('GET', '/produit/'.$produit->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', $produit->getNom());

        $lot = $produit->getLotCourant();
        self::assertNotNull($lot);

        $client->request('GET', '/tracabilite/'.$lot->getCodeLot());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', $lot->getLieuPeche());
    }

    public function testLeCatalogueEstFiltrable(): void
    {
        $client = static::createClient();

        $client->request('GET', '/catalogue', ['recherche' => 'thon']);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Thon');
    }

    public function testEspacesPrivesInterditsAuxVisiteurs(): void
    {
        $client = static::createClient();

        foreach (['/panier', '/commande/historique', '/compte', '/admin', '/support/litiges', '/fournisseur', '/livreur'] as $url) {
            $client->request('GET', $url);
            self::assertResponseRedirects('/connexion', null, sprintf('La page %s doit exiger une connexion.', $url));
        }
    }

    public function testCloisonnementDesRoles(): void
    {
        $client = static::createClient();
        $this->connecter($client, 'hasina@example.mg');

        foreach (['/admin', '/support/litiges', '/fournisseur', '/livreur'] as $url) {
            $client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, sprintf('Un client ne doit pas acceder a %s.', $url));
        }
    }

    private function connecter(KernelBrowser $client, string $email): void
    {
        $utilisateur = static::getContainer()->get(UtilisateurRepository::class)->findOneBy(['email' => $email]);
        self::assertNotNull($utilisateur);
        $client->loginUser($utilisateur);
    }
}
