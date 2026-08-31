<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Utilisateur;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Determine la langue d'affichage : choix explicite en session, sinon langue
 * preferee du compte, sinon francais.
 */
final readonly class LocaleSubscriber implements EventSubscriberInterface
{
    public const CLE_SESSION = '_locale';

    public function __construct(private Security $security)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 15]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $requete = $event->getRequest();

        if (!$requete->hasPreviousSession()) {
            return;
        }

        $locale = $requete->getSession()->get(self::CLE_SESSION);

        if (!\is_string($locale)) {
            $utilisateur = $this->security->getUser();
            $locale = $utilisateur instanceof Utilisateur
                ? $utilisateur->getLanguePreferee()->locale()
                : null;
        }

        if (\in_array($locale, ['fr', 'mg'], true)) {
            $requete->setLocale($locale);
        }
    }
}
