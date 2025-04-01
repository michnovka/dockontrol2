<?php

declare(strict_types=1);

namespace App\Entity\Enum;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum DockontrolNodeStatus : string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('Online')]
    case ONLINE = 'online';
    #[EnumCase('Pingable')]
    case PINGABLE = 'pingable';

    #[EnumCase('Offline')]
    case OFFLINE = 'offline';

    #[EnumCase('Invalid API secret')]
    case INVALID_API_SECRET = 'invalid_api_secret';
}
