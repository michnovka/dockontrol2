<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Camera;
use App\Entity\DockontrolNode;
use App\Entity\Permission;
use App\Form\Type\JsonType;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CameraType extends AbstractType
{
    /**
     * @inheritdoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nameId', TextType::class, [
                'label' => 'Name',
            ])
            ->add('friendlyName', TextType::class, [
                'label' => 'Friendly name',
            ])
            ->add('dockontrolNode', EntityType::class, [
                'class' => DockontrolNode::class,
                'label' => 'DOCKontrol node',
                'multiple' => false,
                'placeholder' => 'DOCKontrol node',
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-enabled' => 'true',
                    'data-choices-search-choices' => 'true',
                ],
            ])
            ->add('permissionRequired', EntityType::class, [
                'label' => 'Permission required',
                'class' => Permission::class,
                'choice_label' => 'name',
                'multiple' => false,
                'placeholder' => 'Choose permission required',
                'required' => false,
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-choices' => 'true',
                    'data-choices-search-enabled' => 'true',
                ],
            ])
            ->add('dockontrolNodePayload', JsonType::class, [
                'label' => 'Action payload',
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Camera::class,
        ]);
    }
}
