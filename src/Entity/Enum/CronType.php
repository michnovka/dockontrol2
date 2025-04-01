<?php

declare(strict_types=1);

namespace App\Entity\Enum;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ExtrasTrait;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum CronType: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;
    use ExtrasTrait;

    #[EnumCase('Node Monitor', extras: ['runEveryXMinutes' => 1])]
    case MONITOR = 'MONITOR';

    #[EnumCase('Database Cleanup', extras: ['runEveryXMinutes' => 1440, 'runAtFixedTime' => '0 2 * * *'])]
    case DB_CLEANUP = 'DB_CLEANUP';

    #[EnumCase('Action Queue', extras: ['runEveryXMinutes' => 1])]
    case ACTION_QUEUE = 'ACTION_QUEUE';

    public function getRunEveryXMinutes(): int
    {
        return $this->getExtra('runEveryXMinutes', true);
    }

    public function getRunAtFixedTime(): ?string
    {
        return $this->getExtra('runAtFixedTime');
    }
}
