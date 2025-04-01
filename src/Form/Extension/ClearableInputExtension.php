<?php

declare(strict_types=1);

namespace App\Form\Extension;

use App\Form\Type\DateRangeType;
use Override;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClearableInputExtension extends AbstractTypeExtension
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'show_clear_button' => false,
        ]);

        $resolver->setAllowedTypes('show_clear_button', 'bool');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (!empty($options['show_clear_button'])) {
            $view->vars['show_clear_button'] = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function getExtendedTypes(): iterable
    {
        return [
            DateRangeType::class,
            DateTimeType::class,
        ];
    }
}
