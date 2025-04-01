<?php

declare(strict_types=1);

namespace App\Form\Filter;

use App\Entity\Apartment;
use App\Entity\Building;
use App\Entity\User;
use App\Form\Type\DateRangeType;
use App\Form\Type\SearchableEntityType;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignupCodeFilterType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('hash', TextType::class, [
                'required' => false,
                'label' => 'Hash',
            ])

            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Unused' => 'Unused',
                    'Used' => 'Used',
                    'Expired' => 'Expired',
                ],
                'multiple' => true,
                'placeholder' => 'Status',
                'required' => false,
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-remove-items' => 'true',
                    'data-choices-remove-item-button' => 'true',
                ],
            ])
            ->add('timeCreated', DateRangeType::class, [
                'label' => 'Time created',
                'show_clear_button' => true,
                'required' => false,
                'mapped' => true,
            ])
            ->add('timeExpires', DateRangeType::class, [
                'label' => 'Time expires',
                'show_clear_button' => true,
                'required' => false,
                'mapped' => true,
            ])
            ->add('timeUsed', DateRangeType::class, [
                'label' => 'Time used',
                'show_clear_button' => true,
                'required' => false,
                'mapped' => true,
            ]);

        if ($options['is_super_admin']) {
            $builder
                ->add('admin', SearchableEntityType::class, [
                    'class' => User::class,
                    'required' => false,
                    'label' => 'Admin',
                    'multiple' => false,
                    'placeholder' => 'Admin',
                    'choice_label' => function (User $user) {
                        return $user->getTwigDisplayValue();
                    },
                    'attr' => [
                        'data-choices-search-enabled' => 'true',
                        'data-choices-search-choices' => 'true',
                        'data-choices-remove-items' => 'true',
                        'data-choices-remove-item-button' => 'true',
                    ],
                ])
                ->add('building', EntityType::class, [
                    'class' => Building::class,
                    'choice_label' => 'name',
                    'required' => false,
                    'multiple' => false,
                    'placeholder' => 'Building',
                    'attr' => [
                        'class' => 'choices-select',
                        'data-choices-search-enabled' => 'true',
                        'data-choices-search-choices' => 'true',
                        'data-choices-remove-items' => 'true',
                        'data-choices-remove-item-button' => 'true',
                    ],
                ])
                ->add('apartment', SearchableEntityType::class, [
                    'class' => Apartment::class,
                    'required' => false,
                    'label' => 'Apartment',
                    'multiple' => false,
                    'placeholder' => 'Apartment',
                    'choice_label' => function (Apartment $apartment) {
                        return $apartment->getTwigDisplayValue();
                    },
                    'attr' => [
                        'data-choices-search-enabled' => 'true',
                        'data-choices-search-choices' => 'true',
                        'data-choices-remove-items' => 'true',
                        'data-choices-remove-item-button' => 'true',
                    ],
                ])
            ;
        }
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => false,
            'method' => 'GET',
            'is_super_admin' => false,
        ]);
    }
}
