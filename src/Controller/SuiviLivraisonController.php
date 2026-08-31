<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Livraison;
use App\Repository\CommandeRepository;
use App\Security\Voter\LivraisonVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Suivi cartographique de la livraison cote client. */
class SuiviLivraisonController extends AbstractController
{
    public function __construct(private readonly CommandeRepository $commandeRepository)
    {
    }

    #[Route('/suivi/{numero}', name: 'app_suivi_livraison', methods: ['GET'])]
    public function suivi(string $numero): Response
    {
        $livraison = $this->livraison($numero);
        $this->denyAccessUnlessGranted(LivraisonVoter::SUIVRE, $livraison);

        return $this->render('suivi/livraison.html.twig', [
            'livraison' => $livraison,
            'commande' => $livraison->getCommande(),
        ]);
    }

    #[Route('/suivi/{numero}/position', name: 'app_suivi_position', methods: ['GET'])]
    public function position(string $numero): JsonResponse
    {
        $livraison = $this->livraison($numero);
        $this->denyAccessUnlessGranted(LivraisonVoter::SUIVRE, $livraison);

        $position = $livraison->getDernierePosition();

        return new JsonResponse([
            'statut' => $livraison->getStatut()->value,
            'statutLibelle' => $livraison->getStatut()->libelle(),
            'etaMinutes' => $livraison->getEtaMinutes(),
            'livreur' => $livraison->getLivreur()?->getUtilisateur()->getNomComplet(),
            'position' => null === $position ? null : [
                'latitude' => (float) $position->getLatitude(),
                'longitude' => (float) $position->getLongitude(),
                'horodatage' => $position->getHorodatage()->format(\DATE_ATOM),
            ],
            'destination' => [
                'latitude' => null === $livraison->getLatitudeLivraison() ? null : (float) $livraison->getLatitudeLivraison(),
                'longitude' => null === $livraison->getLongitudeLivraison() ? null : (float) $livraison->getLongitudeLivraison(),
            ],
        ]);
    }

    private function livraison(string $numero): Livraison
    {
        $commande = $this->commandeRepository->findOneBy(['numeroCommande' => $numero]);
        if (null === $commande || null === $commande->getLivraison()) {
            throw $this->createNotFoundException('Livraison introuvable.');
        }

        return $commande->getLivraison();
    }
}
