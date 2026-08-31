<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Livreur;
use App\Entity\ZoneLivraison;
use App\Enum\StatutDisponibilite;
use App\Enum\StatutLivraison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Livreur>
 */
class LivreurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Livreur::class);
    }

    /**
     * Livreurs disponibles, c'est-a-dire sans course en cours (regle de gestion 4),
     * en priorisant ceux affectes a la zone de la commande a livrer.
     *
     * @return list<Livreur>
     */
    public function findDisponibles(?ZoneLivraison $zone = null): array
    {
        $sousRequete = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(liv.livreur)')
            ->from('App\Entity\Livraison', 'liv')
            ->where('liv.livreur IS NOT NULL')
            ->andWhere('liv.statut IN (:enCours)');

        $qb = $this->createQueryBuilder('l')
            ->join('l.utilisateur', 'u')
            ->addSelect('u')
            ->where('l.statutDisponibilite = :disponible')
            ->andWhere('u.estActif = true')
            ->andWhere('l.utilisateur NOT IN ('.$sousRequete->getDQL().')')
            ->setParameter('disponible', StatutDisponibilite::DISPONIBLE)
            ->setParameter('enCours', [StatutLivraison::AFFECTEE, StatutLivraison::EN_COURS]);

        if (null !== $zone) {
            $qb->addSelect('CASE WHEN l.zoneAffectation = :zone THEN 0 ELSE 1 END AS HIDDEN priorite')
                ->setParameter('zone', $zone)
                ->orderBy('priorite', 'ASC');
        }

        /** @var list<Livreur> $resultats */
        $resultats = $qb->addOrderBy('l.noteMoyenne', 'DESC')->getQuery()->getResult();

        return $resultats;
    }
}
