<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Form\CommandeType;
use App\Form\LitigeType;
use App\Form\Model\CommandeModel;
use App\Entity\Litige;
use App\Repository\CodePromoRepository;
use App\Repository\CommandeRepository;
use App\Security\Voter\CommandeVoter;
use App\Service\CommandeService;
use App\Service\PaiementService;
use App\Service\PanierService;
use App\Service\StockInsuffisantException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/commande')]
#[IsGranted('ROLE_CLIENT')]
class CommandeController extends AbstractController
{
    public function __construct(
        private readonly CommandeService $commandeService,
        private readonly PanierService $panierService,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/valider', name: 'app_commande_valider', methods: ['GET', 'POST'])]
    public function valider(
        Request $request,
        CodePromoRepository $codePromoRepository,
        PaiementService $paiementService,
    ): Response {
        $client = $this->client();
        $panier = $this->panierService->obtenirPanier($client);

        if ($panier->estVide()) {
            $this->addFlash('info', 'Votre panier est vide.');

            return $this->redirectToRoute('app_catalogue');
        }

        if (0 === \count($client->getUtilisateur()->getAdresses())) {
            $this->addFlash('info', 'Ajoutez d\'abord une adresse de livraison.');

            return $this->redirectToRoute('app_compte_adresse_nouvelle');
        }

        $donnees = new CommandeModel();
        $donnees->adresseLivraison = $client->getUtilisateur()->getAdressePrincipale();
        $formulaire = $this->createForm(CommandeType::class, $donnees, ['client' => $client]);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $codePromo = null;
            if (null !== $donnees->codePromo && '' !== $donnees->codePromo) {
                $codePromo = $codePromoRepository->findOneBy(['code' => strtoupper($donnees->codePromo)]);
                if (null === $codePromo || !$codePromo->estUtilisable()) {
                    $this->addFlash('warning', 'Code promo invalide ou expire : il n\'a pas ete applique.');
                    $codePromo = null;
                }
            }

            try {
                \assert(null !== $donnees->adresseLivraison);
                $commande = $this->commandeService->creerDepuisPanier(
                    $panier,
                    $donnees->adresseLivraison,
                    $donnees->modePaiement,
                    $donnees->creneauLivraison,
                    $codePromo,
                );
            } catch (StockInsuffisantException|\DomainException $exception) {
                $this->addFlash('danger', $exception->getMessage());

                return $this->redirectToRoute('app_panier');
            }

            $paiement = $commande->getPaiement();
            \assert(null !== $paiement);

            if ($donnees->modePaiement->estEnLigne()) {
                $paiementService->initier($paiement, $donnees->numeroPayeur);

                return $this->redirectToRoute('app_commande_paiement', ['numero' => $commande->getNumeroCommande()]);
            }

            $this->commandeService->confirmerApresPaiement($commande);

            return $this->redirectToRoute('app_commande_confirmation', ['numero' => $commande->getNumeroCommande()]);
        }

        return $this->render('commande/valider.html.twig', [
            'formulaire' => $formulaire,
            'panier' => $panier,
        ]);
    }

    /** Ecran de paiement Mobile Money : validation du code recu sur le telephone du payeur. */
    #[Route('/{numero}/paiement', name: 'app_commande_paiement', methods: ['GET', 'POST'])]
    public function paiement(Request $request, string $numero, PaiementService $paiementService): Response
    {
        $commande = $this->commandeParNumero($numero);
        $paiement = $commande->getPaiement();
        \assert(null !== $paiement);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('paiement'.$commande->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            if ($request->request->has('annuler')) {
                $paiementService->echouer($paiement, 'Abandon du payeur');
                $this->commandeService->annuler($commande);

                return $this->redirectToRoute('app_commande_historique');
            }

            if ($paiementService->confirmer($paiement)) {
                $this->commandeService->confirmerApresPaiement($commande);

                return $this->redirectToRoute('app_commande_confirmation', ['numero' => $commande->getNumeroCommande()]);
            }

            $this->addFlash('danger', 'Le paiement n\'a pas pu etre confirme par la passerelle.');
        }

        return $this->render('commande/paiement.html.twig', [
            'commande' => $commande,
            'paiement' => $paiement,
        ]);
    }

    #[Route('/{numero}/confirmation', name: 'app_commande_confirmation', methods: ['GET'])]
    public function confirmation(string $numero): Response
    {
        return $this->render('commande/confirmation.html.twig', ['commande' => $this->commandeParNumero($numero)]);
    }

    #[Route('/historique', name: 'app_commande_historique', methods: ['GET'])]
    public function historique(CommandeRepository $commandeRepository): Response
    {
        return $this->render('commande/historique.html.twig', [
            'commandes' => $commandeRepository->findPourClient($this->client()),
        ]);
    }

    #[Route('/{numero}', name: 'app_commande_detail', methods: ['GET'])]
    public function detail(string $numero): Response
    {
        $commande = $this->commandeParNumero($numero);
        $this->denyAccessUnlessGranted(CommandeVoter::VOIR, $commande);

        return $this->render('commande/detail.html.twig', ['commande' => $commande]);
    }

    /** Facture / recu electronique imprimable. */
    #[Route('/{numero}/facture', name: 'app_commande_facture', methods: ['GET'])]
    public function facture(string $numero): Response
    {
        $commande = $this->commandeParNumero($numero);
        $this->denyAccessUnlessGranted(CommandeVoter::VOIR, $commande);

        return $this->render('commande/facture.html.twig', ['commande' => $commande]);
    }

    #[Route('/{numero}/annuler', name: 'app_commande_annuler', methods: ['POST'])]
    public function annuler(Request $request, string $numero): Response
    {
        $commande = $this->commandeParNumero($numero);

        if (!$this->isCsrfTokenValid('annuler'.$commande->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $this->commandeService->annulerParClient($commande, $this->client());
            $this->addFlash('success', 'Commande annulee.');
        } catch (\DomainException $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_commande_historique');
    }

    #[Route('/{numero}/recommander', name: 'app_commande_recommander', methods: ['POST'])]
    public function recommander(Request $request, string $numero): Response
    {
        $commande = $this->commandeParNumero($numero);
        $this->denyAccessUnlessGranted(CommandeVoter::VOIR, $commande);

        if (!$this->isCsrfTokenValid('recommander'.$commande->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $this->commandeService->recommander($commande, $this->client());
        $this->addFlash('success', 'Les articles encore disponibles ont ete replaces dans votre panier.');

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/{numero}/litige', name: 'app_commande_litige', methods: ['GET', 'POST'])]
    public function ouvrirLitige(Request $request, string $numero): Response
    {
        $commande = $this->commandeParNumero($numero);
        $this->denyAccessUnlessGranted(CommandeVoter::VOIR, $commande);

        $litige = new Litige($commande, $this->client());
        $formulaire = $this->createForm(LitigeType::class, $litige);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $this->em->persist($litige);
            $this->em->flush();
            $this->addFlash('success', 'Reclamation transmise au support POISSON VIP.');

            return $this->redirectToRoute('app_commande_detail', ['numero' => $numero]);
        }

        return $this->render('commande/litige.html.twig', [
            'formulaire' => $formulaire,
            'commande' => $commande,
        ]);
    }

    private function commandeParNumero(string $numero): Commande
    {
        $commande = $this->em->getRepository(Commande::class)->findOneBy(['numeroCommande' => $numero]);
        if (null === $commande) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        return $commande;
    }

    private function client(): Client
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
