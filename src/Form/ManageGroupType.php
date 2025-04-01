<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Group;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ManageGroupType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('groups', EntityType::class, [
                'class' => Group::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'mapped' => true,
                'data' => $options['groups'],
                'label' => false,
                'disabled' => $options['disabled_edit'],
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-choices' => 'true',
                    'data-clear-choices' => 'true',
                    'data-choices-remove-items' => 'true',
                    'data-choices-remove-item-button' => 'true',
                ],
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'groups' => null,
            'disabled_edit' => false,
        ]);
    }
}
