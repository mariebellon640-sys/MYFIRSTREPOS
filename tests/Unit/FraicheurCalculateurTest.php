<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\LotPeche;
use App\Service\FraicheurCalculateur;
use PHPUnit\Framework\TestCase;

class FraicheurCalculateurTest extends TestCase
{
    private FraicheurCalculateur $calculateur;

    protected function setUp(): void
    {
        $this->calculateur = new FraicheurCalculateur(72, 40.0);
    }

    public function testUnLotFraichementPecheEstANotePleine(): void
    {
        $lot = $this->lotPecheIlYA(0);

        self::assertSame(100.0, $this->calculateur->calculerIndice($lot));
        self::assertFalse($this->calculateur->doitEtreRetire($lot));
    }

    public function testIndiceDecroissantAvecLeTemps(): void
    {
        self::assertSame(75.0, $this->calculateur->calculerIndice($this->lotPecheIlYA(18)));
        self::assertSame(50.0, $this->calculateur->calculerIndice($this->lotPecheIlYA(36)));
        self::assertSame(0.0, $this->calculateur->calculerIndice($this->lotPecheIlYA(100)));
    }

    public function testLotSousLeSeuilEstRetireDuCatalogue(): void
    {
        $lot = $this->lotPecheIlYA(60);

        self::assertTrue($this->calculateur->doitEtreRetire($lot));
        self::assertTrue($this->calculateur->actualiser($lot));
        self::assertTrue($lot->isEstRetire());
        self::assertSame('16.7', $lot->getIndiceFraicheur());
        self::assertFalse($this->calculateur->actualiser($lot), 'Un lot deja retire ne doit pas etre retire deux fois.');
    }

    public function testLibellesEtCouleurs(): void
    {
        self::assertSame('Peche du jour', $this->calculateur->libelle(95.0));
        self::assertSame('Tres frais', $this->calculateur->libelle(72.0));
        self::assertSame('Retire de la vente', $this->calculateur->libelle(10.0));
        self::assertNotSame('', $this->calculateur->couleur(95.0));
    }

    private function lotPecheIlYA(int $heures): LotPeche
    {
        $lot = new LotPeche();
        $lot->setDateHeureCapture(new \DateTimeImmutable(sprintf('-%d hours', $heures)));

        return $lot;
    }
}
