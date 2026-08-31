<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LivraisonPosition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LivraisonPosition>
 */
class LivraisonPositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LivraisonPosition::class);
    }
}
