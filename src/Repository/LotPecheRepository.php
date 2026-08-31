<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LotPeche;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LotPeche>
 */
class LotPecheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LotPeche::class);
    }

    /** @return list<LotPeche> */
    public function findActifs(): array
    {
        /** @var list<LotPeche> $lots */
        $lots = $this->createQueryBuilder('l')
            ->join('l.produit', 'p')
            ->addSelect('p')
            ->where('l.estRetire = false')
            ->getQuery()
            ->getResult();

        return $lots;
    }

    public function findParCodeLot(string $codeLot): ?LotPeche
    {
        return $this->findOneBy(['codeLot' => $codeLot]);
    }
}
