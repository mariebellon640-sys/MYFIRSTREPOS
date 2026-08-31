<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\UniteProduit;
use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: 'produit')]
#[ORM\Index(name: 'idx_produit_fournisseur', columns: ['fournisseur_id'])]
#[ORM\Index(name: 'idx_produit_categorie', columns: ['categorie_id'])]
#[ORM\Index(name: 'idx_produit_actif', columns: ['est_actif'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    shortName: 'Produit',
    description: 'Catalogue public des produits de la mer disponibles a la vente.',
    operations: [new GetCollection(), new Get()],
    normalizationContext: ['groups' => ['produit:lire']],
    paginationItemsPerPage: 24,
)]
#[ApiFilter(SearchFilter::class, properties: ['nom' => 'partial', 'espece' => 'partial', 'categorie.nom' => 'exact', 'fournisseur.nomCommercial' => 'partial'])]
#[ApiFilter(OrderFilter::class, properties: ['prixUnitaire', 'nom', 'dateMaj'])]
class Produit
{
    public const SEUIL_ALERTE_STOCK = '5.00';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[Groups(['produit:lire'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Fournisseur::class, inversedBy: 'produits')]
    #[ORM\JoinColumn(name: 'fournisseur_id', referencedColumnName: 'utilisateur_id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['produit:lire'])]
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne(targetEntity: CategorieProduit::class, inversedBy: 'produits')]
    #[ORM\JoinColumn(name: 'categorie_id', nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull(message: 'Choisissez une categorie.')]
    #[Groups(['produit:lire'])]
    private ?CategorieProduit $categorie = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le nom du produit est obligatoire.')]
    #[Groups(['produit:lire'])]
    private string $nom = '';

    #[ORM\Column(length: 150, options: ['comment' => 'Ex: Thon, Tilapia, Crevette'])]
    #[Assert\NotBlank(message: 'L\'espece est obligatoire.')]
    #[Groups(['produit:lire'])]
    private string $espece = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['produit:lire'])]
    private ?string $description = null;

    #[ORM\Column(name: 'prix_unitaire', type: 'decimal', precision: 10, scale: 2)]
    #[Assert\Positive(message: 'Le prix doit etre superieur a zero.')]
    #[Groups(['produit:lire'])]
    private string $prixUnitaire = '0.00';

    #[ORM\Column(enumType: UniteProduit::class, options: ['default' => 'KG'])]
    #[Groups(['produit:lire'])]
    private UniteProduit $unite = UniteProduit::KG;

    #[ORM\Column(name: 'preparations_dispo', length: 255, nullable: true, options: ['comment' => 'Ex: Entier,Filet,Decoupe'])]
    private ?string $preparationsDispo = 'Entier,Filet,Decoupe';

    #[ORM\Column(name: 'stock_disponible', type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    #[Assert\PositiveOrZero(message: 'Le stock ne peut pas etre negatif.')]
    #[Groups(['produit:lire'])]
    private string $stockDisponible = '0.00';

    #[ORM\Column(name: 'photo_url', length: 255, nullable: true)]
    #[Groups(['produit:lire'])]
    private ?string $photoUrl = null;

    #[ORM\Column(name: 'est_actif', options: ['default' => true])]
    private bool $estActif = true;

    #[ORM\Column(name: 'date_creation')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(name: 'date_maj')]
    private \DateTimeImmutable $dateMaj;

    /** @var Collection<int, LotPeche> */
    #[ORM\OneToMany(targetEntity: LotPeche::class, mappedBy: 'produit', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['dateHeureCapture' => 'DESC'])]
    private Collection $lots;

    /** @var Collection<int, Avis> */
    #[ORM\OneToMany(targetEntity: Avis::class, mappedBy: 'produit')]
    private Collection $avis;

    public function __construct()
    {
        $this->lots = new ArrayCollection();
        $this->avis = new ArrayCollection();
        $this->dateCreation = new \DateTimeImmutable();
        $this->dateMaj = new \DateTimeImmutable();
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

    public function getFournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseur $fournisseur): self
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getCategorie(): ?CategorieProduit
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieProduit $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getEspece(): string
    {
        return $this->espece;
    }

    public function setEspece(string $espece): self
    {
        $this->espece = $espece;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrixUnitaire(): string
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(string $prixUnitaire): self
    {
        $this->prixUnitaire = $prixUnitaire;

        return $this;
    }

    public function getUnite(): UniteProduit
    {
        return $this->unite;
    }

    public function setUnite(UniteProduit $unite): self
    {
        $this->unite = $unite;

        return $this;
    }

    public function getPreparationsDispo(): ?string
    {
        return $this->preparationsDispo;
    }

    public function setPreparationsDispo(?string $preparationsDispo): self
    {
        $this->preparationsDispo = $preparationsDispo;

        return $this;
    }

    /** @return list<string> */
    #[Groups(['produit:lire'])]
    public function getPreparations(): array
    {
        if (null === $this->preparationsDispo || '' === trim($this->preparationsDispo)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $this->preparationsDispo))));
    }

    public function getStockDisponible(): string
    {
        return $this->stockDisponible;
    }

    public function setStockDisponible(string $stockDisponible): self
    {
        $this->stockDisponible = $stockDisponible;

        return $this;
    }

    public function decrementerStock(string $quantite): self
    {
        $this->stockDisponible = number_format(max(0, (float) $this->stockDisponible - (float) $quantite), 2, '.', '');

        return $this;
    }

    public function incrementerStock(string $quantite): self
    {
        $this->stockDisponible = number_format((float) $this->stockDisponible + (float) $quantite, 2, '.', '');

        return $this;
    }

    /** Regle de gestion 1 : un produit ne peut etre commande que s'il est en stock. */
    public function estDisponible(string $quantiteDemandee = '0.01'): bool
    {
        return $this->estActif && (float) $this->stockDisponible >= (float) $quantiteDemandee;
    }

    public function estEnAlerteStock(): bool
    {
        return (float) $this->stockDisponible <= (float) self::SEUIL_ALERTE_STOCK;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function setPhotoUrl(?string $photoUrl): self
    {
        $this->photoUrl = $photoUrl;

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

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getDateMaj(): \DateTimeImmutable
    {
        return $this->dateMaj;
    }

    /** @return Collection<int, LotPeche> */
    public function getLots(): Collection
    {
        return $this->lots;
    }

    public function addLot(LotPeche $lot): self
    {
        if (!$this->lots->contains($lot)) {
            $this->lots->add($lot);
            $lot->setProduit($this);
        }

        return $this;
    }

    /** Lot actif le plus recent : celui qui porte la tracabilite affichee au client. */
    public function getLotCourant(): ?LotPeche
    {
        $courant = null;
        foreach ($this->lots as $lot) {
            if ($lot->isEstRetire()) {
                continue;
            }
            if (null === $courant || $lot->getDateHeureCapture() > $courant->getDateHeureCapture()) {
                $courant = $lot;
            }
        }

        return $courant;
    }

    #[Groups(['produit:lire'])]
    public function getIndiceFraicheur(): ?string
    {
        return $this->getLotCourant()?->getIndiceFraicheur();
    }

    /** @return Collection<int, Avis> */
    public function getAvis(): Collection
    {
        return $this->avis;
    }

    #[Groups(['produit:lire'])]
    public function getNoteMoyenne(): ?float
    {
        $notes = [];
        foreach ($this->avis as $avis) {
            if ($avis->estPublie()) {
                $notes[] = $avis->getNote();
            }
        }

        return [] === $notes ? null : round(array_sum($notes) / \count($notes), 1);
    }

    public function __toString(): string
    {
        return $this->nom;
    }
}
