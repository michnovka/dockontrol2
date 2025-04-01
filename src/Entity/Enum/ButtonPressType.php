<?php

declare(strict_types=1);

namespace App\Entity\Enum;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum ButtonPressType: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('click')]
    case CLICK = 'click';
    #[EnumCase('hold')]
    case HOLD = 'hold';
}
