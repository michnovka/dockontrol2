<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Action;
use App\Entity\Button;
use App\Entity\Camera;
use App\Entity\Enum\ButtonIcon;
use App\Entity\Enum\ButtonStyle;
use App\Entity\Enum\ButtonType as ButtonTypeEnum;
use App\Entity\Permission;
use Elao\Enum\Bridge\Symfony\Form\Type\EnumType;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ButtonType extends AbstractType
{
    /**
     * @inheritdoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', TextType::class, [
                'label' => 'Button ID',
            ])
            ->add('name', TextType::class, [
                'label' => 'Name',
            ])
            ->add('nameSpecification', TextType::class, [
                'label' => 'Name specification',
                'required' => false,
            ])
            ->add('type', EnumType::class, [
                'class' => ButtonTypeEnum::class,
                'label' => 'Type',
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('action', EntityType::class, [
                'label' => 'Action',
                'class' => Action::class,
                'choice_label' => 'name',
                'required' => true,
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-choices' => 'true',
                    'data-choices-search-enabled' => 'true',
                ],
            ])
            ->add('permission', EntityType::class, [
                'label' => 'Permission',
                'class' => Permission::class,
                'choice_label' => 'name',
                'required' => false,
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-choices' => 'true',
                    'data-choices-search-enabled' => 'true',
                ],
            ])
            ->add('allow1MinOpen', ChoiceType::class, [
                'label' => 'Allow 1 min open',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('camera1', EntityType::class, [
                'label' => 'Camera 1',
                'class' => Camera::class,
                'choice_label' => 'nameId',
                'required' => false,
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-choices' => 'true',
                    'data-choices-search-enabled' => 'true',
                ],
            ])
            ->add('camera2', EntityType::class, [
                'label' => 'Camera 2',
                'class' => Camera::class,
                'choice_label' => 'nameId',
                'required' => false,
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-choices' => 'true',
                    'data-choices-search-enabled' => 'true',
                ],
            ])
            ->add('camera3', EntityType::class, [
                'label' => 'Camera 3',
                'class' => Camera::class,
                'choice_label' => 'nameId',
                'required' => false,
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-choices' => 'true',
                    'data-choices-search-enabled' => 'true',
                ],
            ])
            ->add('camera4', EntityType::class, [
                'label' => 'Camera 4',
                'class' => Camera::class,
                'choice_label' => 'nameId',
                'required' => false,
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-choices' => 'true',
                    'data-choices-search-enabled' => 'true',
                ],
            ])
            ->add('sortIndex', NumberType::class, [
                'label' => 'Sort index',
            ])
            ->add('buttonStyle', EnumType::class, [
                'label' => 'Button style',
                'class' => ButtonStyle::class,
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('icon', EnumType::class, [
                'label' => 'Icon',
                'class' => ButtonIcon::class,
                'attr' => [
                    'class' => 'choices-select',
                ],
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Button::class,
        ]);
    }
}
