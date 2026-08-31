<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Repository\CommandeRepository;
use App\Repository\UtilisateurRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Verifie que chaque espace metier repond correctement pour le role attendu.
 */
class BackOfficeTest extends WebTestCase
{
    /** @return iterable<string, array{string, list<string>}> */
    public static function espaces(): iterable
    {
        yield 'administrateur' => ['admin@poissonvip.mg', [
            '/admin', '/admin/utilisateurs', '/admin/fournisseurs', '/admin/commandes',
            '/admin/paiements', '/admin/livraisons', '/admin/zones', '/admin/promotions',
        ]];
        yield 'support' => ['support@poissonvip.mg', ['/support/litiges', '/support/avis']];
        yield 'fournisseur' => ['fanja@mareyeur-tana.mg', ['/fournisseur', '/fournisseur/produits', '/fournisseur/commandes']];
        yield 'livreur' => ['tiana@poissonvip.mg', ['/livreur']];
        yield 'client' => ['hasina@example.mg', ['/panier', '/commande/historique', '/compte', '/compte/adresses', '/compte/notifications', '/compte/fidelite']];
    }

    /** @param list<string> $urls */
    #[DataProvider('espaces')]
    public function testEspacesMetier(string $email, array $urls): void
    {
        $client = static::createClient();
        $this->connecter($client, $email);

        foreach ($urls as $url) {
            $client->request('GET', $url);
            self::assertResponseIsSuccessful(sprintf('%s doit etre accessible a %s.', $url, $email));
        }
    }

    public function testUnClientConsulteSaCommandeEtSaFacture(): void
    {
        $client = static::createClient();
        $this->connecter($client, 'hasina@example.mg');

        $commande = static::getContainer()->get(CommandeRepository::class)
            ->findOneBy(['client' => static::getContainer()->get(UtilisateurRepository::class)
                ->findOneBy(['email' => 'hasina@example.mg'])?->getClient()]);
        self::assertNotNull($commande);

        $client->request('GET', '/commande/'.$commande->getNumeroCommande());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', $commande->getNumeroCommande());

        $client->request('GET', '/commande/'.$commande->getNumeroCommande().'/facture');
        self::assertResponseIsSuccessful();
    }

    public function testUnClientNAccedePasALaCommandeDUnAutre(): void
    {
        $client = static::createClient();
        $this->connecter($client, 'nirina@example.mg');

        $autre = static::getContainer()->get(CommandeRepository::class)
            ->findOneBy(['client' => static::getContainer()->get(UtilisateurRepository::class)
                ->findOneBy(['email' => 'hasina@example.mg'])?->getClient()]);
        self::assertNotNull($autre);

        $client->request('GET', '/commande/'.$autre->getNumeroCommande());
        self::assertResponseStatusCodeSame(403);
    }

    private function connecter(KernelBrowser $client, string $email): void
    {
        $utilisateur = static::getContainer()->get(UtilisateurRepository::class)->findOneBy(['email' => $email]);
        self::assertNotNull($utilisateur);
        $client->loginUser($utilisateur);
    }
}
