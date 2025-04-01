<?php

declare(strict_types=1);

namespace App\Form\Filter;

use App\Entity\Apartment;
use App\Entity\Enum\UserRole;
use App\Entity\Group;
use App\Entity\User;
use App\Form\Type\SearchableEntityType;
use Elao\Enum\Bridge\Symfony\Form\Type\EnumType;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFilterType extends AbstractType
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
            ->add('email', TextType::class, [
                'required' => false,
                'label' => 'E-mail',
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'label' => 'Phone',
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
            ->add('group', EntityType::class, [
                'class' => Group::class,
                'choice_label' => 'name',
                'required' => false,
                'multiple' => false,
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-enabled' => 'true',
                    'data-choices-search-choices' => 'true',
                    'data-choices-remove-items' => 'true',
                    'data-choices-remove-item-button' => 'true',
                ],
            ])
            ->add('role', EnumType::class, [
                'label' => 'User role',
                'class' => UserRole::class,
                'multiple' => false,
                'placeholder' => 'User Role',
                'attr' => [
                    'class' => 'choices-select',
                ],
                'required' => false,
            ])
            ->add('landlord', SearchableEntityType::class, [
                'class' => User::class,
                'required' => false,
                'label' => 'Landlord',
                'multiple' => false,
                'placeholder' => 'Landlord',
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
