<?php

declare(strict_types=1);

namespace App\Form;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResetPasswordType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $showPasswordLabel = $options['show_password_label'];

        $builder->add('password', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'The password fields must match.',
            'required' => true,
            'first_options' => ['label' => $showPasswordLabel ? 'Password' : false, 'attr' => ['placeholder' => $this->translator->trans('dockontrol.security.reset_password.new_password'), 'autocomplete' => 'off']],
            'second_options' => ['label' => $showPasswordLabel ? 'Repeat password' : false, 'attr' => ['placeholder' => $this->translator->trans('dockontrol.security.reset_password.repeat_new_password'),'autocomplete' => 'off']],
            'mapped' => false,
        ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'show_password_label' => true,
        ]);

        $resolver->setAllowedTypes('show_password_label', 'boolean');
    }
}
