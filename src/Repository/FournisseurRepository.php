<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Fournisseur;
use App\Enum\StatutValidation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Fournisseur>
 */
class FournisseurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fournisseur::class);
    }

    /** @return list<Fournisseur> */
    public function findParStatut(StatutValidation $statut): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.utilisateur', 'u')
            ->addSelect('u')
            ->where('f.statutValidation = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('f.nomCommercial', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Fournisseur> */
    public function findValides(): array
    {
        return $this->findParStatut(StatutValidation::VALIDE);
    }

    /**
     * Chiffre d'affaires par fournisseur sur les commandes livrees.
     *
     * @return list<array{fournisseur: Fournisseur, nombreCommandes: int, chiffreAffaires: string}>
     */
    public function statistiquesChiffreAffaires(): array
    {
        /** @var list<array{fournisseur: Fournisseur, nombreCommandes: int, chiffreAffaires: string}> $lignes */
        $lignes = $this->createQueryBuilder('f')
            ->select('f AS fournisseur, COUNT(DISTINCT lc.commande) AS nombreCommandes, SUM(lc.sousTotal) AS chiffreAffaires')
            ->join('App\Entity\LigneCommande', 'lc', 'WITH', 'lc.fournisseur = f')
            ->join('lc.commande', 'c')
            ->where('c.statut = :livree')
            ->setParameter('livree', \App\Enum\StatutCommande::LIVREE)
            ->groupBy('f')
            ->orderBy('chiffreAffaires', 'DESC')
            ->getQuery()
            ->getResult();

        return $lignes;
    }
}
