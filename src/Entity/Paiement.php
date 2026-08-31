<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ModePaiement;
use App\Enum\StatutPaiement;
use App\Repository\PaiementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
#[ORM\Table(name: 'paiement')]
#[ORM\Index(name: 'idx_paiement_statut', columns: ['statut'])]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Commande::class, inversedBy: 'paiement')]
    #[ORM\JoinColumn(name: 'commande_id', nullable: false, unique: true, onDelete: 'CASCADE')]
    private Commande $commande;

    #[ORM\Column(name: 'mode_paiement', enumType: ModePaiement::class)]
    private ModePaiement $modePaiement;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $montant;

    #[ORM\Column(enumType: StatutPaiement::class, options: ['default' => 'EN_ATTENTE'])]
    private StatutPaiement $statut = StatutPaiement::EN_ATTENTE;

    #[ORM\Column(name: 'reference_transaction', length: 100, nullable: true)]
    private ?string $referenceTransaction = null;

    #[ORM\Column(name: 'date_paiement', nullable: true)]
    private ?\DateTimeImmutable $datePaiement = null;

    #[ORM\Column(name: 'date_creation')]
    private \DateTimeImmutable $dateCreation;

    public function __construct(Commande $commande, ModePaiement $modePaiement)
    {
        $this->commande = $commande;
        $commande->setPaiement($this);
        $this->modePaiement = $modePaiement;
        $this->montant = $commande->getMontantTotal();
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): Commande
    {
        return $this->commande;
    }

    public function getModePaiement(): ModePaiement
    {
        return $this->modePaiement;
    }

    public function setModePaiement(ModePaiement $modePaiement): self
    {
        $this->modePaiement = $modePaiement;

        return $this;
    }

    public function getMontant(): string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getStatut(): StatutPaiement
    {
        return $this->statut;
    }

    public function setStatut(StatutPaiement $statut): self
    {
        $this->statut = $statut;
        if (StatutPaiement::CONFIRME === $statut && null === $this->datePaiement) {
            $this->datePaiement = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getReferenceTransaction(): ?string
    {
        return $this->referenceTransaction;
    }

    public function setReferenceTransaction(?string $referenceTransaction): self
    {
        $this->referenceTransaction = $referenceTransaction;

        return $this;
    }

    public function getDatePaiement(): ?\DateTimeImmutable
    {
        return $this->datePaiement;
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }
}
