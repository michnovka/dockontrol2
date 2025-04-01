<?php

declare(strict_types=1);

namespace App\Form\Filter\APILog\Succeeded;

use App\Entity\User;
use App\Form\Type\SearchableEntityType;
use Override;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class API2LogSuccessFilterType extends AbstractAPILogSuccessFilterType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('user', SearchableEntityType::class, [
                'class' => User::class,
                'required' => false,
                'label' => 'User',
                'multiple' => false,
                'placeholder' => 'User',
                'choice_label' => function (User $user) {
                    return $user->getTwigDisplayValue();
                },
                'attr' => [
                    'data-choices-search-enabled' => 'true',
                    'data-choices-search-choices' => 'true',
                    'data-choices-remove-items' => 'true',
                    'data-choices-remove-item-button' => 'true',
                ],
            ])
            ->add('apiKey', TextType::class, [
                'label' => 'API Key',
                'required' => false,
            ]);
    }
}
