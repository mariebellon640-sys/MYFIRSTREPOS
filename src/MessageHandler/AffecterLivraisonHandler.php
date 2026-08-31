<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\AffecterLivraison;
use App\Repository\CommandeRepository;
use App\Service\LivraisonAffectationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AffecterLivraisonHandler
{
    public function __construct(
        private CommandeRepository $commandeRepository,
        private LivraisonAffectationService $affectationService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(AffecterLivraison $message): void
    {
        $commande = $this->commandeRepository->find($message->commandeId);

        if (null === $commande) {
            $this->logger->warning('Affectation impossible : commande introuvable.', ['commande' => $message->commandeId]);

            return;
        }

        $livreur = $this->affectationService->affecterAutomatiquement($commande);

        if (null === $livreur) {
            $this->logger->info('Aucun livreur disponible, la commande reste en file d\'attente.', [
                'commande' => $commande->getNumeroCommande(),
            ]);
        }
    }
}
