<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Adresse;
use App\Entity\Utilisateur;
use App\Form\AdresseType;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/compte')]
#[IsGranted('ROLE_USER')]
class CompteController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'app_compte', methods: ['GET', 'POST'])]
    public function profil(Request $request, UserPasswordHasherInterface $hasher): Response
    {
        $utilisateur = $this->utilisateur();

        if ($request->isMethod('POST') && $this->isCsrfTokenValid('profil', (string) $request->request->get('_token'))) {
            $utilisateur
                ->setNom((string) $request->request->get('nom', $utilisateur->getNom()))
                ->setPrenom((string) $request->request->get('prenom', $utilisateur->getPrenom()))
                ->setTelephone((string) $request->request->get('telephone', $utilisateur->getTelephone()));

            $nouveauMotDePasse = (string) $request->request->get('mot_de_passe', '');
            if ('' !== $nouveauMotDePasse) {
                if (\strlen($nouveauMotDePasse) < 8) {
                    $this->addFlash('danger', 'Le mot de passe doit comporter au moins 8 caracteres.');

                    return $this->redirectToRoute('app_compte');
                }
                $utilisateur->setMotDePasse($hasher->hashPassword($utilisateur, $nouveauMotDePasse));
            }

            $this->em->flush();
            $this->addFlash('success', 'Profil mis a jour.');

            return $this->redirectToRoute('app_compte');
        }

        return $this->render('compte/profil.html.twig', ['utilisateur' => $utilisateur]);
    }

    #[Route('/adresses', name: 'app_compte_adresses', methods: ['GET'])]
    public function adresses(): Response
    {
        return $this->render('compte/adresses.html.twig', ['utilisateur' => $this->utilisateur()]);
    }

    #[Route('/adresses/nouvelle', name: 'app_compte_adresse_nouvelle', methods: ['GET', 'POST'])]
    public function nouvelleAdresse(Request $request): Response
    {
        return $this->editerAdresse($request, new Adresse());
    }

    #[Route('/adresses/{id}/modifier', name: 'app_compte_adresse_modifier', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifierAdresse(Request $request, Adresse $adresse): Response
    {
        $this->verifierProprietaire($adresse);

        return $this->editerAdresse($request, $adresse);
    }

    #[Route('/adresses/{id}/supprimer', name: 'app_compte_adresse_supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimerAdresse(Request $request, Adresse $adresse): Response
    {
        $this->verifierProprietaire($adresse);

        if ($this->isCsrfTokenValid('supprimer_adresse'.$adresse->getId(), (string) $request->request->get('_token'))) {
            $this->em->remove($adresse);
            $this->em->flush();
            $this->addFlash('info', 'Adresse supprimee.');
        }

        return $this->redirectToRoute('app_compte_adresses');
    }

    #[Route('/notifications', name: 'app_compte_notifications', methods: ['GET', 'POST'])]
    public function notifications(Request $request, NotificationRepository $repository, NotificationService $service): Response
    {
        $utilisateur = $this->utilisateur();

        if ($request->isMethod('POST') && $this->isCsrfTokenValid('notifications', (string) $request->request->get('_token'))) {
            $service->marquerToutesCommeLues($utilisateur);

            return $this->redirectToRoute('app_compte_notifications');
        }

        return $this->render('compte/notifications.html.twig', [
            'notifications' => $repository->findPourUtilisateur($utilisateur, 50),
        ]);
    }

    #[Route('/fidelite', name: 'app_compte_fidelite', methods: ['GET'])]
    public function fidelite(): Response
    {
        $client = $this->utilisateur()->getClient();
        if (null === $client) {
            throw $this->createAccessDeniedException('Espace reserve aux clients.');
        }

        return $this->render('compte/fidelite.html.twig', ['client' => $client]);
    }

    private function editerAdresse(Request $request, Adresse $adresse): Response
    {
        $utilisateur = $this->utilisateur();
        $formulaire = $this->createForm(AdresseType::class, $adresse);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            if ($adresse->isEstPrincipale()) {
                foreach ($utilisateur->getAdresses() as $autre) {
                    if ($autre !== $adresse) {
                        $autre->setEstPrincipale(false);
                    }
                }
            }

            if (null === $adresse->getId()) {
                $utilisateur->addAdresse($adresse);
                $this->em->persist($adresse);
            }

            $this->em->flush();
            $this->addFlash('success', 'Adresse enregistree.');

            return $this->redirectToRoute('app_compte_adresses');
        }

        return $this->render('compte/adresse_formulaire.html.twig', [
            'formulaire' => $formulaire,
            'adresse' => $adresse,
        ]);
    }

    private function verifierProprietaire(Adresse $adresse): void
    {
        if ($adresse->getUtilisateur()?->getId() !== $this->utilisateur()->getId()) {
            throw $this->createAccessDeniedException('Cette adresse ne vous appartient pas.');
        }
    }

    private function utilisateur(): Utilisateur
    {
        $utilisateur = $this->getUser();
        \assert($utilisateur instanceof Utilisateur);

        return $utilisateur;
    }
}
