<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\StatutCommande;
use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: 'commande')]
#[ORM\Index(name: 'idx_commande_client', columns: ['client_id'])]
#[ORM\Index(name: 'idx_commande_statut', columns: ['statut'])]
#[ORM\HasLifecycleCallbacks]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'numero_commande', length: 30, unique: true)]
    private string $numeroCommande;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'commandes')]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'utilisateur_id', nullable: false, onDelete: 'RESTRICT')]
    private Client $client;

    #[ORM\ManyToOne(targetEntity: Adresse::class)]
    #[ORM\JoinColumn(name: 'adresse_livraison_id', nullable: false, onDelete: 'RESTRICT')]
    private Adresse $adresseLivraison;

    #[ORM\ManyToOne(targetEntity: ZoneLivraison::class)]
    #[ORM\JoinColumn(name: 'zone_livraison_id', nullable: false, onDelete: 'RESTRICT')]
    private ZoneLivraison $zoneLivraison;

    #[ORM\ManyToOne(targetEntity: CodePromo::class)]
    #[ORM\JoinColumn(name: 'code_promo_id', nullable: true, onDelete: 'SET NULL')]
    private ?CodePromo $codePromo = null;

    #[ORM\Column(enumType: StatutCommande::class, options: ['default' => 'EN_ATTENTE'])]
    private StatutCommande $statut = StatutCommande::EN_ATTENTE;

    #[ORM\Column(name: 'montant_produits', type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $montantProduits = '0.00';

    #[ORM\Column(name: 'frais_livraison', type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $fraisLivraison = '0.00';

    #[ORM\Column(name: 'montant_reduction', type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $montantReduction = '0.00';

    #[ORM\Column(name: 'montant_total', type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $montantTotal = '0.00';

    #[ORM\Column(name: 'creneau_livraison_debut', nullable: true)]
    private ?\DateTimeImmutable $creneauLivraisonDebut = null;

    #[ORM\Column(name: 'creneau_livraison_fin', nullable: true)]
    private ?\DateTimeImmutable $creneauLivraisonFin = null;

    #[ORM\Column(name: 'date_commande')]
    private \DateTimeImmutable $dateCommande;

    #[ORM\Column(name: 'date_maj')]
    private \DateTimeImmutable $dateMaj;

    /** @var Collection<int, LigneCommande> */
    #[ORM\OneToMany(targetEntity: LigneCommande::class, mappedBy: 'commande', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignes;

    #[ORM\OneToOne(targetEntity: Livraison::class, mappedBy: 'commande', cascade: ['persist', 'remove'])]
    private ?Livraison $livraison = null;

    #[ORM\OneToOne(targetEntity: Paiement::class, mappedBy: 'commande', cascade: ['persist', 'remove'])]
    private ?Paiement $paiement = null;

    public function __construct(Client $client, Adresse $adresseLivraison, ZoneLivraison $zoneLivraison, ?string $numeroCommande = null)
    {
        $this->client = $client;
        $this->adresseLivraison = $adresseLivraison;
        $this->zoneLivraison = $zoneLivraison;
        $this->numeroCommande = $numeroCommande ?? self::genererNumero();
        $this->dateCommande = new \DateTimeImmutable();
        $this->dateMaj = new \DateTimeImmutable();
        $this->lignes = new ArrayCollection();
    }

    public static function genererNumero(): string
    {
        return sprintf('CMD-%s-%s', (new \DateTimeImmutable())->format('Ymd'), strtoupper(bin2hex(random_bytes(3))));
    }

    #[ORM\PreUpdate]
    public function toucherDateMaj(): void
    {
        $this->dateMaj = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroCommande(): string
    {
        return $this->numeroCommande;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getAdresseLivraison(): Adresse
    {
        return $this->adresseLivraison;
    }

    public function getZoneLivraison(): ZoneLivraison
    {
        return $this->zoneLivraison;
    }

    public function getCodePromo(): ?CodePromo
    {
        return $this->codePromo;
    }

    public function setCodePromo(?CodePromo $codePromo): self
    {
        $this->codePromo = $codePromo;

        return $this;
    }

    public function getStatut(): StatutCommande
    {
        return $this->statut;
    }

    public function setStatut(StatutCommande $statut): self
    {
        $this->statut = $statut;
        $this->dateMaj = new \DateTimeImmutable();

        return $this;
    }

    public function getMontantProduits(): string
    {
        return $this->montantProduits;
    }

    public function setMontantProduits(string $montantProduits): self
    {
        $this->montantProduits = $montantProduits;

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

    public function getMontantReduction(): string
    {
        return $this->montantReduction;
    }

    public function setMontantReduction(string $montantReduction): self
    {
        $this->montantReduction = $montantReduction;

        return $this;
    }

    public function getMontantTotal(): string
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(string $montantTotal): self
    {
        $this->montantTotal = $montantTotal;

        return $this;
    }

    public function recalculerMontants(): self
    {
        $produits = 0.0;
        foreach ($this->lignes as $ligne) {
            $produits += (float) $ligne->getSousTotal();
        }
        $this->montantProduits = number_format($produits, 2, '.', '');
        $total = $produits + (float) $this->fraisLivraison - (float) $this->montantReduction;
        $this->montantTotal = number_format(max(0, $total), 2, '.', '');

        return $this;
    }

    public function getCreneauLivraisonDebut(): ?\DateTimeImmutable
    {
        return $this->creneauLivraisonDebut;
    }

    public function getCreneauLivraisonFin(): ?\DateTimeImmutable
    {
        return $this->creneauLivraisonFin;
    }

    public function setCreneauLivraison(?\DateTimeImmutable $debut, ?\DateTimeImmutable $fin): self
    {
        $this->creneauLivraisonDebut = $debut;
        $this->creneauLivraisonFin = $fin;

        return $this;
    }

    public function getDateCommande(): \DateTimeImmutable
    {
        return $this->dateCommande;
    }

    public function getDateMaj(): \DateTimeImmutable
    {
        return $this->dateMaj;
    }

    /** @return Collection<int, LigneCommande> */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(LigneCommande $ligne): self
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setCommande($this);
        }

        return $this;
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

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(?Paiement $paiement): self
    {
        $this->paiement = $paiement;

        return $this;
    }

    public function estAnnulableParClient(): bool
    {
        return $this->statut->estAnnulableParClient();
    }

    /** Fournisseurs concernes par la commande (repartition multi-fournisseurs). */
    public function concerneFournisseur(Fournisseur $fournisseur): bool
    {
        foreach ($this->lignes as $ligne) {
            if ($ligne->getFournisseur()->getId() === $fournisseur->getId()) {
                return true;
            }
        }

        return false;
    }

    public function __toString(): string
    {
        return $this->numeroCommande;
    }
}
