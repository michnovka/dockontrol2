<?php

declare(strict_types=1);

namespace App\Entity\Enum;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum ConfigGroup: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('logs')]
    case LOGS = 'logs';
    #[EnumCase('email')]
    case EMAIL = 'email';
    #[EnumCase('general')]
    case GENERAL = 'general';
}
