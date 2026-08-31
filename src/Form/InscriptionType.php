<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\Role;
use App\Enum\TypeFournisseur;
use App\Enum\TypeVehicule;
use App\Form\Model\InscriptionModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, ['label' => 'Prenom'])
            ->add('nom', TextType::class, ['label' => 'Nom'])
            ->add('email', EmailType::class, ['label' => 'Adresse e-mail'])
            ->add('telephone', TelType::class, ['label' => 'Telephone', 'attr' => ['placeholder' => '0341234567']])
            ->add('motDePasse', PasswordType::class, ['label' => 'Mot de passe'])
            ->add('role', EnumType::class, [
                'class' => Role::class,
                'label' => 'Je m\'inscris en tant que',
                'choices' => [Role::CLIENT, Role::FOURNISSEUR, Role::LIVREUR],
                'choice_label' => fn (Role $role): string => $role->libelle(),
                'expanded' => true,
            ])
            ->add('nomCommercial', TextType::class, ['label' => 'Nom commercial', 'required' => false])
            ->add('typeFournisseur', EnumType::class, [
                'class' => TypeFournisseur::class,
                'label' => 'Type de fournisseur',
                'required' => false,
                'placeholder' => 'Choisir...',
            ])
            ->add('typeVehicule', EnumType::class, [
                'class' => TypeVehicule::class,
                'label' => 'Vehicule',
                'required' => false,
                'placeholder' => 'Choisir...',
            ])
            ->add('numeroPermis', TextType::class, ['label' => 'Numero de permis', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => InscriptionModel::class]);
    }
}
