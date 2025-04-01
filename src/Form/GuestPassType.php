<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Guest;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class GuestPassType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('expires', ChoiceType::class, [
                'label' => $this->translator->trans('dockontrol.guest_pass.form.expires'),
                'choices' => [
                    '1 Hour' => 1,
                    '24 Hour' => 24,
                    '2 Days' => 48,
                    '1 Week' => 168,
                ],
                'multiple' => false,
                'mapped' => false,
            ])
            ->add('remainingActions', ChoiceType::class, [
                'label' => $this->translator->trans('dockontrol.guest_pass.form.maximum_number_of_actions'),
                'choices' => [
                    'unlimited' => '-1',
                    5 => 5,
                    10 => 10,
                    20 => 20,
                    50 => 50,
                ],
                'multiple' => false,
            ])
            ->add('defaultLanguage', ChoiceType::class, [
                'label' => $this->translator->trans('dockontrol.guest_pass.form.default_language'),
                'choices' => [
                    'Czech' => 'cs',
                    'English' => 'en',
                ],
                'placeholder' => false,
                'required' => false,
            ])
            ->add('note', TextType::class, [
                'label' => $this->translator->trans('dockontrol.guest_pass.form.note'),
                'required' => false,
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Guest::class,
        ]);
    }
}
