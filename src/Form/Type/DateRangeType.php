<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Form\DataTransformer\DateRangeTransformer;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateRangeType extends AbstractType
{
    #[Override]
    public function getParent(): string
    {
        return TextType::class;
    }

    /**
     * {@inheritdoc}
     * @psalm-suppress PropertyTypeCoercion
     */
    #[Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['attr']['class'] = 'date-range-time';
        $view->vars['is_datetime'] = $options['date_picker_type'] === 'datetime';
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isDateTime = $options['date_picker_type'] === 'datetime';

        $builder
            ->addModelTransformer(new DateRangeTransformer($isDateTime));
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'date_picker_type' => 'date',
        ]);

        $resolver->setAllowedValues('date_picker_type', [
            'date',
            'datetime',
        ]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'date_range';
    }
}
