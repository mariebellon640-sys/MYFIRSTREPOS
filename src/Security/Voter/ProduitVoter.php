<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Enum\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Produit>
 */
class ProduitVoter extends Voter
{
    public const MODIFIER = 'PRODUIT_MODIFIER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::MODIFIER === $attribute && $subject instanceof Produit;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $utilisateur = $token->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return false;
        }
        \assert($subject instanceof Produit);

        if (Role::ADMIN === $utilisateur->getRole()) {
            return true;
        }

        $fournisseur = $utilisateur->getFournisseur();

        return null !== $fournisseur
            && $fournisseur->peutPublier()
            && $subject->getFournisseur()->getId() === $fournisseur->getId();
    }
}
