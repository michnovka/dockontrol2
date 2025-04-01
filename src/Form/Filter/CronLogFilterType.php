<?php

declare(strict_types=1);

namespace App\Form\Filter;

use App\Entity\ActionQueueCronGroup;
use App\Entity\Enum\CronType;
use App\Form\Type\DateRangeType;
use Elao\Enum\Bridge\Symfony\Form\Type\EnumType;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CronLogFilterType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setMethod('GET');

        $builder
            ->add('timeStart', DateRangeType::class, [
                'required' => false,
                'label' => 'Time start',
                'show_clear_button' => true,
                'mapped' => true,
            ])
            ->add('timeEnd', DateRangeType::class, [
                'required' => false,
                'label' => 'Time end',
                'show_clear_button' => true,
                'mapped' => true,
            ])
            ->add('cronGroup', EntityType::class, [
                'required' => false,
                'label' => 'Cron group',
                'class' => ActionQueueCronGroup::class,
                'choice_label' => 'name',
                'multiple' => false,
                'placeholder' => 'Cron group',
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-choices' => 'true',
                    'data-choices-search-enabled' => 'true',
                ],
            ])
            ->add('cronType', EnumType::class, [
                'required' => false,
                'label' => 'Cron type',
                'class' => CronType::class,
                'multiple' => false,
                'placeholder' => 'Cron type',
                'attr' => [
                    'class' => 'choices-select',
                    'data-choices-search-choices' => 'true',
                    'data-choices-search-enabled' => 'true',
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
