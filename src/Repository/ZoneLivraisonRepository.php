<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ZoneLivraison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ZoneLivraison>
 */
class ZoneLivraisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZoneLivraison::class);
    }

    /** @return list<ZoneLivraison> */
    public function findActives(): array
    {
        /** @var list<ZoneLivraison> $zones */
        $zones = $this->createQueryBuilder('z')
            ->where('z.estActive = true')
            ->orderBy('z.nomZone', 'ASC')
            ->getQuery()
            ->getResult();

        return $zones;
    }
}
