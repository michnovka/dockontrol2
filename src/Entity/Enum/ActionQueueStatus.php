<?php

declare(strict_types=1);

namespace App\Entity\Enum;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum ActionQueueStatus: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('Queued')]
    case QUEUED = 'QUEUED';

    #[EnumCase('Executed')]
    case EXECUTED = 'EXECUTED';

    #[EnumCase('Failed')]
    case FAILED = 'FAILED';
}
