<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'client')]
class Client
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Utilisateur::class, inversedBy: 'client')]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $utilisateur;

    #[ORM\Column(name: 'points_fidelite', options: ['unsigned' => true, 'default' => 0])]
    private int $pointsFidelite = 0;

    #[ORM\Column(name: 'code_parrainage', length: 20, unique: true)]
    private string $codeParrainage;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parraine_par_id', referencedColumnName: 'utilisateur_id', nullable: true, onDelete: 'SET NULL')]
    private ?self $parrainePar = null;

    /** @var Collection<int, Commande> */
    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'client')]
    #[ORM\OrderBy(['dateCommande' => 'DESC'])]
    private Collection $commandes;

    /** @var Collection<int, Avis> */
    #[ORM\OneToMany(targetEntity: Avis::class, mappedBy: 'client')]
    private Collection $avis;

    public function __construct(Utilisateur $utilisateur, ?string $codeParrainage = null)
    {
        $this->utilisateur = $utilisateur;
        $utilisateur->setClient($this);
        $this->codeParrainage = $codeParrainage ?? self::genererCodeParrainage();
        $this->commandes = new ArrayCollection();
        $this->avis = new ArrayCollection();
    }

    public static function genererCodeParrainage(): string
    {
        return 'PVIP-'.strtoupper(bin2hex(random_bytes(4)));
    }

    public function getId(): ?int
    {
        return $this->utilisateur->getId();
    }

    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    public function getPointsFidelite(): int
    {
        return $this->pointsFidelite;
    }

    public function setPointsFidelite(int $pointsFidelite): self
    {
        $this->pointsFidelite = max(0, $pointsFidelite);

        return $this;
    }

    public function ajouterPointsFidelite(int $points): self
    {
        return $this->setPointsFidelite($this->pointsFidelite + $points);
    }

    public function getCodeParrainage(): string
    {
        return $this->codeParrainage;
    }

    public function getParrainePar(): ?self
    {
        return $this->parrainePar;
    }

    public function setParrainePar(?self $parrainePar): self
    {
        $this->parrainePar = $parrainePar;

        return $this;
    }

    /** @return Collection<int, Commande> */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    /** @return Collection<int, Avis> */
    public function getAvis(): Collection
    {
        return $this->avis;
    }

    public function __toString(): string
    {
        return $this->utilisateur->getNomComplet();
    }
}
