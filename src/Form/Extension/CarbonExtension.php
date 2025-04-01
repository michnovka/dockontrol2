<?php

declare(strict_types=1);

namespace App\Form\Extension;

use App\Form\DataTransformer\CarbonImmutableToDateTimeTransformer;
use App\Form\DataTransformer\CarbonToDateTimeTransformer;
use Override;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CarbonExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->addAllowedValues('input', ['carbon', 'carbon_immutable']);
        $resolver->setDefault('input', 'carbon_immutable');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ('carbon_immutable' === $options['input']) {
            $builder->addModelTransformer(new CarbonImmutableToDateTimeTransformer());
        } elseif ('carbon' === $options['input']) {
            $builder->addModelTransformer(new CarbonToDateTimeTransformer());
        }
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function getExtendedTypes(): iterable
    {
        return [
            DateType::class,
            DateTimeType::class,
            TimeType::class,
        ];
    }
}
