<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TypeReduction;
use App\Repository\CodePromoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CodePromoRepository::class)]
#[ORM\Table(name: 'code_promo')]
class CodePromo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    #[Assert\NotBlank]
    private string $code = '';

    #[ORM\Column(name: 'type_reduction', enumType: TypeReduction::class)]
    private TypeReduction $typeReduction = TypeReduction::POURCENTAGE;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\Positive]
    private string $valeur = '0.00';

    #[ORM\Column(name: 'date_debut')]
    private \DateTimeImmutable $dateDebut;

    #[ORM\Column(name: 'date_fin')]
    #[Assert\GreaterThan(propertyPath: 'dateDebut', message: 'La date de fin doit suivre la date de debut.')]
    private \DateTimeImmutable $dateFin;

    #[ORM\Column(name: 'utilisation_max', nullable: true, options: ['unsigned' => true])]
    private ?int $utilisationMax = null;

    #[ORM\Column(name: 'utilisation_actuelle', options: ['unsigned' => true, 'default' => 0])]
    private int $utilisationActuelle = 0;

    #[ORM\Column(name: 'est_actif', options: ['default' => true])]
    private bool $estActif = true;

    public function __construct()
    {
        $this->dateDebut = new \DateTimeImmutable();
        $this->dateFin = new \DateTimeImmutable('+30 days');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtoupper(trim($code));

        return $this;
    }

    public function getTypeReduction(): TypeReduction
    {
        return $this->typeReduction;
    }

    public function setTypeReduction(TypeReduction $typeReduction): self
    {
        $this->typeReduction = $typeReduction;

        return $this;
    }

    public function getValeur(): string
    {
        return $this->valeur;
    }

    public function setValeur(string $valeur): self
    {
        $this->valeur = $valeur;

        return $this;
    }

    public function getDateDebut(): \DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeImmutable $dateDebut): self
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): \DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeImmutable $dateFin): self
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getUtilisationMax(): ?int
    {
        return $this->utilisationMax;
    }

    public function setUtilisationMax(?int $utilisationMax): self
    {
        $this->utilisationMax = $utilisationMax;

        return $this;
    }

    public function getUtilisationActuelle(): int
    {
        return $this->utilisationActuelle;
    }

    public function incrementerUtilisation(): self
    {
        ++$this->utilisationActuelle;

        return $this;
    }

    public function isEstActif(): bool
    {
        return $this->estActif;
    }

    public function setEstActif(bool $estActif): self
    {
        $this->estActif = $estActif;

        return $this;
    }

    public function estUtilisable(?\DateTimeImmutable $maintenant = null): bool
    {
        $maintenant ??= new \DateTimeImmutable();

        if (!$this->estActif || $maintenant < $this->dateDebut || $maintenant > $this->dateFin) {
            return false;
        }

        return null === $this->utilisationMax || $this->utilisationActuelle < $this->utilisationMax;
    }

    public function calculerReduction(string $montant): string
    {
        $reduction = TypeReduction::POURCENTAGE === $this->typeReduction
            ? (float) $montant * (float) $this->valeur / 100
            : (float) $this->valeur;

        return number_format(min($reduction, (float) $montant), 2, '.', '');
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
