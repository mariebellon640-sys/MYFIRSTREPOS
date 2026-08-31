<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Utilisateur;
use App\Enum\Role;
use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Connexion par e-mail ou par numero de telephone, avec redirection vers
 * l'espace correspondant au role de l'utilisateur.
 */
class ConnexionAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const ROUTE_CONNEXION = 'app_connexion';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UtilisateurRepository $utilisateurRepository,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $identifiant = trim((string) $request->request->get('identifiant', ''));
        $motDePasse = (string) $request->request->get('mot_de_passe', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $identifiant);

        return new Passport(
            new UserBadge($identifiant, function (string $identifiant): Utilisateur {
                $utilisateur = $this->utilisateurRepository->findParEmailOuTelephone($identifiant);
                if (null === $utilisateur) {
                    throw new UserNotFoundException();
                }
                if (!$utilisateur->isEstActif()) {
                    throw new CustomUserMessageAuthenticationException('Ce compte est desactive. Contactez le support POISSON VIP.');
                }
                if (!$utilisateur->isEstVerifie()) {
                    throw new CustomUserMessageAuthenticationException('Votre compte n\'est pas encore verifie : saisissez le code OTP recu.');
                }

                return $utilisateur;
            }),
            new PasswordCredentials($motDePasse),
            [
                new CsrfTokenBadge('authenticate', (string) $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $cible = $this->getTargetPath($request->getSession(), $firewallName);
        if (null !== $cible) {
            return new RedirectResponse($cible);
        }

        $utilisateur = $token->getUser();
        \assert($utilisateur instanceof Utilisateur);

        return new RedirectResponse($this->urlGenerator->generate(self::routeAccueil($utilisateur->getRole())));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->getLoginUrl($request));
    }

    public static function routeAccueil(Role $role): string
    {
        return match ($role) {
            Role::ADMIN => 'admin_tableau_de_bord',
            Role::SUPPORT => 'support_litiges',
            Role::FOURNISSEUR => 'fournisseur_tableau_de_bord',
            Role::LIVREUR => 'livreur_courses',
            Role::CLIENT => 'app_catalogue',
        };
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::ROUTE_CONNEXION);
    }
}
