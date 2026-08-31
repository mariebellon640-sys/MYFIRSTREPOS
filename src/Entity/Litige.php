<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\StatutLitige;
use App\Enum\TypeLitige;
use App\Repository\LitigeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LitigeRepository::class)]
#[ORM\Table(name: 'litige')]
class Litige
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Commande::class)]
    #[ORM\JoinColumn(name: 'commande_id', nullable: false, onDelete: 'CASCADE')]
    private Commande $commande;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'utilisateur_id', nullable: false, onDelete: 'CASCADE')]
    private Client $client;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'traite_par_id', nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $traitePar = null;

    #[ORM\Column(name: 'type_litige', enumType: TypeLitige::class)]
    private TypeLitige $typeLitige = TypeLitige::AUTRE;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Decrivez le probleme rencontre.')]
    private string $description = '';

    #[ORM\Column(enumType: StatutLitige::class, options: ['default' => 'OUVERT'])]
    private StatutLitige $statut = StatutLitige::OUVERT;

    #[ORM\Column(name: 'reponse', type: 'text', nullable: true)]
    private ?string $reponse = null;

    #[ORM\Column(name: 'montant_rembourse', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $montantRembourse = null;

    #[ORM\Column(name: 'date_ouverture')]
    private \DateTimeImmutable $dateOuverture;

    #[ORM\Column(name: 'date_resolution', nullable: true)]
    private ?\DateTimeImmutable $dateResolution = null;

    public function __construct(Commande $commande, Client $client)
    {
        $this->commande = $commande;
        $this->client = $client;
        $this->dateOuverture = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): Commande
    {
        return $this->commande;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getTraitePar(): ?Utilisateur
    {
        return $this->traitePar;
    }

    public function setTraitePar(?Utilisateur $traitePar): self
    {
        $this->traitePar = $traitePar;

        return $this;
    }

    public function getTypeLitige(): TypeLitige
    {
        return $this->typeLitige;
    }

    public function setTypeLitige(TypeLitige $typeLitige): self
    {
        $this->typeLitige = $typeLitige;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getStatut(): StatutLitige
    {
        return $this->statut;
    }

    public function setStatut(StatutLitige $statut): self
    {
        $this->statut = $statut;
        if (\in_array($statut, [StatutLitige::RESOLU, StatutLitige::REJETE], true)) {
            $this->dateResolution = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getReponse(): ?string
    {
        return $this->reponse;
    }

    public function setReponse(?string $reponse): self
    {
        $this->reponse = $reponse;

        return $this;
    }

    public function getMontantRembourse(): ?string
    {
        return $this->montantRembourse;
    }

    public function setMontantRembourse(?string $montantRembourse): self
    {
        $this->montantRembourse = $montantRembourse;

        return $this;
    }

    public function getDateOuverture(): \DateTimeImmutable
    {
        return $this->dateOuverture;
    }

    public function getDateResolution(): ?\DateTimeImmutable
    {
        return $this->dateResolution;
    }
}
