<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Litige;
use App\Entity\Utilisateur;
use App\Enum\StatutLitige;
use App\Enum\StatutModeration;
use App\Repository\AvisRepository;
use App\Repository\LitigeRepository;
use App\Service\PaiementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/support')]
#[IsGranted('ROLE_SUPPORT')]
class SupportController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/litiges', name: 'support_litiges', methods: ['GET'])]
    public function litiges(LitigeRepository $litigeRepository): Response
    {
        return $this->render('support/litiges.html.twig', [
            'ouverts' => $litigeRepository->findBy(['statut' => [StatutLitige::OUVERT, StatutLitige::EN_COURS]], ['dateOuverture' => 'ASC']),
            'clotures' => $litigeRepository->findBy(['statut' => [StatutLitige::RESOLU, StatutLitige::REJETE]], ['dateResolution' => 'DESC'], 30),
        ]);
    }

    #[Route('/litiges/{id}/traiter', name: 'support_litige_traiter', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function traiterLitige(Request $request, Litige $litige, PaiementService $paiementService): Response
    {
        if (!$this->isCsrfTokenValid('litige'.$litige->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $utilisateur = $this->getUser();
        \assert($utilisateur instanceof Utilisateur);

        $statut = StatutLitige::tryFrom((string) $request->request->get('statut', ''));
        $litige->setTraitePar($utilisateur);
        $litige->setReponse($request->request->get('reponse'));

        $montantRembourse = (string) $request->request->get('montant_rembourse', '');
        if ('' !== $montantRembourse && (float) $montantRembourse > 0) {
            $litige->setMontantRembourse(number_format((float) $montantRembourse, 2, '.', ''));
            $paiement = $litige->getCommande()->getPaiement();
            if (null !== $paiement) {
                $paiementService->rembourser($paiement, $litige->getMontantRembourse());
            }
        }

        if (null !== $statut) {
            $litige->setStatut($statut);
        }

        $this->em->flush();
        $this->addFlash('success', 'Litige mis a jour.');

        return $this->redirectToRoute('support_litiges');
    }

    #[Route('/avis', name: 'support_avis', methods: ['GET'])]
    public function moderationAvis(AvisRepository $avisRepository): Response
    {
        return $this->render('support/avis.html.twig', ['avis' => $avisRepository->findAModerer()]);
    }

    #[Route('/avis/{id}/moderer', name: 'support_avis_moderer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function modererAvis(Request $request, Avis $avis): Response
    {
        if (!$this->isCsrfTokenValid('moderer'.$avis->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $statut = StatutModeration::tryFrom((string) $request->request->get('statut', ''));
        if (null !== $statut) {
            $avis->setStatutModeration($statut);
            $this->em->flush();
            $this->addFlash('success', 'Avis modere.');
        }

        return $this->redirectToRoute('support_avis');
    }
}
