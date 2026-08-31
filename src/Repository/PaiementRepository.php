<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Paiement;
use App\Enum\StatutPaiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Paiement>
 */
class PaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }

    /** @return list<Paiement> */
    public function findRecents(int $limite = 30): array
    {
        /** @var list<Paiement> $paiements */
        $paiements = $this->createQueryBuilder('p')
            ->join('p.commande', 'c')->addSelect('c')
            ->orderBy('p.dateCreation', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();

        return $paiements;
    }

    /** @return array<string, string> */
    public function totauxParMode(): array
    {
        /** @var list<array{mode: \App\Enum\ModePaiement, total: string}> $lignes */
        $lignes = $this->createQueryBuilder('p')
            ->select('p.modePaiement AS mode, SUM(p.montant) AS total')
            ->where('p.statut = :confirme')
            ->setParameter('confirme', StatutPaiement::CONFIRME)
            ->groupBy('p.modePaiement')
            ->getQuery()
            ->getResult();

        $totaux = [];
        foreach ($lignes as $ligne) {
            $totaux[$ligne['mode']->value] = number_format((float) $ligne['total'], 2, '.', '');
        }

        return $totaux;
    }
}
