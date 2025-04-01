<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Building;
use App\Entity\DockontrolNode;
use App\Entity\Enum\DockontrolNodeStatus;
use Elao\Enum\Bridge\Symfony\Form\Type\EnumType;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DockontrolNodeType extends AbstractType
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
            ])
            ->add('ip', TextType::class, [
                'label' => 'Ip',
            ]);

        if (!$options['editable']) {
            $builder
                ->add('status', EnumType::class, [
                    'class' => DockontrolNodeStatus::class,
                    'label' => 'Status',
                    'disabled' => $options['editable'],
                    'attr' => [
                        'class' => 'choices-select',
                    ],
                ]);
        }
        $builder
            ->add('comment', TextType::class, [
                'label' => 'Comment',
                'required' => false,
            ])
            ->add('enabled', ChoiceType::class, [
                'label' => 'Enabled',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('building', EntityType::class, [
                'label' => 'Building',
                'class' => Building::class,
                'choice_label' => function (Building $building): string {
                    return $building->getName();
                },
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
            'data_class' => DockontrolNode::class,
            'editable' => false,
        ]);
    }
}
