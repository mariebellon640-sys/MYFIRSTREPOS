<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PanierItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PanierItemRepository::class)]
#[ORM\Table(name: 'panier_item')]
#[ORM\UniqueConstraint(name: 'uq_panier_produit', columns: ['panier_id', 'produit_id', 'preparation_choisie'])]
class PanierItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Panier::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'panier_id', nullable: false, onDelete: 'CASCADE')]
    private ?Panier $panier = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: 'produit_id', nullable: false, onDelete: 'CASCADE')]
    private Produit $produit;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\Positive(message: 'La quantite doit etre superieure a zero.')]
    private string $quantite = '1.00';

    #[ORM\Column(name: 'preparation_choisie', length: 50, nullable: true)]
    private ?string $preparationChoisie = null;

    public function __construct(Produit $produit, string $quantite = '1.00', ?string $preparationChoisie = null)
    {
        $this->produit = $produit;
        $this->quantite = $quantite;
        $this->preparationChoisie = $preparationChoisie;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPanier(): ?Panier
    {
        return $this->panier;
    }

    public function setPanier(?Panier $panier): self
    {
        $this->panier = $panier;

        return $this;
    }

    public function getProduit(): Produit
    {
        return $this->produit;
    }

    public function getQuantite(): string
    {
        return $this->quantite;
    }

    public function setQuantite(string $quantite): self
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function ajouterQuantite(string $quantite): self
    {
        $this->quantite = number_format((float) $this->quantite + (float) $quantite, 2, '.', '');

        return $this;
    }

    public function getPreparationChoisie(): ?string
    {
        return $this->preparationChoisie;
    }

    public function setPreparationChoisie(?string $preparationChoisie): self
    {
        $this->preparationChoisie = $preparationChoisie;

        return $this;
    }

    public function correspond(Produit $produit, ?string $preparation): bool
    {
        return $this->produit->getId() === $produit->getId() && $this->preparationChoisie === $preparation;
    }

    public function getSousTotal(): string
    {
        return number_format((float) $this->produit->getPrixUnitaire() * (float) $this->quantite, 2, '.', '');
    }
}
