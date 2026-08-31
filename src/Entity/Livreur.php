<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\StatutDisponibilite;
use App\Enum\TypeVehicule;
use App\Repository\LivreurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LivreurRepository::class)]
#[ORM\Table(name: 'livreur')]
#[ORM\Index(name: 'idx_livreur_statut', columns: ['statut_disponibilite'])]
class Livreur
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Utilisateur::class, inversedBy: 'livreur')]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $utilisateur;

    #[ORM\Column(name: 'type_vehicule', enumType: TypeVehicule::class)]
    private TypeVehicule $typeVehicule = TypeVehicule::MOTO;

    #[ORM\Column(name: 'numero_permis', length: 50, nullable: true)]
    private ?string $numeroPermis = null;

    #[ORM\Column(name: 'statut_disponibilite', enumType: StatutDisponibilite::class, options: ['default' => 'HORS_LIGNE'])]
    private StatutDisponibilite $statutDisponibilite = StatutDisponibilite::HORS_LIGNE;

    #[ORM\Column(name: 'latitude_actuelle', type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $latitudeActuelle = null;

    #[ORM\Column(name: 'longitude_actuelle', type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $longitudeActuelle = null;

    #[ORM\ManyToOne(targetEntity: ZoneLivraison::class)]
    #[ORM\JoinColumn(name: 'zone_affectation_id', nullable: true, onDelete: 'SET NULL')]
    private ?ZoneLivraison $zoneAffectation = null;

    #[ORM\Column(name: 'note_moyenne', type: 'decimal', precision: 2, scale: 1, options: ['default' => '0.0'])]
    private string $noteMoyenne = '0.0';

    /** @var Collection<int, Livraison> */
    #[ORM\OneToMany(targetEntity: Livraison::class, mappedBy: 'livreur')]
    private Collection $livraisons;

    public function __construct(Utilisateur $utilisateur)
    {
        $this->utilisateur = $utilisateur;
        $utilisateur->setLivreur($this);
        $this->livraisons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->utilisateur->getId();
    }

    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    public function getTypeVehicule(): TypeVehicule
    {
        return $this->typeVehicule;
    }

    public function setTypeVehicule(TypeVehicule $typeVehicule): self
    {
        $this->typeVehicule = $typeVehicule;

        return $this;
    }

    public function getNumeroPermis(): ?string
    {
        return $this->numeroPermis;
    }

    public function setNumeroPermis(?string $numeroPermis): self
    {
        $this->numeroPermis = $numeroPermis;

        return $this;
    }

    public function getStatutDisponibilite(): StatutDisponibilite
    {
        return $this->statutDisponibilite;
    }

    public function setStatutDisponibilite(StatutDisponibilite $statut): self
    {
        $this->statutDisponibilite = $statut;

        return $this;
    }

    public function getLatitudeActuelle(): ?string
    {
        return $this->latitudeActuelle;
    }

    public function getLongitudeActuelle(): ?string
    {
        return $this->longitudeActuelle;
    }

    public function setPositionActuelle(?string $latitude, ?string $longitude): self
    {
        $this->latitudeActuelle = $latitude;
        $this->longitudeActuelle = $longitude;

        return $this;
    }

    public function getZoneAffectation(): ?ZoneLivraison
    {
        return $this->zoneAffectation;
    }

    public function setZoneAffectation(?ZoneLivraison $zoneAffectation): self
    {
        $this->zoneAffectation = $zoneAffectation;

        return $this;
    }

    public function getNoteMoyenne(): string
    {
        return $this->noteMoyenne;
    }

    public function setNoteMoyenne(string $noteMoyenne): self
    {
        $this->noteMoyenne = $noteMoyenne;

        return $this;
    }

    /** @return Collection<int, Livraison> */
    public function getLivraisons(): Collection
    {
        return $this->livraisons;
    }

    public function __toString(): string
    {
        return $this->utilisateur->getNomComplet();
    }
}
