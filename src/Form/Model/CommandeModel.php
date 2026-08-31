<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Adresse;
use App\Enum\ModePaiement;
use Symfony\Component\Validator\Constraints as Assert;

/** Donnees de validation du panier (etape « commander »). */
class CommandeModel
{
    #[Assert\NotNull(message: 'Choisissez une adresse de livraison.')]
    public ?Adresse $adresseLivraison = null;

    public ModePaiement $modePaiement = ModePaiement::MVOLA;

    #[Assert\GreaterThan('now', message: 'Le creneau de livraison doit etre dans le futur.')]
    public ?\DateTimeImmutable $creneauLivraison = null;

    public ?string $codePromo = null;

    #[Assert\Regex(pattern: '/^(\+261|0)3[2-4|8]\d{7}$/', message: 'Numero Mobile Money invalide.')]
    public ?string $numeroPayeur = null;
}
