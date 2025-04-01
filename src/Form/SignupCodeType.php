<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Apartment;
use App\Entity\SignupCode;
use App\Entity\User;
use App\Form\Type\SearchableEntityType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignupCodeType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $adminUser = $options['admin_user'];
        $builder
            ->add('apartment', SearchableEntityType::class, [
                'class' => Apartment::class,
                'required' => true,
                'label' => 'Apartment',
                'multiple' => false,
                'placeholder' => 'Apartment',
                'choice_label' => function (Apartment $apartments) {
                    return $apartments->getTwigDisplayValue();
                },
                'attr' => [
                    'data-choices-search-enabled' => 'true',
                    'data-choices-search-choices' => 'true',
                    'data-choices-remove-items' => 'true',
                    'data-choices-remove-item-button' => 'true',
                ],
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SignupCode::class,
            'admin_user' => null,
        ]);

        $resolver->setAllowedTypes('admin_user', ['null', User::class]);
    }
}
