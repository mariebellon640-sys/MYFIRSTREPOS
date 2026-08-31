<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Fournisseur;
use App\Entity\LotPeche;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Enum\StatutCommande;
use App\Form\LotPecheType;
use App\Form\ProduitType;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use App\Security\Voter\ProduitVoter;
use App\Service\CommandeService;
use App\Service\FraicheurCalculateur;
use App\Service\StatistiquesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/fournisseur')]
#[IsGranted('ROLE_FOURNISSEUR')]
class FournisseurController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'fournisseur_tableau_de_bord', methods: ['GET'])]
    public function tableauDeBord(StatistiquesService $statistiques): Response
    {
        $fournisseur = $this->fournisseur();

        return $this->render('fournisseur/tableau_de_bord.html.twig', [
            'fournisseur' => $fournisseur,
            'statistiques' => $statistiques->tableauDeBordFournisseur($fournisseur),
        ]);
    }

    #[Route('/produits', name: 'fournisseur_produits', methods: ['GET'])]
    public function produits(ProduitRepository $produitRepository): Response
    {
        return $this->render('fournisseur/produits.html.twig', [
            'produits' => $produitRepository->findParFournisseur($this->fournisseur()),
        ]);
    }

    #[Route('/produits/nouveau', name: 'fournisseur_produit_nouveau', methods: ['GET', 'POST'])]
    public function nouveauProduit(Request $request): Response
    {
        $fournisseur = $this->fournisseur();
        if (!$fournisseur->peutPublier()) {
            $this->addFlash('warning', 'Votre compte doit d\'abord etre valide par un administrateur.');

            return $this->redirectToRoute('fournisseur_produits');
        }

        $produit = new Produit();
        $produit->setFournisseur($fournisseur);

        return $this->editerProduit($request, $produit);
    }

    #[Route('/produits/{id}/modifier', name: 'fournisseur_produit_modifier', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifierProduit(Request $request, Produit $produit): Response
    {
        $this->denyAccessUnlessGranted(ProduitVoter::MODIFIER, $produit);

        return $this->editerProduit($request, $produit);
    }

    #[Route('/produits/{id}/stock', name: 'fournisseur_produit_stock', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function majStock(Request $request, Produit $produit): Response
    {
        $this->denyAccessUnlessGranted(ProduitVoter::MODIFIER, $produit);

        if (!$this->isCsrfTokenValid('stock'.$produit->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $produit->setStockDisponible(number_format((float) $request->request->get('stock', 0), 2, '.', ''));
        $this->em->flush();
        $this->addFlash('success', 'Stock mis a jour.');

        return $this->redirectToRoute('fournisseur_produits');
    }

    /** Declaration d'un arrivage : cree le lot, son code de tracabilite et alimente le stock. */
    #[Route('/produits/{id}/arrivage', name: 'fournisseur_arrivage', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function declararerArrivage(Request $request, Produit $produit, FraicheurCalculateur $fraicheur): Response
    {
        $this->denyAccessUnlessGranted(ProduitVoter::MODIFIER, $produit);

        $lot = new LotPeche();
        $formulaire = $this->createForm(LotPecheType::class, $lot);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $produit->addLot($lot);
            $produit->incrementerStock($lot->getQuantiteKg());
            $fraicheur->actualiser($lot);

            $this->em->persist($lot);
            $this->em->flush();

            $this->addFlash('success', sprintf('Arrivage enregistre sous le code de lot %s.', $lot->getCodeLot()));

            return $this->redirectToRoute('fournisseur_produits');
        }

        return $this->render('fournisseur/arrivage.html.twig', [
            'formulaire' => $formulaire,
            'produit' => $produit,
        ]);
    }

    #[Route('/commandes', name: 'fournisseur_commandes', methods: ['GET'])]
    public function commandes(CommandeRepository $commandeRepository): Response
    {
        return $this->render('fournisseur/commandes.html.twig', [
            'commandes' => $commandeRepository->findPourFournisseur($this->fournisseur()),
        ]);
    }

    #[Route('/commandes/{id}/preparer', name: 'fournisseur_commande_preparer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function preparer(Request $request, \App\Entity\Commande $commande, CommandeService $commandeService): Response
    {
        $this->denyAccessUnlessGranted(\App\Security\Voter\CommandeVoter::PREPARER, $commande);

        if (!$this->isCsrfTokenValid('preparer'.$commande->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $statut = StatutCommande::tryFrom((string) $request->request->get('statut', '')) ?? StatutCommande::EN_PREPARATION;
        $commandeService->changerStatut($commande, $statut);
        $this->addFlash('success', 'Statut de la commande mis a jour.');

        return $this->redirectToRoute('fournisseur_commandes');
    }

    private function editerProduit(Request $request, Produit $produit): Response
    {
        $formulaire = $this->createForm(ProduitType::class, $produit);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            if (null === $produit->getId()) {
                $this->em->persist($produit);
            }
            $this->em->flush();
            $this->addFlash('success', 'Produit enregistre.');

            return $this->redirectToRoute('fournisseur_produits');
        }

        return $this->render('fournisseur/produit_formulaire.html.twig', [
            'formulaire' => $formulaire,
            'produit' => $produit,
        ]);
    }

    private function fournisseur(): Fournisseur
    {
        $utilisateur = $this->getUser();
        \assert($utilisateur instanceof Utilisateur);
        $fournisseur = $utilisateur->getFournisseur();
        if (null === $fournisseur) {
            throw $this->createAccessDeniedException('Aucun profil fournisseur rattache a ce compte.');
        }

        return $fournisseur;
    }
}
