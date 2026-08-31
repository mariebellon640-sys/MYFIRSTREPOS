<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LotPecheRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Lot d'arrivage : porte la tracabilite (lieu et heure de peche) et l'indice de
 * fraicheur recalcule periodiquement par FraicheurCalculateur.
 */
#[ORM\Entity(repositoryClass: LotPecheRepository::class)]
#[ORM\Table(name: 'lot_peche')]
#[ORM\Index(name: 'idx_lot_produit', columns: ['produit_id'])]
class LotPeche
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'lots')]
    #[ORM\JoinColumn(name: 'produit_id', nullable: false, onDelete: 'CASCADE')]
    private ?Produit $produit = null;

    #[ORM\Column(name: 'code_lot', length: 50, unique: true, options: ['comment' => 'Encode dans le QR code'])]
    private string $codeLot;

    #[ORM\Column(name: 'lieu_peche', length: 150)]
    #[Assert\NotBlank(message: 'Le lieu de peche est obligatoire pour la tracabilite.')]
    private string $lieuPeche = '';

    #[ORM\Column(name: 'date_heure_capture')]
    #[Assert\NotNull(message: 'La date et l\'heure de capture sont obligatoires.')]
    #[Assert\LessThanOrEqual('now', message: 'La date de capture ne peut pas etre dans le futur.')]
    private \DateTimeImmutable $dateHeureCapture;

    #[ORM\Column(name: 'date_heure_mise_en_vente')]
    private \DateTimeImmutable $dateHeureMiseEnVente;

    #[ORM\Column(name: 'quantite_kg', type: 'decimal', precision: 10, scale: 2)]
    #[Assert\Positive]
    private string $quantiteKg = '0.00';

    #[ORM\Column(name: 'indice_fraicheur', type: 'decimal', precision: 4, scale: 1, options: ['default' => '100.0', 'comment' => 'Decroit automatiquement depuis la capture (0-100)'])]
    private string $indiceFraicheur = '100.0';

    #[ORM\Column(name: 'est_retire', options: ['default' => false])]
    private bool $estRetire = false;

    public function __construct(?string $codeLot = null)
    {
        $this->codeLot = $codeLot ?? self::genererCodeLot();
        $this->dateHeureCapture = new \DateTimeImmutable();
        $this->dateHeureMiseEnVente = new \DateTimeImmutable();
    }

    public static function genererCodeLot(): string
    {
        return sprintf('LOT-%s-%s', (new \DateTimeImmutable())->format('Ymd'), strtoupper(bin2hex(random_bytes(3))));
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCodeLot(): string
    {
        return $this->codeLot;
    }

    public function getLieuPeche(): string
    {
        return $this->lieuPeche;
    }

    public function setLieuPeche(string $lieuPeche): self
    {
        $this->lieuPeche = $lieuPeche;

        return $this;
    }

    public function getDateHeureCapture(): \DateTimeImmutable
    {
        return $this->dateHeureCapture;
    }

    public function setDateHeureCapture(\DateTimeImmutable $dateHeureCapture): self
    {
        $this->dateHeureCapture = $dateHeureCapture;

        return $this;
    }

    public function getDateHeureMiseEnVente(): \DateTimeImmutable
    {
        return $this->dateHeureMiseEnVente;
    }

    public function setDateHeureMiseEnVente(\DateTimeImmutable $dateHeureMiseEnVente): self
    {
        $this->dateHeureMiseEnVente = $dateHeureMiseEnVente;

        return $this;
    }

    public function getQuantiteKg(): string
    {
        return $this->quantiteKg;
    }

    public function setQuantiteKg(string $quantiteKg): self
    {
        $this->quantiteKg = $quantiteKg;

        return $this;
    }

    public function getIndiceFraicheur(): string
    {
        return $this->indiceFraicheur;
    }

    public function setIndiceFraicheur(string $indiceFraicheur): self
    {
        $this->indiceFraicheur = $indiceFraicheur;

        return $this;
    }

    public function isEstRetire(): bool
    {
        return $this->estRetire;
    }

    public function setEstRetire(bool $estRetire): self
    {
        $this->estRetire = $estRetire;

        return $this;
    }

    public function getHeuresDepuisCapture(?\DateTimeImmutable $maintenant = null): float
    {
        $maintenant ??= new \DateTimeImmutable();

        return max(0.0, ($maintenant->getTimestamp() - $this->dateHeureCapture->getTimestamp()) / 3600);
    }

    public function __toString(): string
    {
        return $this->codeLot;
    }
}
