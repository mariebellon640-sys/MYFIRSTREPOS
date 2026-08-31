<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\CategorieProduit;
use App\Entity\Produit;
use App\Enum\UniteProduit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['label' => 'Nom du produit'])
            ->add('espece', TextType::class, ['label' => 'Espece', 'attr' => ['placeholder' => 'Thon, capitaine, crevette...']])
            ->add('categorie', EntityType::class, [
                'class' => CategorieProduit::class,
                'label' => 'Categorie',
                'choice_label' => fn (CategorieProduit $categorie): string => (string) $categorie,
            ])
            ->add('description', TextareaType::class, ['label' => 'Description', 'required' => false])
            ->add('prixUnitaire', NumberType::class, ['label' => 'Prix unitaire (Ar)', 'scale' => 2])
            ->add('unite', EnumType::class, [
                'class' => UniteProduit::class,
                'label' => 'Unite de vente',
                'choice_label' => fn (UniteProduit $unite): string => $unite->libelle(),
            ])
            ->add('preparationsDispo', TextType::class, [
                'label' => 'Preparations proposees',
                'required' => false,
                'help' => 'Separees par une virgule : entier, vide, en filets, en darnes.',
            ])
            ->add('stockDisponible', NumberType::class, ['label' => 'Stock disponible', 'scale' => 2])
            ->add('photoUrl', UrlType::class, ['label' => 'Photo (URL)', 'required' => false])
            ->add('estActif', CheckboxType::class, ['label' => 'Publie au catalogue', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Produit::class]);
    }
}
