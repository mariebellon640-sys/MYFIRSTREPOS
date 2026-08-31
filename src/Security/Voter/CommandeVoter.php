<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Enum\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Commande>
 */
class CommandeVoter extends Voter
{
    public const VOIR = 'COMMANDE_VOIR';
    public const ANNULER = 'COMMANDE_ANNULER';
    public const PREPARER = 'COMMANDE_PREPARER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VOIR, self::ANNULER, self::PREPARER], true) && $subject instanceof Commande;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $utilisateur = $token->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return false;
        }
        \assert($subject instanceof Commande);

        if (\in_array($utilisateur->getRole(), [Role::ADMIN, Role::SUPPORT], true)) {
            return self::PREPARER !== $attribute;
        }

        $client = $utilisateur->getClient();
        $fournisseur = $utilisateur->getFournisseur();

        return match ($attribute) {
            self::VOIR => (null !== $client && $subject->getClient()->getId() === $client->getId())
                || (null !== $fournisseur && $subject->concerneFournisseur($fournisseur)),
            self::ANNULER => null !== $client
                && $subject->getClient()->getId() === $client->getId()
                && $subject->estAnnulableParClient(),
            self::PREPARER => null !== $fournisseur && $subject->concerneFournisseur($fournisseur),
            default => false,
        };
    }
}
