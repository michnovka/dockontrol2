<?php

declare(strict_types=1);

namespace App\Form;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangePasswordType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'currentPassword',
                PasswordType::class,
                [
                    'label' => $this->translator->trans('dockontrol.settings.change_password.form.current_password'),
                    'required' => true,
                    'attr' => [
                        'placeholder' => $this->translator->trans('dockontrol.settings.change_password.form.current_password_place_holder'),
                    ],
                    'constraints' => new UserPassword(message: $this->translator->trans('dockontrol.settings.change_password.form.current_password_invalid_message')),
                ]
            )
            ->add(
                'newPassword',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'invalid_message' => $this->translator->trans('dockontrol.settings.change_password.form.repeated_new_password_invalid_message'),
                    'required' => true,
                    'first_options' => ['label' => $this->translator->trans('dockontrol.settings.change_password.form.new_password'), 'attr' => ['placeholder' => $this->translator->trans('dockontrol.settings.change_password.form.new_password_place_holder'), 'autocomplete' => 'off']],
                    'second_options' => ['label' => $this->translator->trans('dockontrol.settings.change_password.form.repeated_new_password'), 'attr' => ['placeholder' => $this->translator->trans('dockontrol.settings.change_password.form.repeated_new_password_place_holder'),'autocomplete' => 'off']],
                    'mapped' => false,
                ]
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
