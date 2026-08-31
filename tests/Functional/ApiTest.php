<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiTest extends WebTestCase
{
    public function testCatalogueExposeParApiPlatform(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/produits', server: ['HTTP_ACCEPT' => 'application/ld+json']);

        self::assertResponseIsSuccessful();
        $donnees = $this->decoder($client);

        self::assertArrayHasKey('member', $donnees);
        self::assertGreaterThan(0, $donnees['totalItems']);
        self::assertArrayHasKey('nom', $donnees['member'][0]);
        self::assertArrayHasKey('indiceFraicheur', $donnees['member'][0]);
    }

    public function testRechercheParNom(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/produits?nom=thon', server: ['HTTP_ACCEPT' => 'application/ld+json']);

        self::assertResponseIsSuccessful();
        foreach ($this->decoder($client)['member'] as $produit) {
            self::assertStringContainsStringIgnoringCase('thon', $produit['nom']);
        }
    }

    public function testAuthentificationJwt(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['identifiant' => 'hasina@example.mg', 'motDePasse' => 'Poisson2024!'], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('token', $this->decoder($client));
    }

    public function testAuthentificationJwtRefuseeAvecMauvaisMotDePasse(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['identifiant' => 'hasina@example.mg', 'motDePasse' => 'incorrect'], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(401);
    }

    /** @return array<string, mixed> */
    private function decoder(KernelBrowser $client): array
    {
        $contenu = $client->getResponse()->getContent();
        self::assertIsString($contenu);

        return json_decode($contenu, true, 512, \JSON_THROW_ON_ERROR);
    }
}
