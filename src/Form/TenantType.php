<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Override;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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
use Symfony\Contracts\Translation\TranslatorInterface;

class TenantType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    /**
    * @inheritDoc
    */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $showEditFormFields = $options['show_edit_form_fields'];
        $defaultPhonePrefix = $this->parameterBag->get('default_phone_prefix');

        $builder
            ->add('name', TextType::class, [
                'label' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.name'),
                'attr' => [
                    'placeholder' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.name'),
                ],
            ]);
        if ($showEditFormFields) {
            $builder
                ->add('email', RepeatedType::class, [
                    'type' => EmailType::class,
                    'invalid_message' => 'The email fields must match.',
                    'first_options' => [
                        'label' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.email'),
                        'attr' => [
                            'autocomplete' => 'new-password',
                            'placeholder' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.email'),
                        ],
                    ],
                    'second_options' => [
                        'label' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.repeat_email'),
                        'attr' => [
                            'autocomplete' => 'new-password',
                            'placeholder' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.repeat_email'),
                        ],
                    ],
                    'required' => true,
                ]);
        } else {
            $builder
                ->add('email', EmailType::class, [
                    'label' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.email'),
                    'attr' => [
                        'placeholder' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.email'),
                    ],
                ])
                ->add('enabled', ChoiceType::class, [
                    'label' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.enabled'),
                    'choices' => [
                        'Yes' => true,
                        'No' => false,
                    ],
                ])
            ;
        }
        $builder
            ->add('phone', TextType::class, [
                'label' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.phone'),
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 9, minMessage: 'Phone must be at least 9 characters long.'),
                ],
                'attr' => [
                    'placeholder' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.phone'),
                    'class' => 'phone-number',
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'first_options' => [
                    'label' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.password'),
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'placeholder' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.password'),
                    ],
                ],
                'second_options' => [
                    'label' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.repeat_password'),
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'placeholder' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.repeat_password'),
                    ],
                ],
                'required' => $showEditFormFields,
                'mapped' => false,
            ])
            ->add('phoneCountryPrefix', IntegerType::class, [
                'label' => false,
                'required' => true,
                'attr' => [
                    'value' => $showEditFormFields ? $defaultPhonePrefix : $options['data']->getPhoneCountryPrefix(),
                    'placeholder' => $this->translator->trans('dockontrol.settings.apartment.create_and_edit_modal.country_code'),
                    'class' => 'country-prefix text-center',
                ],
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'show_edit_form_fields' => true,
        ]);
    }
}
