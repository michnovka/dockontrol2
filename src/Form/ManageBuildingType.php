<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Building;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ManageBuildingType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('buildings', EntityType::class, [
                'class' => Building::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'label' => false,
                'data' => $options['buildings'],
                'mapped' => false,
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
            'buildings' => null,
            'disabled_edit' => false,
        ]);
    }
}
