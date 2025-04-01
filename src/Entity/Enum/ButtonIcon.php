<?php

declare(strict_types=1);

namespace App\Entity\Enum;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum ButtonIcon: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('building')]
    case BUILDING = 'building';
    #[EnumCase('elevator')]
    case ELEVATOR = 'elevator';
    #[EnumCase('entrance')]
    case ENTRANCE = 'entrance';
    #[EnumCase('entrance_pedestrian')]
    case ENTRANCE_PEDESTRIAN = 'entrance_pedestrian';
    #[EnumCase('garage')]
    case GARAGE = 'garage';

    #[EnumCase('gate')]
    case GATE = 'gate';

    #[EnumCase('nuki')]
    case NUKI = 'nuki';

    #[EnumCase('enter')]
    case ENTER = 'enter';

    #[EnumCase('exit')]
    case EXIT = 'exit';
}
