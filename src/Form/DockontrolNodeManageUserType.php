<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\DockontrolNode;
use App\Entity\User;
use App\Form\Type\SearchableEntityType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DockontrolNodeManageUserType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('notifyWhenStatusChange', ChoiceType::class, [
                'label' => 'Notify when status change',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('usersToNotifyWhenStatusChanges', SearchableEntityType::class, [
                'class' => User::class,
                'label' => 'Whom to notify',
                'multiple' => true,
                'placeholder' => 'Users',
                'choice_label' => function (User $user) {
                    return $user->getTwigDisplayValue();
                },
                'attr' => [
                    'data-choices-search-enabled' => 'true',
                    'data-choices-search-choices' => 'true',
                    'data-choices-remove-items' => 'true',
                    'data-choices-remove-item-button' => 'true',
                ],
            ]);
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DockontrolNode::class,
        ]);
    }
}
