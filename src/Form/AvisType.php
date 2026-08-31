<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Avis;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AvisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', ChoiceType::class, [
                'label' => 'Note',
                'choices' => ['5 - Excellent' => 5, '4 - Tres bien' => 4, '3 - Correct' => 3, '2 - Decevant' => 2, '1 - Mauvais' => 1],
            ])
            ->add('commentaire', TextareaType::class, ['label' => 'Votre avis', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Avis::class]);
    }
}
