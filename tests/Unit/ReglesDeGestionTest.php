<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\CodePromo;
use App\Entity\Fournisseur;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Enum\Role;
use App\Enum\StatutCommande;
use App\Enum\StatutValidation;
use App\Enum\TypeReduction;
use PHPUnit\Framework\TestCase;

/**
 * Couverture des regles de gestion du cahier des charges (section 5.3).
 */
class ReglesDeGestionTest extends TestCase
{
    public function testUnProduitNEstCommandableQueDansLaLimiteDuStock(): void
    {
        $produit = new Produit();
        $produit->setStockDisponible('3.50')->setEstActif(true);

        self::assertTrue($produit->estDisponible('3.50'));
        self::assertFalse($produit->estDisponible('3.51'));

        $produit->decrementerStock('1.50');
        self::assertSame('2.00', $produit->getStockDisponible());
        self::assertFalse($produit->estDisponible('2.01'));
    }

    public function testUnProduitInactifNEstJamaisDisponible(): void
    {
        $produit = new Produit();
        $produit->setStockDisponible('10.00')->setEstActif(false);

        self::assertFalse($produit->estDisponible('1.00'));
    }

    public function testAnnulationClientImpossibleDesLaPreparation(): void
    {
        self::assertTrue(StatutCommande::EN_ATTENTE->estAnnulableParClient());
        self::assertTrue(StatutCommande::VALIDEE->estAnnulableParClient());
        self::assertFalse(StatutCommande::EN_PREPARATION->estAnnulableParClient());
        self::assertFalse(StatutCommande::EN_LIVRAISON->estAnnulableParClient());
        self::assertFalse(StatutCommande::LIVREE->estAnnulableParClient());
    }

    public function testUnFournisseurNonValideNePeutPasPublier(): void
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setRole(Role::FOURNISSEUR);
        $fournisseur = new Fournisseur($utilisateur);

        self::assertFalse($fournisseur->peutPublier());

        $fournisseur->setStatutValidation(StatutValidation::VALIDE);
        self::assertTrue($fournisseur->peutPublier());

        $fournisseur->setStatutValidation(StatutValidation::SUSPENDU);
        self::assertFalse($fournisseur->peutPublier());
    }

    public function testCalculDesReductions(): void
    {
        $pourcentage = (new CodePromo())->setTypeReduction(TypeReduction::POURCENTAGE)->setValeur('10.00');
        self::assertSame('5000.00', $pourcentage->calculerReduction('50000.00'));

        $montantFixe = (new CodePromo())->setTypeReduction(TypeReduction::MONTANT_FIXE)->setValeur('5000.00');
        self::assertSame('5000.00', $montantFixe->calculerReduction('50000.00'));
        self::assertSame('3000.00', $montantFixe->calculerReduction('3000.00'), 'La reduction ne peut pas depasser le montant.');
    }

    public function testUnCodePromoExpireOuEpuiseNEstPasUtilisable(): void
    {
        $expire = (new CodePromo())->setDateFin(new \DateTimeImmutable('-1 day'));
        self::assertFalse($expire->estUtilisable());

        $epuise = (new CodePromo())->setUtilisationMax(1);
        self::assertTrue($epuise->estUtilisable());
        $epuise->incrementerUtilisation();
        self::assertFalse($epuise->estUtilisable());
    }
}
