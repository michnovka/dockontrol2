<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Announcement;
use App\Entity\Building;
use App\Form\Type\SearchableEntityType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnnouncementType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startTime', DateTimeType::class, [
                'required' => false,
                'html5' => false,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd HH:mm:ss',
                'show_clear_button' => true,
                'attr' => [
                    'class' => 'datetime-picker',
                ],
            ])
            ->add('endTime', DateTimeType::class, [
                'required' => false,
                'html5' => false,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd HH:mm:ss',
                'show_clear_button' => true,
                'attr' => [
                    'class' => 'datetime-picker',
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Subject',
                'required' => true,
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'required' => true,
                'attr' => [
                    'rows' => 15,
                ],
            ])
            ->add('building', SearchableEntityType::class, [
                'label' => 'Building',
                'class' => Building::class,
                'choice_label' => function (Building $building) {
                    return $building->getTwigDisplayValue();
                },
                'choice_value' => 'id',
                'placeholder' => 'All buildings',
                'multiple' => false,
                'attr' => [
                    'data-choices-search-enabled' => 'true',
                    'data-choices-search-choices' => 'true',
                    'data-choices-remove-items' => 'true',
                    'data-choices-remove-item-button' => 'true',
                ],
                'required' => false,
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Announcement::class,
        ]);
    }
}
