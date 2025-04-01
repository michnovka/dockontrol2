<?php

declare(strict_types=1);

namespace App\Form\Filter;

use App\Entity\Enum\NukiAction;
use App\Entity\Enum\NukiStatus;
use App\Entity\Nuki;
use App\Entity\User;
use App\Form\Type\DateRangeType;
use App\Form\Type\SearchableEntityType;
use Elao\Enum\Bridge\Symfony\Form\Type\EnumType;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NukiLogFilterType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setMethod('GET');

        $builder
            ->add('time', DateRangeType::class, [
                'required' => false,
                'label' => 'Time',
                'show_clear_button' => true,
                'mapped' => true,
            ])
            ->add('user', SearchableEntityType::class, [
                'class' => User::class,
                'required' => false,
                'label' => 'User',
                'multiple' => false,
                'placeholder' => 'User',
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
            ->add('nuki', EntityType::class, [
                'class' => Nuki::class,
                'choice_label' => 'name',
                'required' => false,
                'label' => 'Nuki name',
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('status', EnumType::class, [
                'class' => NukiStatus::class,
                'required' => false,
                'label' => 'Status',
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('action', EnumType::class, [
                'class' => NukiAction::class,
                'required' => false,
                'label' => 'Action',
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => null,
        ]);
    }
}
