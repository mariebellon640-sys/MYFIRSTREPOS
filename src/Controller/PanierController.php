<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\PanierItem;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Service\PanierService;
use App\Service\StockInsuffisantException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/panier')]
#[IsGranted('ROLE_CLIENT')]
class PanierController extends AbstractController
{
    public function __construct(private readonly PanierService $panierService)
    {
    }

    #[Route('', name: 'app_panier', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('panier/index.html.twig', [
            'panier' => $this->panierService->obtenirPanier($this->client()),
        ]);
    }

    #[Route('/ajouter/{id}', name: 'app_panier_ajouter', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function ajouter(Request $request, Produit $produit): Response
    {
        if (!$this->isCsrfTokenValid('panier_ajouter'.$produit->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $quantite = number_format((float) $request->request->get('quantite', 1), 2, '.', '');
        $preparation = $request->request->get('preparation');

        try {
            $this->panierService->ajouterProduit($this->client(), $produit, $quantite, '' === $preparation ? null : $preparation);
            $this->addFlash('success', sprintf('%s ajoute au panier.', $produit->getNom()));
        } catch (StockInsuffisantException $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/item/{id}/quantite', name: 'app_panier_quantite', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function modifierQuantite(Request $request, PanierItem $item): Response
    {
        $this->verifierProprietaire($item);

        if (!$this->isCsrfTokenValid('panier_item'.$item->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $this->panierService->modifierQuantite($item, number_format((float) $request->request->get('quantite', 0), 2, '.', ''));
        } catch (StockInsuffisantException $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/item/{id}/retirer', name: 'app_panier_retirer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function retirer(Request $request, PanierItem $item): Response
    {
        $this->verifierProprietaire($item);

        if (!$this->isCsrfTokenValid('panier_item'.$item->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $this->panierService->retirerItem($item);
        $this->addFlash('info', 'Article retire du panier.');

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/vider', name: 'app_panier_vider', methods: ['POST'])]
    public function vider(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('panier_vider', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $this->panierService->vider($this->panierService->obtenirPanier($this->client()));
        $this->addFlash('info', 'Panier vide.');

        return $this->redirectToRoute('app_panier');
    }

    private function verifierProprietaire(PanierItem $item): void
    {
        $panier = $item->getPanier();
        if (null === $panier || $panier->getClient()->getId() !== $this->client()->getId()) {
            throw $this->createAccessDeniedException('Cet article ne vous appartient pas.');
        }
    }

    private function client(): \App\Entity\Client
    {
        $utilisateur = $this->getUser();
        \assert($utilisateur instanceof Utilisateur);
        $client = $utilisateur->getClient();
        if (null === $client) {
            throw $this->createAccessDeniedException('Aucun profil client rattache a ce compte.');
        }

        return $client;
    }
}
