<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Avis;
use App\Entity\Fournisseur;
use App\Entity\Livreur;
use App\Entity\Produit;
use App\Enum\StatutModeration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avis>
 */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    /** @return list<Avis> */
    public function findPubliesPourProduit(Produit $produit): array
    {
        /** @var list<Avis> $avis */
        $avis = $this->createQueryBuilder('a')
            ->join('a.client', 'c')->addSelect('c')
            ->join('c.utilisateur', 'u')->addSelect('u')
            ->where('a.produit = :produit')
            ->andWhere('a.statutModeration = :publie')
            ->setParameter('produit', $produit)
            ->setParameter('publie', StatutModeration::PUBLIE)
            ->orderBy('a.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();

        return $avis;
    }

    /** @return list<Avis> */
    public function findAModerer(): array
    {
        /** @var list<Avis> $avis */
        $avis = $this->createQueryBuilder('a')
            ->join('a.client', 'c')->addSelect('c')
            ->join('c.utilisateur', 'u')->addSelect('u')
            ->where('a.statutModeration = :attente OR a.estSignale = true')
            ->setParameter('attente', StatutModeration::EN_ATTENTE)
            ->orderBy('a.dateCreation', 'ASC')
            ->getQuery()
            ->getResult();

        return $avis;
    }

    public function noteMoyenneFournisseur(Fournisseur $fournisseur): ?float
    {
        $moyenne = $this->createQueryBuilder('a')
            ->select('AVG(a.note)')
            ->where('a.fournisseur = :fournisseur')
            ->andWhere('a.statutModeration = :publie')
            ->setParameter('fournisseur', $fournisseur)
            ->setParameter('publie', StatutModeration::PUBLIE)
            ->getQuery()
            ->getSingleScalarResult();

        return null === $moyenne ? null : round((float) $moyenne, 1);
    }

    public function noteMoyenneLivreur(Livreur $livreur): ?float
    {
        $moyenne = $this->createQueryBuilder('a')
            ->select('AVG(a.note)')
            ->where('a.livreur = :livreur')
            ->andWhere('a.statutModeration = :publie')
            ->setParameter('livreur', $livreur)
            ->setParameter('publie', StatutModeration::PUBLIE)
            ->getQuery()
            ->getSingleScalarResult();

        return null === $moyenne ? null : round((float) $moyenne, 1);
    }
}
