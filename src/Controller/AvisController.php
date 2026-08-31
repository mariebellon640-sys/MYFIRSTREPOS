<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Enum\StatutCommande;
use App\Form\AvisType;
use App\Security\Voter\CommandeVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/avis')]
#[IsGranted('ROLE_CLIENT')]
class AvisController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /** Un avis n'est possible qu'une fois la commande livree. */
    #[Route('/commande/{id}/deposer', name: 'app_avis_deposer', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function deposer(Request $request, Commande $commande): Response
    {
        $this->denyAccessUnlessGranted(CommandeVoter::VOIR, $commande);

        if (StatutCommande::LIVREE !== $commande->getStatut()) {
            $this->addFlash('warning', 'Vous pourrez deposer un avis une fois la commande livree.');

            return $this->redirectToRoute('app_commande_detail', ['numero' => $commande->getNumeroCommande()]);
        }

        $utilisateur = $this->getUser();
        \assert($utilisateur instanceof Utilisateur);
        $client = $utilisateur->getClient();
        \assert(null !== $client);

        $avis = new Avis($client, $commande);
        $produitId = $request->query->getInt('produit');
        foreach ($commande->getLignes() as $ligne) {
            if ($ligne->getProduit()->getId() === $produitId) {
                $avis->setProduit($ligne->getProduit());
                $avis->setFournisseur($ligne->getFournisseur());
            }
        }
        $avis->setLivreur($commande->getLivraison()?->getLivreur());

        $formulaire = $this->createForm(AvisType::class, $avis);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $this->em->persist($avis);
            $this->em->flush();
            $this->addFlash('success', 'Merci : votre avis sera publie apres moderation.');

            return $this->redirectToRoute('app_commande_detail', ['numero' => $commande->getNumeroCommande()]);
        }

        return $this->render('avis/deposer.html.twig', [
            'formulaire' => $formulaire,
            'commande' => $commande,
        ]);
    }

    #[Route('/{id}/signaler', name: 'app_avis_signaler', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function signaler(Request $request, Avis $avis): Response
    {
        if (!$this->isCsrfTokenValid('signaler'.$avis->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $avis->signaler((string) $request->request->get('motif', 'Contenu inapproprie'));
        $this->em->flush();
        $this->addFlash('info', 'Avis signale au support : merci de votre vigilance.');

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_catalogue'));
    }
}
