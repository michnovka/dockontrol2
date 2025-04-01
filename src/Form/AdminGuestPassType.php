<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Guest;
use App\Entity\User;
use App\Form\Type\SearchableEntityType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminGuestPassType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', SearchableEntityType::class, [
                'class' => User::class,
                'required' => true,
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
            ->add('expires', ChoiceType::class, [
                'label' => 'Expires',
                'choices' => [
                    '1 Hour' => 1,
                    '24 Hour' => 24,
                    '2 Days' => 48,
                    '1 Week' => 168,
                ],
                'multiple' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('remainingActions', ChoiceType::class, [
                'label' => 'Maximum number of actions',
                'choices' => [
                    'unlimited' => '-1',
                    5 => 5,
                    10 => 10,
                    20 => 20,
                    50 => 50,
                ],
                'multiple' => false,
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('defaultLanguage', ChoiceType::class, [
                'label' => 'Default language',
                'choices' => [
                    'Czech' => 'cz',
                    'English' => 'en',
                ],
                'placeholder' => false,
                'required' => false,
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Note',
                'required' => false,
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Guest::class,
        ]);
    }
}
