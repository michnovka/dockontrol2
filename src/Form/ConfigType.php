<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Enum\ConfigName;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigType extends AbstractType
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ConfigName $configName */
        $configName = $options['config_name'];
        $formType = $configName->getConfigType()->getInputType();

        $formOptions = [
            'label' => false,
            'required' => $options['required'],
            'data' => $options['value'],
            'attr' => [
                'class' => 'form-control-sm',
            ],
        ];

        if ($formType === ChoiceType::class) {
            $formOptions['choices'] = [
                'Yes' => true,
                'No' => false,
            ];
            $formOptions['expanded'] = true;
            $formOptions['placeholder'] = false;
            $formOptions['attr'] = [
                'class' => 'd-flex gap-2',
            ];
            $formOptions['data'] = $options['value'];
        }

        $builder->add('config_' . $configName->value, $formType, $formOptions);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'config_name' => null,
            'value' => null,
            'required' => true,
        ]);

        $resolver->setAllowedTypes('config_name', ['null', ConfigName::class]);
    }
}
