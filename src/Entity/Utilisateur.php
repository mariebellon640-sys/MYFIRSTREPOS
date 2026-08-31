<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Langue;
use App\Enum\Role;
use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
#[ORM\Index(name: 'idx_utilisateur_role', columns: ['role'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse e-mail est deja utilisee.')]
#[UniqueEntity(fields: ['telephone'], message: 'Ce numero de telephone est deja utilise.')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 100)]
    private string $nom = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prenom est obligatoire.')]
    #[Assert\Length(max: 100)]
    private string $prenom = '';

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L\'adresse e-mail est obligatoire.')]
    #[Assert\Email(message: 'Adresse e-mail invalide.')]
    private string $email = '';

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank(message: 'Le numero de telephone est obligatoire.')]
    #[Assert\Regex(pattern: '/^\+?[0-9 ]{9,20}$/', message: 'Numero de telephone invalide.')]
    private string $telephone = '';

    #[ORM\Column(name: 'mot_de_passe', length: 255)]
    private string $motDePasse = '';

    #[ORM\Column(enumType: Role::class, options: ['default' => 'CLIENT'])]
    private Role $role = Role::CLIENT;

    #[ORM\Column(name: 'photo_profil', length: 255, nullable: true)]
    private ?string $photoProfil = null;

    #[ORM\Column(name: 'langue_preferee', enumType: Langue::class, options: ['default' => 'FR'])]
    private Langue $languePreferee = Langue::FR;

    #[ORM\Column(name: 'est_verifie', options: ['default' => false])]
    private bool $estVerifie = false;

    #[ORM\Column(name: 'est_actif', options: ['default' => true])]
    private bool $estActif = true;

    #[ORM\Column(name: 'date_creation')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(name: 'date_maj')]
    private \DateTimeImmutable $dateMaj;

    /** Code OTP de verification du compte (SMS ou e-mail). */
    #[ORM\Column(name: 'code_otp', length: 6, nullable: true)]
    private ?string $codeOtp = null;

    #[ORM\Column(name: 'code_otp_expire_le', nullable: true)]
    private ?\DateTimeImmutable $codeOtpExpireLe = null;

    #[ORM\Column(name: 'jeton_reinitialisation', length: 100, nullable: true)]
    private ?string $jetonReinitialisation = null;

    #[ORM\Column(name: 'jeton_expire_le', nullable: true)]
    private ?\DateTimeImmutable $jetonExpireLe = null;

    /** @var Collection<int, Adresse> */
    #[ORM\OneToMany(targetEntity: Adresse::class, mappedBy: 'utilisateur', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $adresses;

    /** @var Collection<int, Notification> */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'utilisateur', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $notifications;

    #[ORM\OneToOne(targetEntity: Client::class, mappedBy: 'utilisateur', cascade: ['persist', 'remove'])]
    private ?Client $client = null;

    #[ORM\OneToOne(targetEntity: Fournisseur::class, mappedBy: 'utilisateur', cascade: ['persist', 'remove'])]
    private ?Fournisseur $fournisseur = null;

    #[ORM\OneToOne(targetEntity: Livreur::class, mappedBy: 'utilisateur', cascade: ['persist', 'remove'])]
    private ?Livreur $livreur = null;

    public function __construct()
    {
        $this->adresses = new ArrayCollection();
        $this->notifications = new ArrayCollection();
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

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNomComplet(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getMotDePasse(): string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): self
    {
        $this->motDePasse = $motDePasse;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->motDePasse;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): self
    {
        $this->role = $role;

        return $this;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return array_values(array_unique(['ROLE_USER', $this->role->securityRole()]));
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
    }

    public function getPhotoProfil(): ?string
    {
        return $this->photoProfil;
    }

    public function setPhotoProfil(?string $photoProfil): self
    {
        $this->photoProfil = $photoProfil;

        return $this;
    }

    public function getLanguePreferee(): Langue
    {
        return $this->languePreferee;
    }

    public function setLanguePreferee(Langue $languePreferee): self
    {
        $this->languePreferee = $languePreferee;

        return $this;
    }

    public function isEstVerifie(): bool
    {
        return $this->estVerifie;
    }

    public function setEstVerifie(bool $estVerifie): self
    {
        $this->estVerifie = $estVerifie;

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

    public function getCodeOtp(): ?string
    {
        return $this->codeOtp;
    }

    public function setCodeOtp(?string $codeOtp, ?\DateTimeImmutable $expireLe = null): self
    {
        $this->codeOtp = $codeOtp;
        $this->codeOtpExpireLe = $expireLe;

        return $this;
    }

    public function getCodeOtpExpireLe(): ?\DateTimeImmutable
    {
        return $this->codeOtpExpireLe;
    }

    public function getJetonReinitialisation(): ?string
    {
        return $this->jetonReinitialisation;
    }

    public function setJetonReinitialisation(?string $jeton, ?\DateTimeImmutable $expireLe = null): self
    {
        $this->jetonReinitialisation = $jeton;
        $this->jetonExpireLe = $expireLe;

        return $this;
    }

    public function getJetonExpireLe(): ?\DateTimeImmutable
    {
        return $this->jetonExpireLe;
    }

    /** @return Collection<int, Adresse> */
    public function getAdresses(): Collection
    {
        return $this->adresses;
    }

    public function addAdresse(Adresse $adresse): self
    {
        if (!$this->adresses->contains($adresse)) {
            $this->adresses->add($adresse);
            $adresse->setUtilisateur($this);
        }

        return $this;
    }

    public function removeAdresse(Adresse $adresse): self
    {
        $this->adresses->removeElement($adresse);

        return $this;
    }

    public function getAdressePrincipale(): ?Adresse
    {
        foreach ($this->adresses as $adresse) {
            if ($adresse->isEstPrincipale()) {
                return $adresse;
            }
        }

        return $this->adresses->first() ?: null;
    }

    /** @return Collection<int, Notification> */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUtilisateur($this);
        }

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
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

    public function getLivreur(): ?Livreur
    {
        return $this->livreur;
    }

    public function setLivreur(?Livreur $livreur): self
    {
        $this->livreur = $livreur;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getNomComplet();
    }
}
