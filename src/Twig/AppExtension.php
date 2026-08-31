<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Utilisateur;
use App\Repository\NotificationRepository;
use App\Repository\PanierRepository;
use App\Service\FraicheurCalculateur;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly PanierRepository $panierRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly FraicheurCalculateur $fraicheur,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('ariary', $this->formaterAriary(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('nombre_articles_panier', $this->nombreArticlesPanier(...)),
            new TwigFunction('notifications_non_lues', $this->notificationsNonLues(...)),
            new TwigFunction('libelle_fraicheur', $this->fraicheur->libelle(...)),
            new TwigFunction('couleur_fraicheur', $this->fraicheur->couleur(...)),
            new TwigFunction('url_qr_code', $this->urlQrCode(...)),
        ];
    }

    public function formaterAriary(string|float|int|null $montant): string
    {
        return number_format((float) $montant, 0, ',', ' ').' Ar';
    }

    public function nombreArticlesPanier(): int
    {
        $utilisateur = $this->security->getUser();
        if (!$utilisateur instanceof Utilisateur || null === $utilisateur->getClient()) {
            return 0;
        }

        return $this->panierRepository->findPourClient($utilisateur->getClient())?->getNombreArticles() ?? 0;
    }

    public function notificationsNonLues(): int
    {
        $utilisateur = $this->security->getUser();

        return $utilisateur instanceof Utilisateur ? $this->notificationRepository->compterNonLues($utilisateur) : 0;
    }

    /** QR code de tracabilite genere par un service public sans cle d'API. */
    public function urlQrCode(string $contenu, int $taille = 180): string
    {
        return sprintf('https://api.qrserver.com/v1/create-qr-code/?size=%dx%d&data=%s', $taille, $taille, rawurlencode($contenu));
    }
}
