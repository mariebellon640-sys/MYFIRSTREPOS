<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\LotPeche;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Indice de fraicheur (0-100) decroissant lineairement depuis l'heure de capture
 * declaree. Au-dela du seuil de retrait, le lot sort du catalogue (regle de gestion 2).
 */
class FraicheurCalculateur
{
    public function __construct(
        #[Autowire('%env(int:FRAICHEUR_DUREE_VIE_HEURES)%')]
        private readonly int $dureeVieHeures = 72,
        #[Autowire('%env(float:FRAICHEUR_SEUIL_RETRAIT)%')]
        private readonly float $seuilRetrait = 40.0,
    ) {
    }

    public function calculerIndice(LotPeche $lot, ?\DateTimeImmutable $maintenant = null): float
    {
        $heures = $lot->getHeuresDepuisCapture($maintenant);
        $indice = 100 * (1 - $heures / $this->dureeVieHeures);

        return round(max(0.0, min(100.0, $indice)), 1);
    }

    public function doitEtreRetire(LotPeche $lot, ?\DateTimeImmutable $maintenant = null): bool
    {
        return $this->calculerIndice($lot, $maintenant) < $this->seuilRetrait;
    }

    /** Met a jour l'indice du lot et son retrait eventuel. Retourne true si le lot vient d'etre retire. */
    public function actualiser(LotPeche $lot, ?\DateTimeImmutable $maintenant = null): bool
    {
        $indice = $this->calculerIndice($lot, $maintenant);
        $lot->setIndiceFraicheur(number_format($indice, 1, '.', ''));

        if (!$lot->isEstRetire() && $indice < $this->seuilRetrait) {
            $lot->setEstRetire(true);

            return true;
        }

        return false;
    }

    public function libelle(float $indice): string
    {
        return match (true) {
            $indice >= 85 => 'Peche du jour',
            $indice >= 70 => 'Tres frais',
            $indice >= 55 => 'Frais',
            $indice >= $this->seuilRetrait => 'A consommer rapidement',
            default => 'Retire de la vente',
        };
    }

    public function couleur(float $indice): string
    {
        return match (true) {
            $indice >= 85 => 'success',
            $indice >= 70 => 'primary',
            $indice >= 55 => 'info',
            $indice >= $this->seuilRetrait => 'warning',
            default => 'danger',
        };
    }

    public function getSeuilRetrait(): float
    {
        return $this->seuilRetrait;
    }

    public function getDureeVieHeures(): int
    {
        return $this->dureeVieHeures;
    }
}
