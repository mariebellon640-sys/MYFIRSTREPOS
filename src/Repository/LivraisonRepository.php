<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Livraison;
use App\Entity\Livreur;
use App\Enum\StatutLivraison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Livraison>
 */
class LivraisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Livraison::class);
    }

    /** @return list<Livraison> */
    public function findEnAttenteAffectation(): array
    {
        /** @var list<Livraison> $livraisons */
        $livraisons = $this->createQueryBuilder('l')
            ->join('l.commande', 'c')->addSelect('c')
            ->join('c.zoneLivraison', 'z')->addSelect('z')
            ->where('l.statut = :statut')
            ->setParameter('statut', StatutLivraison::EN_ATTENTE_AFFECTATION)
            ->orderBy('c.dateCommande', 'ASC')
            ->getQuery()
            ->getResult();

        return $livraisons;
    }

    /** @return list<Livraison> */
    public function findPourLivreur(Livreur $livreur, bool $seulementActives = false): array
    {
        $qb = $this->createQueryBuilder('l')
            ->join('l.commande', 'c')->addSelect('c')
            ->join('c.adresseLivraison', 'a')->addSelect('a')
            ->join('c.zoneLivraison', 'z')->addSelect('z')
            ->where('l.livreur = :livreur')
            ->setParameter('livreur', $livreur)
            ->orderBy('l.dateAffectation', 'DESC');

        if ($seulementActives) {
            $qb->andWhere('l.statut IN (:actives)')
                ->setParameter('actives', [StatutLivraison::AFFECTEE, StatutLivraison::EN_COURS]);
        }

        /** @var list<Livraison> $livraisons */
        $livraisons = $qb->getQuery()->getResult();

        return $livraisons;
    }

    /** Regle de gestion 4 : un livreur ne peut cumuler deux courses non cloturees. */
    public function aUneCourseEnCours(Livreur $livreur): bool
    {
        return 0 < (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.livreur = :livreur')
            ->andWhere('l.statut IN (:actives)')
            ->setParameter('livreur', $livreur)
            ->setParameter('actives', [StatutLivraison::AFFECTEE, StatutLivraison::EN_COURS])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
