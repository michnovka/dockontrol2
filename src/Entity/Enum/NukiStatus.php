<?php

declare(strict_types=1);

namespace App\Entity\Enum;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum NukiStatus: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('Ok')]
    case OK = 'ok';
    #[EnumCase('Incorrect PIN')]
    case INCORRECT_PIN = 'incorrect_pin';

    #[EnumCase('Error')]
    case ERROR = 'error';

    #[EnumCase('Incorrect Password1')]
    case INCORRECT_PASSWORD1 = 'incorrect_password1';
}
