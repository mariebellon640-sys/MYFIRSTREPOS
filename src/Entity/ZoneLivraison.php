<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ZoneLivraisonRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ZoneLivraisonRepository::class)]
#[ORM\Table(name: 'zone_livraison')]
class ZoneLivraison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'nom_zone', length: 100)]
    #[Assert\NotBlank]
    private string $nomZone = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $commune = '';

    #[ORM\Column(name: 'frais_livraison', type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    #[Assert\PositiveOrZero]
    private string $fraisLivraison = '0.00';

    #[ORM\Column(name: 'delai_estime_minutes', options: ['unsigned' => true, 'default' => 45])]
    #[Assert\Positive]
    private int $delaiEstimeMinutes = 45;

    #[ORM\Column(name: 'est_active', options: ['default' => true])]
    private bool $estActive = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomZone(): string
    {
        return $this->nomZone;
    }

    public function setNomZone(string $nomZone): self
    {
        $this->nomZone = $nomZone;

        return $this;
    }

    public function getCommune(): string
    {
        return $this->commune;
    }

    public function setCommune(string $commune): self
    {
        $this->commune = $commune;

        return $this;
    }

    public function getFraisLivraison(): string
    {
        return $this->fraisLivraison;
    }

    public function setFraisLivraison(string $fraisLivraison): self
    {
        $this->fraisLivraison = $fraisLivraison;

        return $this;
    }

    public function getDelaiEstimeMinutes(): int
    {
        return $this->delaiEstimeMinutes;
    }

    public function setDelaiEstimeMinutes(int $delaiEstimeMinutes): self
    {
        $this->delaiEstimeMinutes = $delaiEstimeMinutes;

        return $this;
    }

    public function isEstActive(): bool
    {
        return $this->estActive;
    }

    public function setEstActive(bool $estActive): self
    {
        $this->estActive = $estActive;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->nomZone, $this->commune);
    }
}
