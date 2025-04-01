<?php

declare(strict_types=1);

namespace App\Entity\Enum;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum SpecialButtonType: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('car_enter')]
    case CAR_ENTER = 'car_enter';

    #[EnumCase('car_exit')]
    case CAR_EXIT = 'car_exit';
}
