<?php

declare(strict_types=1);

namespace App\Form\Filter;

use App\Entity\User;
use App\Form\Type\DateRangeType;
use App\Form\Type\SearchableEntityType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApiKeyFilterType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('timeCreated', DateRangeType::class, [
                'label' => 'Time created',
                'show_clear_button' => true,
                'required' => false,
                'mapped' => true,
            ])
            ->add('timeLastUsed', DateRangeType::class, [
                'label' => 'Time last used',
                'show_clear_button' => true,
                'required' => false,
                'mapped' => true,
            ])
            ->add('user', SearchableEntityType::class, [
                'class' => User::class,
                'required' => false,
                'label' => 'User',
                'multiple' => false,
                'placeholder' => 'User',
                'choice_label' => function (User $apartment) {
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
