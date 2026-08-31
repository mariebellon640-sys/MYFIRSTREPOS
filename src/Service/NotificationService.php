<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Utilisateur;
use App\Enum\CanalNotification;
use App\Enum\TypeNotification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;

/**
 * Notifications multicanal : la notification interne (push web) est toujours
 * persistee ; l'e-mail est envoye en complement lorsqu'il est demande.
 */
class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @param list<CanalNotification> $canaux */
    public function notifier(
        Utilisateur $destinataire,
        TypeNotification $type,
        string $titre,
        string $contenu,
        array $canaux = [CanalNotification::PUSH],
    ): void {
        foreach ($canaux as $canal) {
            $notification = new Notification($destinataire, $type, $titre, $contenu, $canal);
            $this->em->persist($notification);

            if (CanalNotification::EMAIL === $canal) {
                $this->envoyerEmail($destinataire, $titre, $contenu);
            }
        }

        $this->em->flush();
    }

    public function marquerToutesCommeLues(Utilisateur $utilisateur): void
    {
        foreach ($utilisateur->getNotifications() as $notification) {
            $notification->marquerCommeLue();
        }
        $this->em->flush();
    }

    private function envoyerEmail(Utilisateur $destinataire, string $titre, string $contenu): void
    {
        $email = (new TemplatedEmail())
            ->from('no-reply@poissonvip.mg')
            ->to($destinataire->getEmail())
            ->subject('[POISSON VIP] '.$titre)
            ->htmlTemplate('email/notification.html.twig')
            ->context(['titre' => $titre, 'contenu' => $contenu, 'destinataire' => $destinataire]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Envoi d\'e-mail impossible', ['erreur' => $exception->getMessage()]);
        }
    }
}
