<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Nuki;
use App\Entity\User;
use App\Form\Type\SearchableEntityType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class NukiType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /** @inheritDoc */
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
            ->add('name', TextType::class, [
                'label' => $this->translator->trans('dockontrol.settings.nuki.form.nuki_name'),
                'attr' => [
                    'placeholder' => $this->translator->trans('dockontrol.settings.nuki.form.nuki_name'),
                ],
            ])
            ->add('dockontrolNukiApiServer', TextType::class, [
                'label' => $this->translator->trans('dockontrol.settings.nuki.form.nuki_api_server'),
                'attr' => [
                    'placeholder' => $this->translator->trans('dockontrol.settings.nuki.form.nuki_api_server'),
                ],
            ])
            ->add('username', TextType::class, [
                'label' => $this->translator->trans('dockontrol.settings.nuki.form.username'),
                'attr' => [
                    'placeholder' => $this->translator->trans('dockontrol.settings.nuki.form.username'),
                ],
            ])
            ->add('password1', PasswordType::class, [
                'label' => $this->translator->trans('dockontrol.settings.nuki.form.password'),
                'attr' => [
                    'placeholder' => $this->translator->trans('dockontrol.settings.nuki.form.password'),
                ],
                'required' => $options['required_password'],
                'mapped' => false,
            ])
            ->add('canLock', ChoiceType::class, [
                'label' => $this->translator->trans('dockontrol.settings.nuki.form.can_lock'),
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
        ;
        if ($options['show_pin']) {
            $builder
                ->add('pin', TelType::class, [
                    'label' => $this->translator->trans('dockontrol.settings.nuki.form.pin'),
                    'required' => false,
                    'attr' => [
                        'placeholder' => $this->translator->trans('dockontrol.settings.nuki.form.pin'),
                        'maxlength' => 8,
                    ],
                    'mapped' => false,
                ])
            ;
        }
    }

    /** @inheritDoc */
    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Nuki::class,
            'required_password' => true,
            'show_pin' => false,
        ]);

        $resolver->setAllowedTypes('required_password', ['bool']);
    }
}
