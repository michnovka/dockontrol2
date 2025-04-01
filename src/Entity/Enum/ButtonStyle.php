<?php

declare(strict_types=1);

namespace App\Entity\Enum;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum ButtonStyle: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('basic')]
    case BASIC = 'basic';
    #[EnumCase('blue')]
    case BLUE = 'blue';
    #[EnumCase('red')]
    case RED = 'red';
}
