<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Utilisateur;
use App\Enum\CanalNotification;
use App\Enum\TypeNotification;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Verification du compte par code a usage unique envoye par e-mail et SMS.
 */
class OtpService
{
    private const DUREE_VALIDITE = '+10 minutes';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationService $notificationService,
    ) {
    }

    public function envoyerCode(Utilisateur $utilisateur): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', \STR_PAD_LEFT);
        $utilisateur->setCodeOtp($code, new \DateTimeImmutable(self::DUREE_VALIDITE));
        $this->em->flush();

        $this->notificationService->notifier(
            $utilisateur,
            TypeNotification::SYSTEME,
            'Votre code de verification POISSON VIP',
            sprintf('Votre code de verification est %s. Il expire dans 10 minutes.', $code),
            [CanalNotification::EMAIL, CanalNotification::SMS],
        );

        return $code;
    }

    public function verifier(Utilisateur $utilisateur, string $code): bool
    {
        $attendu = $utilisateur->getCodeOtp();
        $expiration = $utilisateur->getCodeOtpExpireLe();

        if (null === $attendu || null === $expiration || $expiration < new \DateTimeImmutable()) {
            return false;
        }

        if (!hash_equals($attendu, $code)) {
            return false;
        }

        $utilisateur->setEstVerifie(true);
        $utilisateur->setCodeOtp(null);
        $this->em->flush();

        return true;
    }
}
