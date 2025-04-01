<?php

declare(strict_types=1);

namespace App\Form\Filter;

use App\Entity\Action;
use App\Entity\Enum\ActionQueueStatus;
use App\Entity\User;
use App\Form\Type\DateRangeType;
use App\Form\Type\SearchableEntityType;
use Elao\Enum\Bridge\Symfony\Form\Type\EnumType;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionQueueFilterType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setMethod('GET');

        $builder
            ->add('timeStart', DateRangeType::class, [
                'label' => 'Time start',
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
            ->add('action', EntityType::class, [
                'class' => Action::class,
                'choice_label' => 'friendlyName',
                'label' => 'Action',
                'required' => false,
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-choices' => 'true',
                    'data-choices-search-enabled' => 'true',
                ],
            ])
            ->add('status', EnumType::class, [
                'class' => ActionQueueStatus::class,
                'placeholder' => 'All',
                'required' => false,
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
            'data_class' => null,
            'csrf_protection' => false,
        ]);
    }
}
