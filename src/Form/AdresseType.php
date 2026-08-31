<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Adresse;
use App\Entity\ZoneLivraison;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdresseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, ['label' => 'Libelle', 'attr' => ['placeholder' => 'Domicile, bureau...']])
            ->add('zoneLivraison', EntityType::class, [
                'class' => ZoneLivraison::class,
                'label' => 'Zone de livraison',
                'choice_label' => fn (ZoneLivraison $zone): string => (string) $zone,
                'query_builder' => fn (EntityRepository $repository) => $repository->createQueryBuilder('z')
                    ->where('z.estActive = true')
                    ->orderBy('z.nomZone', 'ASC'),
            ])
            ->add('quartier', TextType::class, ['label' => 'Quartier'])
            ->add('rue', TextType::class, ['label' => 'Rue', 'required' => false])
            ->add('pointRepere', TextType::class, ['label' => 'Point de repere', 'required' => false])
            ->add('latitude', HiddenType::class, ['required' => false])
            ->add('longitude', HiddenType::class, ['required' => false])
            ->add('estPrincipale', CheckboxType::class, ['label' => 'Adresse principale', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Adresse::class]);
    }
}
