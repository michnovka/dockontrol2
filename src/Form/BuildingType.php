<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Building;
use App\Entity\Group;
use App\Entity\Permission;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BuildingType extends AbstractType
{
    /**
     * @inheritdoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
            ])
            ->add('defaultGroup', EntityType::class, [
                'class' => Group::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select default group',
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])
            ->add('permissions', EntityType::class, [
                'class' => Permission::class,
                'choice_label' => 'name',
                'label' => 'Permissions',
                'multiple' => true,
                'expanded' => true,
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Building::class,
        ]);
    }
}
