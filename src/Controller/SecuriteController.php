<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Fournisseur;
use App\Entity\Livreur;
use App\Entity\Utilisateur;
use App\Enum\Role;
use App\Form\InscriptionType;
use App\Form\Model\InscriptionModel;
use App\Repository\UtilisateurRepository;
use App\Security\OtpService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecuriteController extends AbstractController
{
    #[Route('/connexion', name: 'app_connexion', methods: ['GET', 'POST'])]
    public function connexion(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof Utilisateur) {
            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('securite/connexion.html.twig', [
            'derniere_saisie' => $authenticationUtils->getLastUsername(),
            'erreur' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/deconnexion', name: 'app_deconnexion', methods: ['GET'])]
    public function deconnexion(): never
    {
        throw new \LogicException('Cette methode est interceptee par le pare-feu de securite.');
    }

    #[Route('/inscription', name: 'app_inscription', methods: ['GET', 'POST'])]
    public function inscription(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UtilisateurRepository $utilisateurRepository,
        OtpService $otpService,
    ): Response {
        $donnees = new InscriptionModel();
        $formulaire = $this->createForm(InscriptionType::class, $donnees);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            if (null !== $utilisateurRepository->findParEmailOuTelephone($donnees->email)
                || null !== $utilisateurRepository->findParEmailOuTelephone($donnees->telephone)) {
                $this->addFlash('danger', 'Un compte existe deja avec cet e-mail ou ce numero de telephone.');

                return $this->render('securite/inscription.html.twig', ['formulaire' => $formulaire]);
            }

            $utilisateur = new Utilisateur();
            $utilisateur
                ->setNom($donnees->nom)
                ->setPrenom($donnees->prenom)
                ->setEmail(strtolower($donnees->email))
                ->setTelephone($donnees->telephone)
                ->setRole($donnees->role)
                ->setMotDePasse($hasher->hashPassword($utilisateur, $donnees->motDePasse));

            $em->persist($utilisateur);

            switch ($donnees->role) {
                case Role::FOURNISSEUR:
                    $fournisseur = new Fournisseur($utilisateur);
                    $fournisseur->setNomCommercial($donnees->nomCommercial ?? $utilisateur->getNomComplet());
                    if (null !== $donnees->typeFournisseur) {
                        $fournisseur->setTypeFournisseur($donnees->typeFournisseur);
                    }
                    $em->persist($fournisseur);
                    $this->addFlash('info', 'Votre compte fournisseur sera actif des sa validation par un administrateur.');
                    break;
                case Role::LIVREUR:
                    $livreur = new Livreur($utilisateur);
                    if (null !== $donnees->typeVehicule) {
                        $livreur->setTypeVehicule($donnees->typeVehicule);
                    }
                    $livreur->setNumeroPermis($donnees->numeroPermis);
                    $em->persist($livreur);
                    break;
                default:
                    $em->persist(new Client($utilisateur));
            }

            $em->flush();
            $otpService->envoyerCode($utilisateur);

            $this->addFlash('success', 'Compte cree. Un code de verification vient de vous etre envoye.');

            return $this->redirectToRoute('app_verification_otp', ['identifiant' => $utilisateur->getEmail()]);
        }

        return $this->render('securite/inscription.html.twig', ['formulaire' => $formulaire]);
    }

    #[Route('/verification-otp', name: 'app_verification_otp', methods: ['GET', 'POST'])]
    public function verificationOtp(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        OtpService $otpService,
    ): Response {
        $identifiant = (string) $request->query->get('identifiant', $request->request->get('identifiant', ''));
        $utilisateur = '' === $identifiant ? null : $utilisateurRepository->findParEmailOuTelephone($identifiant);

        if ($request->isMethod('POST') && null !== $utilisateur) {
            if ($request->request->has('renvoyer')) {
                $otpService->envoyerCode($utilisateur);
                $this->addFlash('info', 'Un nouveau code vient de vous etre envoye.');
            } elseif ($otpService->verifier($utilisateur, (string) $request->request->get('code', ''))) {
                $this->addFlash('success', 'Compte verifie : vous pouvez vous connecter.');

                return $this->redirectToRoute('app_connexion');
            } else {
                $this->addFlash('danger', 'Code invalide ou expire.');
            }
        }

        return $this->render('securite/verification_otp.html.twig', [
            'identifiant' => $identifiant,
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/mot-de-passe/oublie', name: 'app_mot_de_passe_oublie', methods: ['GET', 'POST'])]
    public function motDePasseOublie(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $em,
        OtpService $otpService,
    ): Response {
        if ($request->isMethod('POST')) {
            $utilisateur = $utilisateurRepository->findParEmailOuTelephone((string) $request->request->get('identifiant', ''));
            if (null !== $utilisateur) {
                $utilisateur->setJetonReinitialisation(bin2hex(random_bytes(32)), new \DateTimeImmutable('+1 hour'));
                $em->flush();
                $otpService->envoyerCode($utilisateur);
            }

            $this->addFlash('info', 'Si ce compte existe, un code de reinitialisation vient d\'etre envoye.');

            return $this->redirectToRoute('app_mot_de_passe_reinitialiser');
        }

        return $this->render('securite/mot_de_passe_oublie.html.twig');
    }

    #[Route('/mot-de-passe/reinitialiser', name: 'app_mot_de_passe_reinitialiser', methods: ['GET', 'POST'])]
    public function reinitialiser(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        OtpService $otpService,
    ): Response {
        if ($request->isMethod('POST')) {
            $utilisateur = $utilisateurRepository->findParEmailOuTelephone((string) $request->request->get('identifiant', ''));
            $code = (string) $request->request->get('code', '');
            $nouveauMotDePasse = (string) $request->request->get('mot_de_passe', '');

            if (null !== $utilisateur && \strlen($nouveauMotDePasse) >= 8 && $otpService->verifier($utilisateur, $code)) {
                $utilisateur->setMotDePasse($hasher->hashPassword($utilisateur, $nouveauMotDePasse));
                $utilisateur->setJetonReinitialisation(null);
                $em->flush();

                $this->addFlash('success', 'Mot de passe reinitialise.');

                return $this->redirectToRoute('app_connexion');
            }

            $this->addFlash('danger', 'Code invalide, expire, ou mot de passe trop court (8 caracteres minimum).');
        }

        return $this->render('securite/mot_de_passe_reinitialiser.html.twig');
    }
}
