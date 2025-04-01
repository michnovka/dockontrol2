<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Apartment;
use App\Entity\Building;
use App\Entity\Group;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApartmentType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('building', EntityType::class, [
                'label' => 'Building',
                'class' => Building::class,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'attr' => [
                    'class' => 'choices-select',
                ],
                'required' => true,
            ])
            ->add('name', TextType::class, [
                'label' => 'Name',
            ])
            ->add('defaultGroup', EntityType::class, [
                'label' => 'Default group',
                'class' => Group::class,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'attr' => [
                    'class' => 'choices-select',
                ],
                'required' => false,
                'placeholder' => 'No default group',
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Apartment::class,
        ]);
    }
}
