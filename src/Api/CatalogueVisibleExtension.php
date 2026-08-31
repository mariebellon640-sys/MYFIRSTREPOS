<?php

declare(strict_types=1);

namespace App\Api;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Produit;
use App\Enum\StatutValidation;
use Doctrine\ORM\QueryBuilder;

/**
 * L'API publique ne doit exposer que les produits reellement commandables :
 * fournisseur valide, produit actif et stock disponible.
 */
final class CatalogueVisibleExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->restreindre($queryBuilder, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->restreindre($queryBuilder, $resourceClass);
    }

    private function restreindre(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (Produit::class !== $resourceClass) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->join(sprintf('%s.fournisseur', $alias), 'fournisseur_visible')
            ->andWhere(sprintf('%s.estActif = true', $alias))
            ->andWhere(sprintf('%s.stockDisponible > 0', $alias))
            ->andWhere('fournisseur_visible.statutValidation = :statut_valide')
            ->setParameter('statut_valide', StatutValidation::VALIDE);
    }
}
