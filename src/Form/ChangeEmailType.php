<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangeEmailType extends AbstractType
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
            ->add('email', RepeatedType::class, [
                'type' => EmailType::class,
                'invalid_message' => $this->translator->trans('dockontrol.settings.change_email.form.invalid_email_message'),
                'first_options' => ['label' => $this->translator->trans('dockontrol.settings.change_email.form.email'), 'data' => $options['current_user_email']],
                'second_options' => ['label' => $this->translator->trans('dockontrol.settings.change_email.form.repeated_email')],
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                ],
                'mapped' => false,
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'current_user_email' => null,
        ]);
    }
}
