<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\Enum\CronType;
use InvalidArgumentException;
use Twig\Extension\RuntimeExtensionInterface;

class CronRuntime implements RuntimeExtensionInterface
{
    public function getCronTab(CronType $cronType, string $projectDir, ?string $cronGroupName = null): string
    {
        $runEveryXMinutes = $cronType->getRunEveryXMinutes();
        if ($cronType->getRunAtFixedTime()) {
            $cronSchedule = $cronType->getRunAtFixedTime();
        } elseif ($runEveryXMinutes > 1) {
            $cronSchedule = '*/' . $cronType->getRunEveryXMinutes() . ' * * * *';
        } else {
            $cronSchedule = '* * * * *';
        }

        $command = match ($cronType) {
            CronType::ACTION_QUEUE => 'cron:action-queue ' . $cronGroupName,
            CronType::DB_CLEANUP => 'cron:db-cleanup',
            CronType::MONITOR => 'cron:monitor',
            default => throw new InvalidArgumentException('Unsupported cron type.')
        };

        return $cronSchedule . ' php ' . $projectDir . '/bin/console ' . $command;
    }
}
