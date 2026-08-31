<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LigneCommandeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LigneCommandeRepository::class)]
#[ORM\Table(name: 'ligne_commande')]
#[ORM\Index(name: 'idx_lignecommande_commande', columns: ['commande_id'])]
class LigneCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'lignes')]
    #[ORM\JoinColumn(name: 'commande_id', nullable: false, onDelete: 'CASCADE')]
    private ?Commande $commande = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: 'produit_id', nullable: false, onDelete: 'RESTRICT')]
    private Produit $produit;

    /** Denormalise pour la repartition multi-fournisseurs et l'historique des ventes. */
    #[ORM\ManyToOne(targetEntity: Fournisseur::class)]
    #[ORM\JoinColumn(name: 'fournisseur_id', referencedColumnName: 'utilisateur_id', nullable: false, onDelete: 'RESTRICT')]
    private Fournisseur $fournisseur;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $quantite = '0.00';

    /** Prix fige au moment de la commande. */
    #[ORM\Column(name: 'prix_unitaire', type: 'decimal', precision: 10, scale: 2)]
    private string $prixUnitaire = '0.00';

    #[ORM\Column(name: 'preparation_choisie', length: 50, nullable: true)]
    private ?string $preparationChoisie = null;

    #[ORM\Column(name: 'sous_total', type: 'decimal', precision: 10, scale: 2)]
    private string $sousTotal = '0.00';

    public function __construct(Produit $produit, string $quantite, ?string $preparationChoisie = null)
    {
        $fournisseur = $produit->getFournisseur();
        if (null === $fournisseur) {
            throw new \LogicException('Un produit commande doit etre rattache a un fournisseur.');
        }

        $this->produit = $produit;
        $this->fournisseur = $fournisseur;
        $this->quantite = $quantite;
        $this->prixUnitaire = $produit->getPrixUnitaire();
        $this->preparationChoisie = $preparationChoisie;
        $this->sousTotal = number_format((float) $quantite * (float) $this->prixUnitaire, 2, '.', '');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): self
    {
        $this->commande = $commande;

        return $this;
    }

    public function getProduit(): Produit
    {
        return $this->produit;
    }

    public function getFournisseur(): Fournisseur
    {
        return $this->fournisseur;
    }

    public function getQuantite(): string
    {
        return $this->quantite;
    }

    public function getPrixUnitaire(): string
    {
        return $this->prixUnitaire;
    }

    public function getPreparationChoisie(): ?string
    {
        return $this->preparationChoisie;
    }

    public function getSousTotal(): string
    {
        return $this->sousTotal;
    }
}
