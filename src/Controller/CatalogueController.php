<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Repository\AvisRepository;
use App\Repository\CategorieProduitRepository;
use App\Repository\FournisseurRepository;
use App\Repository\LotPecheRepository;
use App\Repository\ProduitRepository;
use App\Service\FraicheurCalculateur;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CatalogueController extends AbstractController
{
    #[Route('/catalogue', name: 'app_catalogue', methods: ['GET'])]
    public function catalogue(
        Request $request,
        ProduitRepository $produitRepository,
        CategorieProduitRepository $categorieRepository,
        FournisseurRepository $fournisseurRepository,
        PaginatorInterface $paginator,
    ): Response {
        $filtres = [
            'recherche' => $request->query->get('recherche'),
            'categorie' => $request->query->getInt('categorie') ?: null,
            'fournisseur' => $request->query->getInt('fournisseur') ?: null,
            'prixMin' => $request->query->get('prixMin'),
            'prixMax' => $request->query->get('prixMax'),
            'fraicheurMin' => $request->query->get('fraicheurMin'),
            'enStock' => $request->query->getBoolean('enStock'),
            'tri' => $request->query->get('tri'),
        ];

        $pagination = $paginator->paginate(
            $produitRepository->creerRequeteCatalogue($filtres),
            $request->query->getInt('page', 1),
            12,
        );

        $utilisateur = $this->getUser();
        $recommandations = [];
        if ($utilisateur instanceof Utilisateur && null !== $utilisateur->getClient()) {
            $recommandations = $produitRepository->findRecommandationsPourClient((int) $utilisateur->getClient()->getId());
        }

        return $this->render('catalogue/index.html.twig', [
            'pagination' => $pagination,
            'filtres' => $filtres,
            'categories' => $categorieRepository->findBy([], ['nom' => 'ASC']),
            'fournisseurs' => $fournisseurRepository->findValides(),
            'recommandations' => $recommandations,
        ]);
    }

    #[Route('/produit/{id}', name: 'app_produit', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function produit(Produit $produit, AvisRepository $avisRepository, FraicheurCalculateur $fraicheur): Response
    {
        return $this->render('catalogue/produit.html.twig', [
            'produit' => $produit,
            'lot' => $produit->getLotCourant(),
            'avis' => $avisRepository->findPubliesPourProduit($produit),
            'indice' => $produit->getIndiceFraicheur(),
            'fraicheur' => $fraicheur,
        ]);
    }

    /** Page de tracabilite atteignable par le QR code appose sur le colis. */
    #[Route('/tracabilite/{codeLot}', name: 'app_tracabilite', methods: ['GET'])]
    public function tracabilite(string $codeLot, LotPecheRepository $lotRepository, FraicheurCalculateur $fraicheur): Response
    {
        $lot = $lotRepository->findParCodeLot($codeLot);
        if (null === $lot) {
            throw $this->createNotFoundException('Lot de peche introuvable.');
        }

        return $this->render('catalogue/tracabilite.html.twig', [
            'lot' => $lot,
            'indice' => $fraicheur->calculerIndice($lot),
            'fraicheur' => $fraicheur,
        ]);
    }
}
