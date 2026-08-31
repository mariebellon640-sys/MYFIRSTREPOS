<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Paiement;
use App\Enum\ModePaiement;
use App\Enum\StatutPaiement;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Passerelle de paiement Mobile Money.
 *
 * Tant que les contrats d'interconnexion Mvola / Orange Money / Airtel Money ne
 * sont pas actifs, le mode « simulation » confirme la transaction localement et
 * journalise l'operation, ce qui permet de derouler le parcours de bout en bout.
 */
class PaiementService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(string:MOBILE_MONEY_MODE)%')]
        private readonly string $mode = 'simulation',
    ) {
    }

    /** Initie la transaction aupres de la passerelle et retourne la reference. */
    public function initier(Paiement $paiement, ?string $numeroPayeur = null): string
    {
        $reference = $this->genererReference($paiement->getModePaiement());
        $paiement->setReferenceTransaction($reference);
        $paiement->setStatut(StatutPaiement::EN_ATTENTE);
        $this->em->flush();

        $this->journaliser('initiation', $paiement, ['numero_payeur' => $this->masquer($numeroPayeur)]);

        return $reference;
    }

    /**
     * Confirme le paiement. En mode simulation la confirmation est immediate ;
     * en mode reel elle est declenchee par le webhook de la passerelle.
     */
    public function confirmer(Paiement $paiement, ?string $referenceExterne = null): bool
    {
        if (ModePaiement::ESPECES_LIVRAISON === $paiement->getModePaiement()) {
            $this->journaliser('paiement_a_la_livraison', $paiement);

            return false;
        }

        if ('simulation' !== $this->mode && null === $referenceExterne) {
            $this->journaliser('confirmation_refusee', $paiement);

            return false;
        }

        $paiement->setStatut(StatutPaiement::CONFIRME);
        if (null !== $referenceExterne) {
            $paiement->setReferenceTransaction($referenceExterne);
        }
        $this->em->flush();

        $this->journaliser('confirmation', $paiement);

        return true;
    }

    public function echouer(Paiement $paiement, string $motif): void
    {
        $paiement->setStatut(StatutPaiement::ECHOUE);
        $this->em->flush();

        $this->journaliser('echec', $paiement, ['motif' => $motif]);
    }

    /** Confirmation du paiement en especes par le livreur a la remise du colis. */
    public function confirmerEspecesParLivreur(Paiement $paiement): void
    {
        if (ModePaiement::ESPECES_LIVRAISON !== $paiement->getModePaiement()) {
            throw new \DomainException('Cette commande n\'est pas reglee en especes a la livraison.');
        }

        $paiement->setStatut(StatutPaiement::CONFIRME);
        $this->em->flush();

        $this->journaliser('encaissement_livreur', $paiement);
    }

    public function rembourser(Paiement $paiement, ?string $montant = null): void
    {
        $paiement->setStatut(StatutPaiement::REMBOURSE);
        $this->em->flush();

        $this->journaliser('remboursement', $paiement, ['montant' => $montant ?? $paiement->getMontant()]);
    }

    private function genererReference(ModePaiement $mode): string
    {
        return sprintf('%s-%s-%s', substr($mode->value, 0, 3), (new \DateTimeImmutable())->format('YmdHis'), strtoupper(bin2hex(random_bytes(3))));
    }

    private function masquer(?string $numero): ?string
    {
        if (null === $numero || \strlen($numero) < 4) {
            return null;
        }

        return str_repeat('*', \strlen($numero) - 4).substr($numero, -4);
    }

    /** @param array<string, mixed> $contexte */
    private function journaliser(string $evenement, Paiement $paiement, array $contexte = []): void
    {
        $this->logger->info('[paiement] '.$evenement, array_merge([
            'paiement_id' => $paiement->getId(),
            'commande' => $paiement->getCommande()->getNumeroCommande(),
            'mode' => $paiement->getModePaiement()->value,
            'montant' => $paiement->getMontant(),
            'statut' => $paiement->getStatut()->value,
        ], $contexte));
    }
}
