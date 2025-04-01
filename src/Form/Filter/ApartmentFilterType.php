<?php

declare(strict_types=1);

namespace App\Form\Filter;

use App\Entity\Building;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApartmentFilterType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Name',
            ])
            ->add('building', EntityType::class, [
                'class' => Building::class,
                'choice_label' => 'name',
                'label' => 'Building',
                'required' => false,
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-choices' => 'true',
                    'data-choices-search-enabled' => 'true',
                ],
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}
