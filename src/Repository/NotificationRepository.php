<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /** @return list<Notification> */
    public function findPourUtilisateur(Utilisateur $utilisateur, int $limite = 20): array
    {
        /** @var list<Notification> $notifications */
        $notifications = $this->createQueryBuilder('n')
            ->where('n.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('n.dateEnvoi', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();

        return $notifications;
    }

    public function compterNonLues(Utilisateur $utilisateur): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.utilisateur = :utilisateur')
            ->andWhere('n.statutLecture = false')
            ->setParameter('utilisateur', $utilisateur)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
