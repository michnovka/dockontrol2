<?php

declare(strict_types=1);

namespace App\Form\Filter;

use App\Form\Type\DateRangeType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailLogFilterType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setMethod('GET');

        $builder
            ->add('time', DateRangeType::class, [
                'required' => false,
                'label' => 'Time',
                'show_clear_button' => true,
                'mapped' => true,
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
