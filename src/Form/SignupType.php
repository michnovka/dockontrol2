<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Apartment;
use App\Entity\User;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class SignupType extends AbstractType
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultPhonePrefix = $this->parameterBag->get('default_phone_prefix');

        $builder
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat password'],
                'required' => true,
                'mapped' => false,
            ])
            ->add('phoneCountryPrefix', IntegerType::class, [
                'label' => false,
                'attr' => [
                    'value' => $defaultPhonePrefix,
                    'class' => 'country-prefix text-center',
                ],
                'constraints' => [
                    new Range(min: 1, max: 9999),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('email', RepeatedType::class, [
                'type' => EmailType::class,
                'invalid_message' => 'The e-mail fields must match.',
                'first_options' => ['label' => 'E-mail'],
                'second_options' => ['label' => 'Repeat e-mail'],
                'label' => 'E-mail',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control phone-number',
                ],
            ])
            ->add('apartment', EntityType::class, [
                'label' => 'Apartment',
                'class' => Apartment::class,
                'choice_label' => function (Apartment $apartment): string {
                    return $apartment->getName() . ' (' . $apartment->getBuilding()->getName() . ') ';
                },
                'attr' => [
                    'class' => 'choices-select',
                ],
                'disabled' => true,
                'mapped' => true,
                'data' => $options['apartment'],
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'apartment' => null,
        ]);

        $resolver->setAllowedTypes('apartment', ['null', Apartment::class]);
    }
}
