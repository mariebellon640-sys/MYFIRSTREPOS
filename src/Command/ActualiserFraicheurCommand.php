<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\CanalNotification;
use App\Enum\TypeNotification;
use App\Repository\LotPecheRepository;
use App\Service\FraicheurCalculateur;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Recalcule l'indice de fraicheur de tous les lots actifs et retire du catalogue
 * ceux qui passent sous le seuil (regle de gestion 2). A planifier toutes les heures.
 */
#[AsCommand(
    name: 'poissonvip:fraicheur:actualiser',
    description: 'Recalcule les indices de fraicheur et retire les lots trop anciens du catalogue.',
)]
class ActualiserFraicheurCommand extends Command
{
    public function __construct(
        private readonly LotPecheRepository $lotRepository,
        private readonly FraicheurCalculateur $fraicheur,
        private readonly NotificationService $notificationService,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $lots = $this->lotRepository->findActifs();
        $retires = 0;

        foreach ($lots as $lot) {
            if (!$this->fraicheur->actualiser($lot)) {
                continue;
            }

            ++$retires;
            $produit = $lot->getProduit();
            if (null === $produit) {
                continue;
            }

            $produit->setStockDisponible('0.00');
            $this->notificationService->notifier(
                $produit->getFournisseur()->getUtilisateur(),
                TypeNotification::STOCK,
                'Lot retire du catalogue',
                sprintf('Le lot %s (%s) a depasse le seuil de fraicheur et a ete retire de la vente.', $lot->getCodeLot(), $produit->getNom()),
                [CanalNotification::PUSH, CanalNotification::EMAIL],
            );
        }

        $this->em->flush();

        $style->success(sprintf('%d lot(s) analyse(s), %d retire(s) du catalogue.', \count($lots), $retires));

        return Command::SUCCESS;
    }
}
