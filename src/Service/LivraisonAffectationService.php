<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Commande;
use App\Entity\Livraison;
use App\Entity\Livreur;
use App\Enum\StatutDisponibilite;
use App\Enum\StatutLivraison;
use App\Enum\TypeNotification;
use App\Repository\LivraisonRepository;
use App\Repository\LivreurRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Affectation des courses : priorite aux livreurs de la zone de livraison, puis
 * au plus proche du point de livraison, en respectant la regle de gestion 4
 * (une seule course active par livreur).
 */
class LivraisonAffectationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LivreurRepository $livreurRepository,
        private readonly LivraisonRepository $livraisonRepository,
        private readonly NotificationService $notificationService,
    ) {
    }

    public function affecterAutomatiquement(Commande $commande): ?Livreur
    {
        $livraison = $commande->getLivraison();
        if (null === $livraison || null !== $livraison->getLivreur()) {
            return $livraison?->getLivreur();
        }

        $candidats = $this->livreurRepository->findDisponibles($commande->getZoneLivraison());
        if ([] === $candidats) {
            return null;
        }

        $meilleur = $this->choisirLePlusProche($candidats, $livraison);
        $this->affecter($livraison, $meilleur);

        return $meilleur;
    }

    public function affecter(Livraison $livraison, Livreur $livreur): void
    {
        if ($this->livraisonRepository->aUneCourseEnCours($livreur)) {
            throw new \DomainException('Ce livreur a deja une course en cours.');
        }

        $livraison->setLivreur($livreur);
        $livreur->setStatutDisponibilite(StatutDisponibilite::EN_COURSE);
        $this->em->flush();

        $this->notificationService->notifier(
            $livreur->getUtilisateur(),
            TypeNotification::LIVRAISON,
            'Nouvelle course affectee',
            sprintf('Commande %s a livrer dans la zone %s.',
                $livraison->getCommande()->getNumeroCommande(),
                $livraison->getCommande()->getZoneLivraison()->getNomZone(),
            ),
        );
    }

    public function libererLivreur(Livreur $livreur): void
    {
        if (!$this->livraisonRepository->aUneCourseEnCours($livreur)) {
            $livreur->setStatutDisponibilite(StatutDisponibilite::DISPONIBLE);
            $this->em->flush();
        }
    }

    /** @param list<Livreur> $candidats */
    private function choisirLePlusProche(array $candidats, Livraison $livraison): Livreur
    {
        $latitude = $livraison->getLatitudeLivraison();
        $longitude = $livraison->getLongitudeLivraison();

        if (null === $latitude || null === $longitude) {
            return $candidats[0];
        }

        $meilleur = $candidats[0];
        $meilleureDistance = \PHP_FLOAT_MAX;

        foreach ($candidats as $candidat) {
            $latitudeLivreur = $candidat->getLatitudeActuelle();
            $longitudeLivreur = $candidat->getLongitudeActuelle();
            if (null === $latitudeLivreur || null === $longitudeLivreur) {
                continue;
            }

            $distance = self::distanceKm(
                (float) $latitude,
                (float) $longitude,
                (float) $latitudeLivreur,
                (float) $longitudeLivreur,
            );

            if ($distance < $meilleureDistance) {
                $meilleureDistance = $distance;
                $meilleur = $candidat;
            }
        }

        return $meilleur;
    }

    /** Distance orthodromique (formule de haversine) en kilometres. */
    public static function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $rayonTerre = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $rayonTerre * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Itineraire optimise (plus proche voisin) pour un lot de livraisons
     * regroupees sur une meme tournee.
     *
     * @param list<Livraison> $livraisons
     *
     * @return list<Livraison>
     */
    public function optimiserItineraire(array $livraisons, float $latitudeDepart, float $longitudeDepart): array
    {
        $restantes = $livraisons;
        $ordonnees = [];
        $lat = $latitudeDepart;
        $lon = $longitudeDepart;

        while ([] !== $restantes) {
            $indexProche = 0;
            $distanceProche = \PHP_FLOAT_MAX;

            foreach ($restantes as $index => $livraison) {
                $latLivraison = $livraison->getLatitudeLivraison();
                $lonLivraison = $livraison->getLongitudeLivraison();
                if (null === $latLivraison || null === $lonLivraison) {
                    continue;
                }
                $distance = self::distanceKm($lat, $lon, (float) $latLivraison, (float) $lonLivraison);
                if ($distance < $distanceProche) {
                    $distanceProche = $distance;
                    $indexProche = $index;
                }
            }

            $choisie = $restantes[$indexProche];
            unset($restantes[$indexProche]);
            $restantes = array_values($restantes);
            $ordonnees[] = $choisie;

            $lat = (float) ($choisie->getLatitudeLivraison() ?? $lat);
            $lon = (float) ($choisie->getLongitudeLivraison() ?? $lon);
        }

        return $ordonnees;
    }

    public function estCloturee(Livraison $livraison): bool
    {
        return \in_array($livraison->getStatut(), [StatutLivraison::LIVREE, StatutLivraison::ECHOUEE], true);
    }
}
