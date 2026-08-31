<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Livraison;
use App\Entity\LivraisonPosition;
use App\Entity\Livreur;
use App\Entity\Utilisateur;
use App\Enum\ModePaiement;
use App\Enum\StatutCommande;
use App\Enum\StatutDisponibilite;
use App\Enum\StatutLivraison;
use App\Repository\LivraisonRepository;
use App\Security\Voter\LivraisonVoter;
use App\Service\CommandeService;
use App\Service\LivraisonAffectationService;
use App\Service\PaiementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/livreur')]
#[IsGranted('ROLE_LIVREUR')]
class LivreurController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LivraisonAffectationService $affectationService,
    ) {
    }

    #[Route('', name: 'livreur_courses', methods: ['GET'])]
    public function courses(LivraisonRepository $livraisonRepository): Response
    {
        $livreur = $this->livreur();
        $actives = $livraisonRepository->findPourLivreur($livreur, true);

        return $this->render('livreur/courses.html.twig', [
            'livreur' => $livreur,
            'courses' => $actives,
            'historique' => $livraisonRepository->findPourLivreur($livreur),
            'itineraire' => $this->affectationService->optimiserItineraire(
                $actives,
                (float) ($livreur->getLatitudeActuelle() ?? '-18.8792'),
                (float) ($livreur->getLongitudeActuelle() ?? '47.5079'),
            ),
        ]);
    }

    #[Route('/disponibilite', name: 'livreur_disponibilite', methods: ['POST'])]
    public function disponibilite(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('disponibilite', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $statut = StatutDisponibilite::tryFrom((string) $request->request->get('statut', ''));
        if (null !== $statut) {
            $this->livreur()->setStatutDisponibilite($statut);
            $this->em->flush();
        }

        return $this->redirectToRoute('livreur_courses');
    }

    #[Route('/course/{id}', name: 'livreur_course', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function course(Livraison $livraison): Response
    {
        $this->denyAccessUnlessGranted(LivraisonVoter::METTRE_A_JOUR, $livraison);

        return $this->render('livreur/course.html.twig', ['livraison' => $livraison]);
    }

    #[Route('/course/{id}/statut', name: 'livreur_course_statut', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function changerStatut(
        Request $request,
        Livraison $livraison,
        CommandeService $commandeService,
        PaiementService $paiementService,
    ): Response {
        $this->denyAccessUnlessGranted(LivraisonVoter::METTRE_A_JOUR, $livraison);

        if (!$this->isCsrfTokenValid('course'.$livraison->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $statut = StatutLivraison::tryFrom((string) $request->request->get('statut', ''));
        if (null === $statut) {
            $this->addFlash('danger', 'Statut de livraison inconnu.');

            return $this->redirectToRoute('livreur_course', ['id' => $livraison->getId()]);
        }

        $livraison->setStatut($statut);
        $livraison->setCommentaire($request->request->get('commentaire'));
        $commande = $livraison->getCommande();

        match ($statut) {
            StatutLivraison::EN_COURS => $commandeService->changerStatut($commande, StatutCommande::EN_LIVRAISON),
            StatutLivraison::LIVREE => $this->cloturer($commande, $commandeService, $paiementService),
            StatutLivraison::ECHOUEE => $commandeService->changerStatut($commande, StatutCommande::ANNULEE),
            default => $this->em->flush(),
        };

        $this->affectationService->libererLivreur($this->livreur());
        $this->addFlash('success', 'Course mise a jour.');

        return $this->redirectToRoute('livreur_courses');
    }

    /** Remontee de position appelee periodiquement par le navigateur du livreur. */
    #[Route('/course/{id}/position', name: 'livreur_course_position', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function remonterPosition(Request $request, Livraison $livraison): JsonResponse
    {
        $this->denyAccessUnlessGranted(LivraisonVoter::METTRE_A_JOUR, $livraison);

        /** @var array{latitude?: float|string, longitude?: float|string} $donnees */
        $donnees = json_decode((string) $request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        if (!isset($donnees['latitude'], $donnees['longitude'])) {
            return new JsonResponse(['erreur' => 'Coordonnees manquantes.'], Response::HTTP_BAD_REQUEST);
        }

        $latitude = number_format((float) $donnees['latitude'], 7, '.', '');
        $longitude = number_format((float) $donnees['longitude'], 7, '.', '');

        $position = new LivraisonPosition($latitude, $longitude);
        $livraison->addPosition($position);
        $this->livreur()->setPositionActuelle($latitude, $longitude);

        $this->em->persist($position);
        $this->em->flush();

        return new JsonResponse([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'etaMinutes' => $livraison->getEtaMinutes(),
        ]);
    }

    private function cloturer(Commande $commande, CommandeService $commandeService, PaiementService $paiementService): void
    {
        $paiement = $commande->getPaiement();
        if (null !== $paiement && ModePaiement::ESPECES_LIVRAISON === $paiement->getModePaiement()) {
            $paiementService->confirmerEspecesParLivreur($paiement);
        }

        $commandeService->changerStatut($commande, StatutCommande::LIVREE);
    }

    private function livreur(): Livreur
    {
        $utilisateur = $this->getUser();
        \assert($utilisateur instanceof Utilisateur);
        $livreur = $utilisateur->getLivreur();
        if (null === $livreur) {
            throw $this->createAccessDeniedException('Aucun profil livreur rattache a ce compte.');
        }

        return $livreur;
    }
}
