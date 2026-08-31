<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Commande;
use App\Entity\Fournisseur;
use App\Enum\StatutCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /** @return list<Commande> */
    public function findPourClient(Client $client): array
    {
        /** @var list<Commande> $commandes */
        $commandes = $this->createQueryBuilder('c')
            ->leftJoin('c.lignes', 'l')->addSelect('l')
            ->leftJoin('c.livraison', 'liv')->addSelect('liv')
            ->leftJoin('c.paiement', 'p')->addSelect('p')
            ->where('c.client = :client')
            ->setParameter('client', $client)
            ->orderBy('c.dateCommande', 'DESC')
            ->getQuery()
            ->getResult();

        return $commandes;
    }

    /** @return list<Commande> */
    public function findPourFournisseur(Fournisseur $fournisseur, ?StatutCommande $statut = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.lignes', 'l')->addSelect('l')
            ->join('l.produit', 'pr')->addSelect('pr')
            ->join('c.client', 'cl')->addSelect('cl')
            ->join('cl.utilisateur', 'u')->addSelect('u')
            ->where('l.fournisseur = :fournisseur')
            ->setParameter('fournisseur', $fournisseur)
            ->orderBy('c.dateCommande', 'DESC');

        if (null !== $statut) {
            $qb->andWhere('c.statut = :statut')->setParameter('statut', $statut);
        }

        /** @var list<Commande> $commandes */
        $commandes = $qb->getQuery()->getResult();

        return $commandes;
    }

    /** @return list<Commande> */
    public function findRecentes(int $limite = 20): array
    {
        /** @var list<Commande> $commandes */
        $commandes = $this->createQueryBuilder('c')
            ->join('c.client', 'cl')->addSelect('cl')
            ->join('cl.utilisateur', 'u')->addSelect('u')
            ->leftJoin('c.livraison', 'liv')->addSelect('liv')
            ->orderBy('c.dateCommande', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();

        return $commandes;
    }

    public function chiffreAffairesTotal(): string
    {
        $total = $this->createQueryBuilder('c')
            ->select('SUM(c.montantTotal)')
            ->where('c.statut = :livree')
            ->setParameter('livree', StatutCommande::LIVREE)
            ->getQuery()
            ->getSingleScalarResult();

        return number_format((float) $total, 2, '.', '');
    }

    public function compterHorsAnnulees(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.statut != :annulee')
            ->setParameter('annulee', StatutCommande::ANNULEE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return array<string, int> */
    public function repartitionParStatut(): array
    {
        /** @var list<array{statut: StatutCommande, total: int}> $lignes */
        $lignes = $this->createQueryBuilder('c')
            ->select('c.statut AS statut, COUNT(c.id) AS total')
            ->groupBy('c.statut')
            ->getQuery()
            ->getResult();

        $repartition = [];
        foreach ($lignes as $ligne) {
            $repartition[$ligne['statut']->value] = (int) $ligne['total'];
        }

        return $repartition;
    }

    /** @return list<array{nom: string, quantite: string, chiffreAffaires: string}> */
    public function produitsLesPlusVendus(int $limite = 5): array
    {
        /** @var list<array{nom: string, quantite: string, chiffreAffaires: string}> $lignes */
        $lignes = $this->createQueryBuilder('c')
            ->select('p.nom AS nom, SUM(l.quantite) AS quantite, SUM(l.sousTotal) AS chiffreAffaires')
            ->join('c.lignes', 'l')
            ->join('l.produit', 'p')
            ->where('c.statut != :annulee')
            ->setParameter('annulee', StatutCommande::ANNULEE)
            ->groupBy('p.id')
            ->orderBy('quantite', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();

        return $lignes;
    }

    /** @return list<array{zone: string, total: int}> */
    public function commandesParZone(): array
    {
        /** @var list<array{zone: string, total: int}> $lignes */
        $lignes = $this->createQueryBuilder('c')
            ->select('z.nomZone AS zone, COUNT(c.id) AS total')
            ->join('c.zoneLivraison', 'z')
            ->groupBy('z.id')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        return $lignes;
    }
}
