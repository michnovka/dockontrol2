<?php

declare(strict_types=1);

namespace App\Form\Filter;

use App\Entity\Group;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BuildingFilterType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setMethod('GET');

        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Name',
            ])
            ->add('defaultGroup', EntityType::class, [
                'class' => Group::class,
                'required' => false,
                'label' => 'Default Group',
                'multiple' => false,
                'placeholder' => 'Default Group',
                'choice_label' => function (Group $user) {
                    return $user->getName();
                },
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-remove-items' => 'true',
                    'data-choices-remove-item-button' => 'true',
                ],
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => null,
        ]);
    }
}
