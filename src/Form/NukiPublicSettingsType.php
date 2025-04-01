<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Nuki;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NukiPublicSettingsType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => [
                    'placeholder' => 'Name',
                ],
            ])
            ->add('dockontrolNukiApiServer', TextType::class, [
                'label' => 'Nuki API server',
                'attr' => [
                    'placeholder' => 'Nuki API server',
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => [
                    'placeholder' => 'Username',
                ],
            ])
            ->add('password1', PasswordType::class, [
                'label' => 'Password',
                'attr' => [
                    'placeholder' => 'Password',
                    'autocomplete' => 'new-password',
                ],
                'required' => $options['required_password'],
                'mapped' => false,
            ])
            ->add('canLock', ChoiceType::class, [
                'label' => 'Can lock?',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
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
            'data_class' => Nuki::class,
            'required_password' => true,
        ]);

        $resolver->setAllowedTypes('required_password', ['bool']);
    }
}
