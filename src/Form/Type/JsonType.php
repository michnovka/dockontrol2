<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Form\DataTransformer\JsonTransformer;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class JsonType extends AbstractType
{
    #[Override]
    public function getParent(): string
    {
        return TextType::class;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addModelTransformer(new JsonTransformer());
    }
}
