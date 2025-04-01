<?php

declare(strict_types=1);

namespace App\Entity\Enum;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ExtrasTrait;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

enum ConfigType: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;
    use ExtrasTrait;

    #[EnumCase('string', extras: ['input_type' => TextType::class])]
    case STRING = 'string';

    #[EnumCase('datetime', extras: ['input_type' => DateTimeType::class])]
    case DATETIME = 'datetime';

    #[EnumCase('int', extras: ['input_type' => IntegerType::class])]
    case INT = 'int';

    #[EnumCase('boolean', extras: ['input_type' => ChoiceType::class])]
    case BOOLEAN = 'boolean';

    #[EnumCase('secret', extras: ['input_type' => TextType::class])]
    case SECRET = 'secret';

    /**
     * @return class-string<AbstractType>
     */
    public function getInputType(): string
    {
        return $this->getExtra('input_type');
    }
}
