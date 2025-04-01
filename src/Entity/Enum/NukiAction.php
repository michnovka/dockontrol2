<?php

declare(strict_types=1);

namespace App\Entity\Enum;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum NukiAction : string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('Lock')]
    case LOCK = 'lock';
    #[EnumCase('Unlock')]
    case UNLOCK = 'unlock';

    #[EnumCase('PIN Check')]
    case PIN_CHECK = 'pin_check';

    #[EnumCase('Password1 Check')]
    case PASSWORD1_CHECK = 'password1_check';
}
