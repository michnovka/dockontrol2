<?php

declare(strict_types=1);

namespace App\Form\Filter\APILog\Failed;

use App\Form\Type\DateRangeType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractAPILogFailedFilterType extends AbstractType
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
                'label' => 'Time',
                'show_clear_button' => true,
                'required' => false,
                'mapped' => true,
            ])
            ->add('ip', TextType::class, [
                'label' => 'IP address',
                'required' => false,
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => false,
        ]);
    }
}
