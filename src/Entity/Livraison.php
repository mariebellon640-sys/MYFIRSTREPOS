<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\StatutLivraison;
use App\Repository\LivraisonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LivraisonRepository::class)]
#[ORM\Table(name: 'livraison')]
#[ORM\Index(name: 'idx_livraison_livreur', columns: ['livreur_id'])]
#[ORM\Index(name: 'idx_livraison_statut', columns: ['statut'])]
class Livraison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Commande::class, inversedBy: 'livraison')]
    #[ORM\JoinColumn(name: 'commande_id', nullable: false, unique: true, onDelete: 'CASCADE')]
    private Commande $commande;

    #[ORM\ManyToOne(targetEntity: Livreur::class, inversedBy: 'livraisons')]
    #[ORM\JoinColumn(name: 'livreur_id', referencedColumnName: 'utilisateur_id', nullable: true, onDelete: 'SET NULL')]
    private ?Livreur $livreur = null;

    #[ORM\Column(enumType: StatutLivraison::class, options: ['default' => 'EN_ATTENTE_AFFECTATION'])]
    private StatutLivraison $statut = StatutLivraison::EN_ATTENTE_AFFECTATION;

    #[ORM\Column(name: 'date_affectation', nullable: true)]
    private ?\DateTimeImmutable $dateAffectation = null;

    #[ORM\Column(name: 'date_prise_en_charge', nullable: true)]
    private ?\DateTimeImmutable $datePriseEnCharge = null;

    #[ORM\Column(name: 'date_livraison_effective', nullable: true)]
    private ?\DateTimeImmutable $dateLivraisonEffective = null;

    #[ORM\Column(name: 'latitude_livraison', type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $latitudeLivraison = null;

    #[ORM\Column(name: 'longitude_livraison', type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $longitudeLivraison = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    /** @var Collection<int, LivraisonPosition> */
    #[ORM\OneToMany(targetEntity: LivraisonPosition::class, mappedBy: 'livraison', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['horodatage' => 'DESC'])]
    private Collection $positions;

    public function __construct(Commande $commande)
    {
        $this->commande = $commande;
        $commande->setLivraison($this);
        $this->positions = new ArrayCollection();
        $adresse = $commande->getAdresseLivraison();
        $this->latitudeLivraison = $adresse->getLatitude();
        $this->longitudeLivraison = $adresse->getLongitude();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): Commande
    {
        return $this->commande;
    }

    public function getLivreur(): ?Livreur
    {
        return $this->livreur;
    }

    public function setLivreur(?Livreur $livreur): self
    {
        $this->livreur = $livreur;
        if (null !== $livreur) {
            $this->dateAffectation = new \DateTimeImmutable();
            $this->statut = StatutLivraison::AFFECTEE;
        }

        return $this;
    }

    public function getStatut(): StatutLivraison
    {
        return $this->statut;
    }

    public function setStatut(StatutLivraison $statut): self
    {
        $this->statut = $statut;
        if (StatutLivraison::EN_COURS === $statut && null === $this->datePriseEnCharge) {
            $this->datePriseEnCharge = new \DateTimeImmutable();
        }
        if (StatutLivraison::LIVREE === $statut && null === $this->dateLivraisonEffective) {
            $this->dateLivraisonEffective = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getDateAffectation(): ?\DateTimeImmutable
    {
        return $this->dateAffectation;
    }

    public function getDatePriseEnCharge(): ?\DateTimeImmutable
    {
        return $this->datePriseEnCharge;
    }

    public function getDateLivraisonEffective(): ?\DateTimeImmutable
    {
        return $this->dateLivraisonEffective;
    }

    public function getLatitudeLivraison(): ?string
    {
        return $this->latitudeLivraison;
    }

    public function getLongitudeLivraison(): ?string
    {
        return $this->longitudeLivraison;
    }

    public function setCoordonneesLivraison(?string $latitude, ?string $longitude): self
    {
        $this->latitudeLivraison = $latitude;
        $this->longitudeLivraison = $longitude;

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

    /** @return Collection<int, LivraisonPosition> */
    public function getPositions(): Collection
    {
        return $this->positions;
    }

    public function addPosition(LivraisonPosition $position): self
    {
        if (!$this->positions->contains($position)) {
            $this->positions->add($position);
            $position->setLivraison($this);
        }

        return $this;
    }

    public function getDernierePosition(): ?LivraisonPosition
    {
        return $this->positions->first() ?: null;
    }

    public function getEtaMinutes(?\DateTimeImmutable $maintenant = null): ?int
    {
        if ($this->statut->estCloturee() || null === $this->datePriseEnCharge) {
            return null;
        }
        $maintenant ??= new \DateTimeImmutable();
        $delai = $this->commande->getZoneLivraison()->getDelaiEstimeMinutes();
        $ecoule = (int) floor(($maintenant->getTimestamp() - $this->datePriseEnCharge->getTimestamp()) / 60);

        return max(0, $delai - $ecoule);
    }
}
