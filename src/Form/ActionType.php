<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Action;
use App\Entity\ActionQueueCronGroup;
use App\Entity\DockontrolNode;
use App\Entity\Enum\ActionType as ActionTypeEnum;
use App\Form\Type\JsonType;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionType extends AbstractType
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
            ->add('friendlyName', TextType::class, [
                'label' => 'Friendly name',
            ])
            ->add('type', EnumType::class, [
                'class' => ActionTypeEnum::class,
                'label' => 'Type',
                'choice_label' => function (ActionTypeEnum $actionType) {
                    return $actionType->getReadable();
                },
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('actionQueueCronGroup', EntityType::class, [
                'label' => 'Cron group',
                'class' => ActionQueueCronGroup::class,
                'choice_label' => 'name',
                'required' => false,
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('dockontrolNode', EntityType::class, [
                'class' => DockontrolNode::class,
                'choice_label' => 'name',
                'required' => false,
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('actionPayload', JsonType::class, [
                'label' => 'Action payload',
                'required' => false,
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Action::class,
        ]);
    }
}
