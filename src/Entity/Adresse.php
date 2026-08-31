<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AdresseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdresseRepository::class)]
#[ORM\Table(name: 'adresse')]
#[ORM\Index(name: 'idx_adresse_utilisateur', columns: ['utilisateur_id'])]
class Adresse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'adresses')]
    #[ORM\JoinColumn(name: 'utilisateur_id', nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(targetEntity: ZoneLivraison::class)]
    #[ORM\JoinColumn(name: 'zone_livraison_id', nullable: true, onDelete: 'SET NULL')]
    #[Assert\NotNull(message: 'Choisissez une zone de livraison.')]
    private ?ZoneLivraison $zoneLivraison = null;

    #[ORM\Column(length: 100, options: ['comment' => 'Ex: Maison, Bureau'])]
    #[Assert\NotBlank(message: 'Donnez un libelle a cette adresse (ex: Maison).')]
    private string $libelle = '';

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le quartier est obligatoire.')]
    private string $quartier = '';

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $rue = null;

    #[ORM\Column(name: 'point_repere', length: 255, nullable: true)]
    private ?string $pointRepere = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(name: 'est_principale', options: ['default' => false])]
    private bool $estPrincipale = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getZoneLivraison(): ?ZoneLivraison
    {
        return $this->zoneLivraison;
    }

    public function setZoneLivraison(?ZoneLivraison $zoneLivraison): self
    {
        $this->zoneLivraison = $zoneLivraison;

        return $this;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getQuartier(): string
    {
        return $this->quartier;
    }

    public function setQuartier(string $quartier): self
    {
        $this->quartier = $quartier;

        return $this;
    }

    public function getRue(): ?string
    {
        return $this->rue;
    }

    public function setRue(?string $rue): self
    {
        $this->rue = $rue;

        return $this;
    }

    public function getPointRepere(): ?string
    {
        return $this->pointRepere;
    }

    public function setPointRepere(?string $pointRepere): self
    {
        $this->pointRepere = $pointRepere;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function isEstPrincipale(): bool
    {
        return $this->estPrincipale;
    }

    public function setEstPrincipale(bool $estPrincipale): self
    {
        $this->estPrincipale = $estPrincipale;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s%s', $this->libelle, $this->quartier, $this->rue ? ', '.$this->rue : '');
    }
}
