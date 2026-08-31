<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Livraison;
use App\Entity\Utilisateur;
use App\Enum\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Livraison>
 */
class LivraisonVoter extends Voter
{
    public const SUIVRE = 'LIVRAISON_SUIVRE';
    public const METTRE_A_JOUR = 'LIVRAISON_METTRE_A_JOUR';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::SUIVRE, self::METTRE_A_JOUR], true) && $subject instanceof Livraison;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $utilisateur = $token->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return false;
        }
        \assert($subject instanceof Livraison);

        if (\in_array($utilisateur->getRole(), [Role::ADMIN, Role::SUPPORT], true)) {
            return true;
        }

        $livreur = $utilisateur->getLivreur();
        $estLivreurAffecte = null !== $livreur
            && null !== $subject->getLivreur()
            && $subject->getLivreur()->getId() === $livreur->getId();

        if (self::METTRE_A_JOUR === $attribute) {
            return $estLivreurAffecte;
        }

        $client = $utilisateur->getClient();

        return $estLivreurAffecte
            || (null !== $client && $subject->getCommande()->getClient()->getId() === $client->getId());
    }
}
