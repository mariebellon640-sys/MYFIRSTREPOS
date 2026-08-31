<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Litige;
use App\Enum\TypeLitige;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LitigeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeLitige', EnumType::class, [
                'class' => TypeLitige::class,
                'label' => 'Motif',
                'choice_label' => fn (TypeLitige $type): string => $type->libelle(),
            ])
            ->add('description', TextareaType::class, ['label' => 'Description du probleme']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Litige::class]);
    }
}
