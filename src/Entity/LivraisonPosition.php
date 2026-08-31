<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LivraisonPositionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LivraisonPositionRepository::class)]
#[ORM\Table(name: 'livraison_position')]
#[ORM\Index(name: 'idx_position_livraison', columns: ['livraison_id', 'horodatage'])]
class LivraisonPosition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Livraison::class, inversedBy: 'positions')]
    #[ORM\JoinColumn(name: 'livraison_id', nullable: false, onDelete: 'CASCADE')]
    private ?Livraison $livraison = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7)]
    private string $latitude;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7)]
    private string $longitude;

    #[ORM\Column]
    private \DateTimeImmutable $horodatage;

    public function __construct(string $latitude, string $longitude)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->horodatage = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getLivraison(): ?Livraison
    {
        return $this->livraison;
    }

    public function setLivraison(?Livraison $livraison): self
    {
        $this->livraison = $livraison;

        return $this;
    }

    public function getLatitude(): string
    {
        return $this->latitude;
    }

    public function getLongitude(): string
    {
        return $this->longitude;
    }

    public function getHorodatage(): \DateTimeImmutable
    {
        return $this->horodatage;
    }
}
