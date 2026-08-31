<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\LotPeche;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LotPecheType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lieuPeche', TextType::class, ['label' => 'Lieu de peche', 'attr' => ['placeholder' => 'Mahajanga, Toamasina, lac Alaotra...']])
            ->add('dateHeureCapture', DateTimeType::class, [
                'label' => 'Date et heure de capture',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('quantiteKg', NumberType::class, ['label' => 'Quantite (kg)', 'scale' => 2]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => LotPeche::class]);
    }
}
