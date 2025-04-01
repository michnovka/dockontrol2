<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Apartment;
use App\Entity\Enum\ButtonPressType;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Form\Type\SearchableEntityType;
use Elao\Enum\Bridge\Symfony\Form\Type\EnumType;
use InvalidArgumentException;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class UserType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $builder->getData();
        $userRoleChoices = $options['user_role_choices'];
        $isCreateNewForm = $options['is_create_new_form'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone',
                'attr' => [
                    'class' => 'phone-number',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 9, minMessage: 'Phone must be at least 9 characters long.'),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat password'],
                'required' => !$options['edit_password'],
                'mapped' => false,
            ])
            ->add('buttonPressType', EnumType::class, [
                'class' => ButtonPressType::class,
                'label' => 'Button press type',
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('enabled', ChoiceType::class, [
                'label' => 'Enabled',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'disabled' => $options['allowEnabledCheckbox'],
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('hasCameraAccess', ChoiceType::class, [
                'label' => 'Has camera access',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'attr' => [
                    'class' => 'choices-select',
                ],
                'disabled' => $user->isTenant(),
            ])
            ->add('canCreateGuests', ChoiceType::class, [
                'label' => 'Can create guests',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'attr' => [
                    'class' => 'choices-select',
                ],
                'disabled' => $user->isTenant(),
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
                'disabled' => $user->isTenant(),
                'attr' => [
                    'data-choices-search-enabled' => 'true',
                    'data-choices-search-choices' => 'true',
                    'data-choices-remove-items' => 'true',
                    'data-choices-remove-item-button' => 'true',
                ],
                'data' => $options['apartment'],
            ]);

        if ($isCreateNewForm) {
            $builder->add('role', EnumType::class, [
                'label' => 'User role',
                'class' => UserRole::class,
                'multiple' => false,
                'choices' => $this->getChoicesArrayForUserRole($userRoleChoices),
                'attr' => [
                    'class' => 'choices-select',
                ],
            ]);
        } else {
            $builder->remove('landlord');
        }

            $builder->add('customCarEnterDetails', ChoiceType::class, [
                'label' => 'Custom car enter details',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('carEnterExitAllowed', ChoiceType::class, [
                'label' => 'Allow car enter/exit?',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'attr' => [
                    'class' => 'choices-select',
                ],
                'disabled' => $options['allow_edit_field'],
            ])
            ->add('carEnterExitShow', ChoiceType::class, [
                'label' => 'Show car enter/exit?',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('disableAutomaticallyDueToInactivity', ChoiceType::class, [
                'label' => 'Auto-disable on inactivity?',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'disabled' => $options['allow_edit_field'],
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('phoneCountryPrefix', IntegerType::class, [
                'label' => false,
                'required' => true,
                'attr' => [
                    'placeholder' => 'Prefix',
                    'class' => 'country-prefix text-center',
                ],
                'constraints' => [
                    new Range(min: 1, max: 9999),
                ],
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'edit_password' => false,
            'allow_edit_field' => false,
            'user_role_choices' => 'all',
            'apartment' => null,
            'allowEnabledCheckbox' => false,
            'is_create_new_form' => false,
        ]);

        $resolver->setAllowedTypes('user_role_choices', 'string');
        $resolver->setAllowedTypes('apartment', ['null', Apartment::class]);
    }

    /**
     * @return UserRole[]
     */
    private function getChoicesArrayForUserRole(string $userRoleChoices): array
    {
        if ($userRoleChoices === 'limited') {
            return [
                UserRole::TENANT,
                UserRole::LANDLORD,
            ];
        } elseif ($userRoleChoices === 'all') {
            return UserRole::cases();
        } else {
            throw new InvalidArgumentException('Invalid value for option user_role_choices.');
        }
    }
}
