<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\StatutValidation;
use App\Enum\TypeFournisseur;
use App\Repository\FournisseurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FournisseurRepository::class)]
#[ORM\Table(name: 'fournisseur')]
#[ORM\Index(name: 'idx_fournisseur_statut', columns: ['statut_validation'])]
class Fournisseur
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Utilisateur::class, inversedBy: 'fournisseur')]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $utilisateur;

    #[ORM\Column(name: 'nom_commercial', length: 150)]
    #[Assert\NotBlank(message: 'Le nom commercial est obligatoire.')]
    #[Groups(['produit:lire'])]
    private string $nomCommercial = '';

    #[ORM\Column(name: 'type_fournisseur', enumType: TypeFournisseur::class)]
    private TypeFournisseur $typeFournisseur = TypeFournisseur::MAREYEUR;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: ZoneLivraison::class)]
    #[ORM\JoinColumn(name: 'zone_livraison_id', nullable: true, onDelete: 'SET NULL')]
    private ?ZoneLivraison $zoneLivraison = null;

    #[ORM\Column(name: 'statut_validation', enumType: StatutValidation::class, options: ['default' => 'EN_ATTENTE'])]
    private StatutValidation $statutValidation = StatutValidation::EN_ATTENTE;

    #[ORM\Column(name: 'note_moyenne', type: 'decimal', precision: 2, scale: 1, options: ['default' => '0.0'])]
    private string $noteMoyenne = '0.0';

    #[ORM\Column(name: 'date_validation', nullable: true)]
    private ?\DateTimeImmutable $dateValidation = null;

    /** @var Collection<int, Produit> */
    #[ORM\OneToMany(targetEntity: Produit::class, mappedBy: 'fournisseur', cascade: ['persist'])]
    private Collection $produits;

    public function __construct(Utilisateur $utilisateur)
    {
        $this->utilisateur = $utilisateur;
        $utilisateur->setFournisseur($this);
        $this->produits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->utilisateur->getId();
    }

    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    public function getNomCommercial(): string
    {
        return $this->nomCommercial;
    }

    public function setNomCommercial(string $nomCommercial): self
    {
        $this->nomCommercial = $nomCommercial;

        return $this;
    }

    public function getTypeFournisseur(): TypeFournisseur
    {
        return $this->typeFournisseur;
    }

    public function setTypeFournisseur(TypeFournisseur $typeFournisseur): self
    {
        $this->typeFournisseur = $typeFournisseur;

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

    public function getZoneLivraison(): ?ZoneLivraison
    {
        return $this->zoneLivraison;
    }

    public function setZoneLivraison(?ZoneLivraison $zoneLivraison): self
    {
        $this->zoneLivraison = $zoneLivraison;

        return $this;
    }

    public function getStatutValidation(): StatutValidation
    {
        return $this->statutValidation;
    }

    public function setStatutValidation(StatutValidation $statutValidation): self
    {
        $this->statutValidation = $statutValidation;
        if (StatutValidation::VALIDE === $statutValidation && null === $this->dateValidation) {
            $this->dateValidation = new \DateTimeImmutable();
        }

        return $this;
    }

    /** Regle de gestion 6 : seul un fournisseur valide peut publier des produits. */
    public function peutPublier(): bool
    {
        return StatutValidation::VALIDE === $this->statutValidation;
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

    public function getDateValidation(): ?\DateTimeImmutable
    {
        return $this->dateValidation;
    }

    /** @return Collection<int, Produit> */
    public function getProduits(): Collection
    {
        return $this->produits;
    }

    public function addProduit(Produit $produit): self
    {
        if (!$this->produits->contains($produit)) {
            $this->produits->add($produit);
            $produit->setFournisseur($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nomCommercial;
    }
}
