<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CategorieProduitRepository;
use App\Repository\ProduitRepository;
use App\Repository\ZoneLivraisonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccueilController extends AbstractController
{
    #[Route('/', name: 'app_accueil', methods: ['GET'])]
    public function index(
        ProduitRepository $produitRepository,
        CategorieProduitRepository $categorieRepository,
        ZoneLivraisonRepository $zoneRepository,
    ): Response {
        return $this->render('accueil/index.html.twig', [
            'arrivages' => $produitRepository->findCatalogue(['enStock' => true, 'tri' => 'fraicheur'], 8),
            'categories' => $categorieRepository->findBy(['categorieParent' => null], ['nom' => 'ASC']),
            'zones' => $zoneRepository->findActives(),
        ]);
    }

    #[Route('/mentions-legales', name: 'app_mentions_legales', methods: ['GET'])]
    public function mentionsLegales(): Response
    {
        return $this->render('accueil/mentions_legales.html.twig');
    }
}
