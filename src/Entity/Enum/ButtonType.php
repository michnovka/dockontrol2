<?php

declare(strict_types=1);

namespace App\Entity\Enum;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum ButtonType: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('gate')]
    case GATE = 'gate';
    #[EnumCase('entrance')]
    case ENTRANCE = 'entrance';
    #[EnumCase('elevator')]
    case ELEVATOR = 'elevator';
    #[EnumCase('multi')]
    case MULTI = 'multi';
    #[EnumCase('custom')]
    case CUSTOM = 'custom';
}
