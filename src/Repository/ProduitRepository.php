<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Fournisseur;
use App\Entity\Produit;
use App\Enum\StatutValidation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    /**
     * Catalogue public : uniquement les produits actifs de fournisseurs valides.
     *
     * @param array{
     *     recherche?: string|null,
     *     categorie?: int|null,
     *     fournisseur?: int|null,
     *     prixMin?: string|null,
     *     prixMax?: string|null,
     *     fraicheurMin?: string|null,
     *     enStock?: bool,
     *     tri?: string|null
     * } $filtres
     */
    public function creerRequeteCatalogue(array $filtres = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.fournisseur', 'f')
            ->addSelect('f')
            ->join('f.utilisateur', 'fu')
            ->addSelect('fu')
            ->join('p.categorie', 'c')
            ->addSelect('c')
            ->leftJoin('p.lots', 'lot', 'WITH', 'lot.estRetire = false')
            ->addSelect('lot')
            ->where('p.estActif = true')
            ->andWhere('f.statutValidation = :valide')
            ->setParameter('valide', StatutValidation::VALIDE);

        if (!empty($filtres['recherche'])) {
            $qb->andWhere('p.nom LIKE :recherche OR p.espece LIKE :recherche OR p.description LIKE :recherche')
                ->setParameter('recherche', '%'.$filtres['recherche'].'%');
        }
        if (!empty($filtres['categorie'])) {
            $qb->andWhere('c.id = :categorie')->setParameter('categorie', $filtres['categorie']);
        }
        if (!empty($filtres['fournisseur'])) {
            $qb->andWhere('f.utilisateur = :fournisseur')->setParameter('fournisseur', $filtres['fournisseur']);
        }
        if (isset($filtres['prixMin']) && '' !== $filtres['prixMin'] && null !== $filtres['prixMin']) {
            $qb->andWhere('p.prixUnitaire >= :prixMin')->setParameter('prixMin', $filtres['prixMin']);
        }
        if (isset($filtres['prixMax']) && '' !== $filtres['prixMax'] && null !== $filtres['prixMax']) {
            $qb->andWhere('p.prixUnitaire <= :prixMax')->setParameter('prixMax', $filtres['prixMax']);
        }
        if (isset($filtres['fraicheurMin']) && '' !== $filtres['fraicheurMin'] && null !== $filtres['fraicheurMin']) {
            $qb->andWhere('lot.indiceFraicheur >= :fraicheurMin')->setParameter('fraicheurMin', $filtres['fraicheurMin']);
        }
        if (!empty($filtres['enStock'])) {
            $qb->andWhere('p.stockDisponible > 0');
        }

        return match ($filtres['tri'] ?? null) {
            'prix_asc' => $qb->orderBy('p.prixUnitaire', 'ASC'),
            'prix_desc' => $qb->orderBy('p.prixUnitaire', 'DESC'),
            'fraicheur' => $qb->orderBy('lot.indiceFraicheur', 'DESC'),
            default => $qb->orderBy('p.dateMaj', 'DESC'),
        };
    }

    /** @return list<Produit> */
    public function findCatalogue(array $filtres = [], int $limite = 24): array
    {
        /** @var list<Produit> $produits */
        $produits = $this->creerRequeteCatalogue($filtres)->setMaxResults($limite)->getQuery()->getResult();

        return $produits;
    }

    /** @return list<Produit> */
    public function findParFournisseur(Fournisseur $fournisseur): array
    {
        /** @var list<Produit> $produits */
        $produits = $this->createQueryBuilder('p')
            ->leftJoin('p.lots', 'lot')
            ->addSelect('lot')
            ->where('p.fournisseur = :fournisseur')
            ->setParameter('fournisseur', $fournisseur)
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();

        return $produits;
    }

    /** @return list<Produit> */
    public function findEnAlerteStock(?Fournisseur $fournisseur = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.estActif = true')
            ->andWhere('p.stockDisponible <= :seuil')
            ->setParameter('seuil', Produit::SEUIL_ALERTE_STOCK)
            ->orderBy('p.stockDisponible', 'ASC');

        if (null !== $fournisseur) {
            $qb->andWhere('p.fournisseur = :fournisseur')->setParameter('fournisseur', $fournisseur);
        }

        /** @var list<Produit> $produits */
        $produits = $qb->getQuery()->getResult();

        return $produits;
    }

    /**
     * Recommandations : produits en stock appartenant aux categories deja
     * commandees par le client, en excluant ceux qu'il a deja achetes.
     *
     * @return list<Produit>
     */
    public function findRecommandationsPourClient(int $clientId, int $limite = 4): array
    {
        $em = $this->getEntityManager();

        /** @var list<int> $categoriesAchetees */
        $categoriesAchetees = array_column($em->createQuery(
            'SELECT DISTINCT IDENTITY(p.categorie) AS categorieId
             FROM App\Entity\LigneCommande lc
             JOIN lc.produit p
             JOIN lc.commande c
             WHERE c.client = :client'
        )->setParameter('client', $clientId)->getArrayResult(), 'categorieId');

        if ([] === $categoriesAchetees) {
            return $this->findCatalogue(['enStock' => true], $limite);
        }

        /** @var list<int> $produitsAchetes */
        $produitsAchetes = array_column($em->createQuery(
            'SELECT DISTINCT IDENTITY(lc.produit) AS produitId
             FROM App\Entity\LigneCommande lc
             JOIN lc.commande c
             WHERE c.client = :client'
        )->setParameter('client', $clientId)->getArrayResult(), 'produitId');

        /** @var list<Produit> $produits */
        $produits = $this->createQueryBuilder('p')
            ->join('p.fournisseur', 'f')
            ->where('p.estActif = true')
            ->andWhere('p.stockDisponible > 0')
            ->andWhere('f.statutValidation = :valide')
            ->andWhere('p.categorie IN (:categories)')
            ->andWhere('p.id NOT IN (:produitsAchetes)')
            ->setParameter('valide', StatutValidation::VALIDE)
            ->setParameter('categories', $categoriesAchetees)
            ->setParameter('produitsAchetes', $produitsAchetes)
            ->orderBy('p.dateMaj', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();

        return $produits;
    }
}
