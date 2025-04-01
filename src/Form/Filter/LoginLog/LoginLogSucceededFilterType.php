<?php

declare(strict_types=1);

namespace App\Form\Filter\LoginLog;

use App\Entity\User;
use App\Form\Type\DateRangeType;
use App\Form\Type\SearchableEntityType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoginLogSucceededFilterType extends AbstractType
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
                'label' => 'Time',
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
            ->add('ip', TextType::class, [
                'label' => 'IP address',
                'required' => false,
            ])
            ->add('browser', TextType::class, [
                'label' => 'Browser',
                'required' => false,
            ])
            ->add('platform', TextType::class, [
                'label' => 'Platform',
                'required' => false,
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
