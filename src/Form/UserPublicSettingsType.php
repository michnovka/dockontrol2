<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Enum\ButtonPressType;
use App\Entity\User;
use Elao\Enum\Bridge\Symfony\Form\Type\EnumType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserPublicSettingsType extends AbstractType
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
            ->add('phone', NumberType::class, [
                'required' => false,
                'label' => $this->translator->trans('dockontrol.settings.my_profile.forms.phone'),
                'constraints' => [
                    new Length(min: 9),
                ],
                'attr' => [
                    'class' => 'phone-number',
                ],
            ])
            ->add('name', TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('dockontrol.settings.my_profile.forms.name'),
            ])
            ->add('buttonPressType', EnumType::class, [
                'class' => ButtonPressType::class,
                'label' => $this->translator->trans('dockontrol.settings.my_profile.forms.button_press_type'),
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-choices' => 'true',
                    'data-choices-search-enabled' => 'false',
                ],
            ])
            ->add('phoneCountryPrefix', IntegerType::class, [
                'label' => false,
                'attr' => [
                    'max' => 9999,
                    'class' => 'country-prefix text-center',
                ],
                'constraints' => [
                    new Range(min: 1, max: 9999),
                ],
            ])
        ;

        if ($options['show_car_enter_exit']) {
            $builder
                ->add('carEnterExitShow', ChoiceType::class, [
                    'choices' => [
                        'YES' => true,
                        'NO' => false,
                    ],
                    'label' => 'Show car enter/exit?',
                    'attr' => [
                        'class' => 'choices-select',
                    ],
                ])
            ;
        }
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'show_car_enter_exit' => true,
        ]);
    }
}
