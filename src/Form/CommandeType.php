<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Adresse;
use App\Entity\Client;
use App\Enum\ModePaiement;
use App\Form\Model\CommandeModel;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $client = $options['client'];
        \assert($client instanceof Client);
        $utilisateurId = $client->getUtilisateur()->getId();

        $builder
            ->add('adresseLivraison', EntityType::class, [
                'class' => Adresse::class,
                'label' => 'Adresse de livraison',
                'choice_label' => fn (Adresse $adresse): string => (string) $adresse,
                'query_builder' => fn (EntityRepository $repository) => $repository->createQueryBuilder('a')
                    ->where('a.utilisateur = :utilisateur')
                    ->setParameter('utilisateur', $utilisateurId)
                    ->orderBy('a.estPrincipale', 'DESC'),
            ])
            ->add('modePaiement', EnumType::class, [
                'class' => ModePaiement::class,
                'label' => 'Mode de paiement',
                'choice_label' => fn (ModePaiement $mode): string => $mode->libelle(),
                'expanded' => true,
            ])
            ->add('numeroPayeur', TelType::class, [
                'label' => 'Numero Mobile Money',
                'required' => false,
                'help' => 'Requis pour MVola, Orange Money et Airtel Money.',
            ])
            ->add('creneauLivraison', DateTimeType::class, [
                'label' => 'Creneau de livraison souhaite',
                'widget' => 'single_text',
                'required' => false,
                'input' => 'datetime_immutable',
            ])
            ->add('codePromo', TextType::class, ['label' => 'Code promo', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CommandeModel::class]);
        $resolver->setRequired('client');
        $resolver->setAllowedTypes('client', Client::class);
    }
}
