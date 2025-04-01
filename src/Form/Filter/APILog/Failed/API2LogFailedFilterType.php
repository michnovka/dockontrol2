<?php

declare(strict_types=1);

namespace App\Form\Filter\APILog\Failed;

use Override;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class API2LogFailedFilterType extends AbstractAPILogFailedFilterType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('apiEndpoint', TextType::class, [
                'label' => 'API endpoint',
                'required' => false,
            ])
            ->add('apiKey', TextType::class, [
                'label' => 'API key',
                'required' => false,
            ]);
    }
}
