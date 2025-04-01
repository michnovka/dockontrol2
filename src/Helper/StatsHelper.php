<?php

declare(strict_types=1);

namespace App\Helper;

use App\Repository\ActionQueueRepository;

readonly class StatsHelper
{
    public function __construct(private ActionQueueRepository $actionQueueRepository)
    {
    }

    /**
     * @return array{periods: array<string>, stats: array<string, array<string, int>>}
     */
    public function getStats(): array
    {
        $periods = [];
        $usageStats = [];
        $stats = $this->actionQueueRepository->getStatistics();
        foreach ($stats as $stat) {
            $usageStats[$stat['action']][$stat['timeStart']] = $stat['count'];
            $periods[$stat['timeStart']] = true;
        }

        $periods = array_keys($periods);
        return ['periods' => $periods, 'stats' => $usageStats];
    }
}
