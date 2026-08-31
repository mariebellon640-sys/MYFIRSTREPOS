<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Enum\Role;
use App\Enum\TypeFournisseur;
use App\Enum\TypeVehicule;
use Symfony\Component\Validator\Constraints as Assert;

/** Donnees du formulaire d'inscription, communes aux trois profils publics. */
class InscriptionModel
{
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 100)]
    public string $nom = '';

    #[Assert\NotBlank(message: 'Le prenom est obligatoire.')]
    #[Assert\Length(max: 100)]
    public string $prenom = '';

    #[Assert\NotBlank(message: 'L\'adresse e-mail est obligatoire.')]
    #[Assert\Email(message: 'Cette adresse e-mail n\'est pas valide.')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Le numero de telephone est obligatoire.')]
    #[Assert\Regex(pattern: '/^(\+261|0)3[2-4|8]\d{7}$/', message: 'Numero malgache attendu, par exemple 0341234567.')]
    public string $telephone = '';

    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
    #[Assert\Length(min: 8, minMessage: 'Le mot de passe doit comporter au moins {{ limit }} caracteres.')]
    public string $motDePasse = '';

    public Role $role = Role::CLIENT;

    public ?TypeFournisseur $typeFournisseur = null;

    public ?string $nomCommercial = null;

    public ?TypeVehicule $typeVehicule = null;

    public ?string $numeroPermis = null;
}
