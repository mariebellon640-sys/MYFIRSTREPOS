<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\LivraisonRepository;
use App\Service\LivraisonAffectationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Rattrape les livraisons restees sans livreur, faute de disponibilite au moment de la commande. */
#[AsCommand(
    name: 'poissonvip:livraisons:affecter',
    description: 'Affecte automatiquement les livraisons en attente aux livreurs disponibles.',
)]
class AffecterLivraisonsCommand extends Command
{
    public function __construct(
        private readonly LivraisonRepository $livraisonRepository,
        private readonly LivraisonAffectationService $affectationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $affectees = 0;

        foreach ($this->livraisonRepository->findEnAttenteAffectation() as $livraison) {
            if (null !== $this->affectationService->affecterAutomatiquement($livraison->getCommande())) {
                ++$affectees;
            }
        }

        $style->success(sprintf('%d livraison(s) affectee(s).', $affectees));

        return Command::SUCCESS;
    }
}
