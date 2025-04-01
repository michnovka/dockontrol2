<?php

declare(strict_types=1);

namespace App\Form\Filter\APILog\Succeeded;

use App\Entity\DockontrolNode;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;

class DockontrolNodeAPILogSuccessFilterType extends AbstractAPILogSuccessFilterType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('dockontrolNode', EntityType::class, [
                'class' => DockontrolNode::class,
                'required' => false,
                'label' => 'DOCKontrol node',
                'multiple' => false,
                'placeholder' => 'DOCKontrol node',
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-enabled' => 'true',
                    'data-choices-search-choices' => 'true',
                ],
            ]);
    }
}
