<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\CanalNotification;
use App\Enum\TypeNotification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\Index(name: 'idx_notification_utilisateur', columns: ['utilisateur_id', 'statut_lecture'])]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(name: 'utilisateur_id', nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(enumType: TypeNotification::class)]
    private TypeNotification $type;

    #[ORM\Column(length: 150)]
    private string $titre;

    #[ORM\Column(type: 'text')]
    private string $contenu;

    #[ORM\Column(enumType: CanalNotification::class)]
    private CanalNotification $canal;

    #[ORM\Column(name: 'statut_lecture', options: ['default' => false])]
    private bool $statutLecture = false;

    #[ORM\Column(name: 'date_envoi')]
    private \DateTimeImmutable $dateEnvoi;

    public function __construct(Utilisateur $utilisateur, TypeNotification $type, string $titre, string $contenu, CanalNotification $canal = CanalNotification::PUSH)
    {
        $this->utilisateur = $utilisateur;
        $this->type = $type;
        $this->titre = $titre;
        $this->contenu = $contenu;
        $this->canal = $canal;
        $this->dateEnvoi = new \DateTimeImmutable();
    }

    public function getId(): ?string
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

    public function getType(): TypeNotification
    {
        return $this->type;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function getContenu(): string
    {
        return $this->contenu;
    }

    public function getCanal(): CanalNotification
    {
        return $this->canal;
    }

    public function isStatutLecture(): bool
    {
        return $this->statutLecture;
    }

    public function marquerCommeLue(): self
    {
        $this->statutLecture = true;

        return $this;
    }

    public function getDateEnvoi(): \DateTimeImmutable
    {
        return $this->dateEnvoi;
    }
}
