<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CodePromo;
use App\Entity\Commande;
use App\Entity\Fournisseur;
use App\Entity\Livraison;
use App\Entity\Livreur;
use App\Entity\Utilisateur;
use App\Entity\ZoneLivraison;
use App\Enum\StatutValidation;
use App\Repository\CommandeRepository;
use App\Repository\LivraisonRepository;
use App\Repository\PaiementRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\ZoneLivraisonRepository;
use App\Service\LivraisonAffectationService;
use App\Service\StatistiquesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'admin_tableau_de_bord', methods: ['GET'])]
    public function tableauDeBord(StatistiquesService $statistiques, CommandeRepository $commandeRepository): Response
    {
        return $this->render('admin/tableau_de_bord.html.twig', [
            'statistiques' => $statistiques->tableauDeBordAdministrateur(),
            'commandesRecentes' => $commandeRepository->findRecentes(10),
        ]);
    }

    #[Route('/utilisateurs', name: 'admin_utilisateurs', methods: ['GET'])]
    public function utilisateurs(Request $request, UtilisateurRepository $utilisateurRepository): Response
    {
        $role = \App\Enum\Role::tryFrom((string) $request->query->get('role', ''));

        return $this->render('admin/utilisateurs.html.twig', [
            'utilisateurs' => null !== $role ? $utilisateurRepository->findParRole($role) : $utilisateurRepository->findBy([], ['dateCreation' => 'DESC'], 200),
            'roleFiltre' => $role,
        ]);
    }

    #[Route('/utilisateurs/{id}/activation', name: 'admin_utilisateur_activation', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function basculerActivation(Request $request, Utilisateur $utilisateur): Response
    {
        if (!$this->isCsrfTokenValid('activation'.$utilisateur->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $utilisateur->setEstActif(!$utilisateur->isEstActif());
        $this->em->flush();
        $this->addFlash('success', $utilisateur->isEstActif() ? 'Compte reactive.' : 'Compte suspendu.');

        return $this->redirectToRoute('admin_utilisateurs');
    }

    #[Route('/fournisseurs', name: 'admin_fournisseurs', methods: ['GET'])]
    public function fournisseurs(\App\Repository\FournisseurRepository $fournisseurRepository): Response
    {
        return $this->render('admin/fournisseurs.html.twig', [
            'enAttente' => $fournisseurRepository->findParStatut(StatutValidation::EN_ATTENTE),
            'valides' => $fournisseurRepository->findParStatut(StatutValidation::VALIDE),
            'suspendus' => $fournisseurRepository->findParStatut(StatutValidation::SUSPENDU),
        ]);
    }

    #[Route('/fournisseurs/{id}/statut', name: 'admin_fournisseur_statut', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function statutFournisseur(Request $request, Fournisseur $fournisseur): Response
    {
        if (!$this->isCsrfTokenValid('fournisseur'.$fournisseur->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $statut = StatutValidation::tryFrom((string) $request->request->get('statut', ''));
        if (null !== $statut) {
            $fournisseur->setStatutValidation($statut);
            $this->em->flush();
            $this->addFlash('success', sprintf('Fournisseur %s : %s.', $fournisseur->getNomCommercial(), $statut->libelle()));
        }

        return $this->redirectToRoute('admin_fournisseurs');
    }

    #[Route('/commandes', name: 'admin_commandes', methods: ['GET'])]
    public function commandes(CommandeRepository $commandeRepository): Response
    {
        return $this->render('admin/commandes.html.twig', [
            'commandes' => $commandeRepository->findRecentes(100),
        ]);
    }

    #[Route('/paiements', name: 'admin_paiements', methods: ['GET'])]
    public function paiements(PaiementRepository $paiementRepository): Response
    {
        return $this->render('admin/paiements.html.twig', [
            'paiements' => $paiementRepository->findRecents(100),
            'totaux' => $paiementRepository->totauxParMode(),
        ]);
    }

    #[Route('/livraisons', name: 'admin_livraisons', methods: ['GET'])]
    public function livraisons(LivraisonRepository $livraisonRepository, \App\Repository\LivreurRepository $livreurRepository): Response
    {
        return $this->render('admin/livraisons.html.twig', [
            'enAttente' => $livraisonRepository->findEnAttenteAffectation(),
            'livreursDisponibles' => $livreurRepository->findDisponibles(),
        ]);
    }

    #[Route('/livraisons/{id}/affecter', name: 'admin_livraison_affecter', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function affecter(Request $request, Livraison $livraison, LivraisonAffectationService $affectationService): Response
    {
        if (!$this->isCsrfTokenValid('affecter'.$livraison->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $livreur = $this->em->getRepository(Livreur::class)->find($request->request->getInt('livreur'));

        try {
            if ($livreur instanceof Livreur) {
                $affectationService->affecter($livraison, $livreur);
            } else {
                $affectationService->affecterAutomatiquement($livraison->getCommande());
            }
            $this->addFlash('success', 'Livraison affectee.');
        } catch (\DomainException $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_livraisons');
    }

    #[Route('/zones', name: 'admin_zones', methods: ['GET', 'POST'])]
    public function zones(Request $request, ZoneLivraisonRepository $zoneRepository): Response
    {
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('zone', (string) $request->request->get('_token'))) {
            $zone = new ZoneLivraison();
            $zone
                ->setNomZone((string) $request->request->get('nom_zone', ''))
                ->setCommune((string) $request->request->get('commune', ''))
                ->setFraisLivraison(number_format((float) $request->request->get('frais', 0), 2, '.', ''))
                ->setDelaiEstimeMinutes($request->request->getInt('delai', 60));

            $this->em->persist($zone);
            $this->em->flush();
            $this->addFlash('success', 'Zone de livraison creee.');

            return $this->redirectToRoute('admin_zones');
        }

        return $this->render('admin/zones.html.twig', ['zones' => $zoneRepository->findBy([], ['nomZone' => 'ASC'])]);
    }

    #[Route('/promotions', name: 'admin_promotions', methods: ['GET', 'POST'])]
    public function promotions(Request $request): Response
    {
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('promo', (string) $request->request->get('_token'))) {
            $promo = new CodePromo();
            $promo
                ->setCode(strtoupper((string) $request->request->get('code', '')))
                ->setTypeReduction(\App\Enum\TypeReduction::from((string) $request->request->get('type', 'POURCENTAGE')))
                ->setValeur(number_format((float) $request->request->get('valeur', 0), 2, '.', ''))
                ->setDateDebut(new \DateTimeImmutable((string) $request->request->get('debut', 'now')))
                ->setDateFin(new \DateTimeImmutable((string) $request->request->get('fin', '+30 days')))
                ->setUtilisationMax($request->request->getInt('max') ?: null);

            $this->em->persist($promo);
            $this->em->flush();
            $this->addFlash('success', 'Code promo cree.');

            return $this->redirectToRoute('admin_promotions');
        }

        return $this->render('admin/promotions.html.twig', [
            'promotions' => $this->em->getRepository(CodePromo::class)->findBy([], ['dateFin' => 'DESC']),
        ]);
    }

    #[Route('/commandes/{id}', name: 'admin_commande_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detailCommande(Commande $commande): Response
    {
        return $this->render('admin/commande_detail.html.twig', ['commande' => $commande]);
    }
}
