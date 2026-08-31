<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\StatutModeration;
use App\Repository\AvisRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AvisRepository::class)]
#[ORM\Table(name: 'avis')]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'avis')]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'utilisateur_id', nullable: false, onDelete: 'CASCADE')]
    private Client $client;

    #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'avis')]
    #[ORM\JoinColumn(name: 'produit_id', nullable: true, onDelete: 'CASCADE')]
    private ?Produit $produit = null;

    #[ORM\ManyToOne(targetEntity: Fournisseur::class)]
    #[ORM\JoinColumn(name: 'fournisseur_id', referencedColumnName: 'utilisateur_id', nullable: true, onDelete: 'CASCADE')]
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne(targetEntity: Livreur::class)]
    #[ORM\JoinColumn(name: 'livreur_id', referencedColumnName: 'utilisateur_id', nullable: true, onDelete: 'CASCADE')]
    private ?Livreur $livreur = null;

    #[ORM\ManyToOne(targetEntity: Commande::class)]
    #[ORM\JoinColumn(name: 'commande_id', nullable: false, onDelete: 'CASCADE')]
    private Commande $commande;

    #[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'comment' => '1 a 5'])]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'La note doit etre comprise entre 1 et 5.')]
    private int $note = 5;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 2000)]
    private ?string $commentaire = null;

    #[ORM\Column(name: 'statut_moderation', enumType: StatutModeration::class, options: ['default' => 'EN_ATTENTE'])]
    private StatutModeration $statutModeration = StatutModeration::EN_ATTENTE;

    #[ORM\Column(name: 'est_signale', options: ['default' => false])]
    private bool $estSignale = false;

    #[ORM\Column(name: 'motif_signalement', length: 255, nullable: true)]
    private ?string $motifSignalement = null;

    #[ORM\Column(name: 'date_creation')]
    private \DateTimeImmutable $dateCreation;

    public function __construct(Client $client, Commande $commande)
    {
        $this->client = $client;
        $this->commande = $commande;
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): self
    {
        $this->produit = $produit;

        return $this;
    }

    public function getFournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseur $fournisseur): self
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getLivreur(): ?Livreur
    {
        return $this->livreur;
    }

    public function setLivreur(?Livreur $livreur): self
    {
        $this->livreur = $livreur;

        return $this;
    }

    public function getCommande(): Commande
    {
        return $this->commande;
    }

    public function getNote(): int
    {
        return $this->note;
    }

    public function setNote(int $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getStatutModeration(): StatutModeration
    {
        return $this->statutModeration;
    }

    public function setStatutModeration(StatutModeration $statutModeration): self
    {
        $this->statutModeration = $statutModeration;

        return $this;
    }

    public function estPublie(): bool
    {
        return StatutModeration::PUBLIE === $this->statutModeration;
    }

    public function isEstSignale(): bool
    {
        return $this->estSignale;
    }

    public function signaler(string $motif): self
    {
        $this->estSignale = true;
        $this->motifSignalement = $motif;

        return $this;
    }

    public function getMotifSignalement(): ?string
    {
        return $this->motifSignalement;
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }
}
