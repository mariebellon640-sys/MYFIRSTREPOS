<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PanierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PanierRepository::class)]
#[ORM\Table(name: 'panier')]
class Panier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'utilisateur_id', nullable: false, onDelete: 'CASCADE')]
    private Client $client;

    #[ORM\Column(name: 'date_creation')]
    private \DateTimeImmutable $dateCreation;

    /** @var Collection<int, PanierItem> */
    #[ORM\OneToMany(targetEntity: PanierItem::class, mappedBy: 'panier', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->dateCreation = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    /** @return Collection<int, PanierItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(PanierItem $item): self
    {
        foreach ($this->items as $existant) {
            if ($existant->correspond($item->getProduit(), $item->getPreparationChoisie())) {
                $existant->ajouterQuantite($item->getQuantite());

                return $this;
            }
        }
        $this->items->add($item);
        $item->setPanier($this);

        return $this;
    }

    public function removeItem(PanierItem $item): self
    {
        $this->items->removeElement($item);

        return $this;
    }

    public function vider(): self
    {
        $this->items->clear();

        return $this;
    }

    public function estVide(): bool
    {
        return $this->items->isEmpty();
    }

    public function getNombreArticles(): int
    {
        return $this->items->count();
    }

    public function getMontantProduits(): string
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += (float) $item->getSousTotal();
        }

        return number_format($total, 2, '.', '');
    }
}
