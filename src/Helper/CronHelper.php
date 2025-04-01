<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\ActionQueueCronGroup;
use App\Entity\Enum\CronType;
use App\Entity\Log\CronLog;
use App\Repository\ActionQueueCronGroupRepository;
use App\Repository\ActionRepository;
use App\Repository\Log\CronLogRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;

readonly class CronHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ActionRepository $actionRepository,
        private CronLogRepository $cronLogRepository,
        private ActionQueueCronGroupRepository $cronGroupRepository,
    ) {
    }

    public function saveCronGroup(ActionQueueCronGroup $cronGroup): void
    {
        $this->entityManager->persist($cronGroup);
        $this->entityManager->flush();
    }

    public function remove(ActionQueueCronGroup $cronGroup): void
    {
        $this->entityManager->remove($cronGroup);
        $this->entityManager->flush();
    }

    public function checkCronGroupIsAssigned(ActionQueueCronGroup $actionQueueCronGroup): bool
    {
        $assignedActions = $this->actionRepository->findBy(['actionQueueCronGroup' => $actionQueueCronGroup]);
        return !empty($assignedActions);
    }

    public function addLog(
        CronType $cronType,
        CarbonImmutable $timeStart,
        CarbonImmutable $timeEnd,
        string $output,
        ?ActionQueueCronGroup $cronGroup = null,
        bool $success = false,
    ): void {
        $cronLog = new CronLog();
        $cronLog
            ->setType($cronType)
            ->setTimeStart($timeStart)
            ->setTimeEnd($timeEnd)
            ->setActionQueueCronGroup($cronGroup)
            ->setOutput($output)
            ->setSuccess($success)
        ;

        $this->entityManager->persist($cronLog);
        $this->entityManager->flush();
    }

    /**
     * @return array{
     *     ACTION_QUEUE: array<string, array{isHealthy?: bool, lastRun: null|string}>,
     *     DB_CLEANUP: array{isHealthy: bool, lastRun: ?string, type?: CronType},
     *     MONITOR: array{isHealthy: bool, lastRun: ?string, type?: CronType}
     *}
     */
    public function getCronHealthStatus(): array
    {
        $cronLogs = $this->cronLogRepository->getLastSuccessfulRunForEachCronType();
        $cronGroupHealthStatus = $this->checkAllCronGroupHealth();
        $cronHealthStatus = [];
        $allCronTypes = CronType::cases();

        foreach ($cronLogs as $cronLog) {
            $cronType = $cronLog['type'];

            if (empty($cronLog['lastRun'])) {
                $cronHealthStatus[$cronType->name] = [
                    'lastRun' => null,
                    'isHealthy' => false,
                ];
            } else {
                $runEveryXMinutes = $cronType->getRunEveryXMinutes();
                $limit = $runEveryXMinutes + min($runEveryXMinutes, 120);

                $isHealthy = CarbonImmutable::createFromTimeString($cronLog['lastRun'])
                        ->diffInMinutes(CarbonImmutable::now()) <= $limit;

                $cronHealthStatus[$cronType->name] = [
                    'type' => $cronType,
                    'lastRun' => $cronLog['lastRun'],
                    'isHealthy' => $isHealthy,
                ];
            }
        }

        foreach ($allCronTypes as $cronType) {
            if (!isset($cronHealthStatus[$cronType->name]) && $cronType !== CronType::ACTION_QUEUE) {
                $cronHealthStatus[$cronType->name] = [
                    'type' => $cronType,
                    'lastRun' => null,
                    'isHealthy' => false,
                ];
            }
        }
        $cronHealthStatus[CronType::ACTION_QUEUE->name] = $cronGroupHealthStatus;

        /**
         * @var array{
         *      ACTION_QUEUE: array<string, array{isHealthy?: bool, lastRun: null|string}>,
         *      DB_CLEANUP: array{isHealthy: bool, lastRun: ?string, type?: CronType},
         *      MONITOR: array{isHealthy: bool, lastRun: ?string, type?: CronType}
         * } $cronHealthStatus
         */

        return $cronHealthStatus;
    }

    /**
     * @return array<string, array{isHealthy?: bool, lastRun: null|string}>
     */
    public function checkAllCronGroupHealth(): array
    {
        $now = CarbonImmutable::now();
        $runEveryXMinutes = CronType::ACTION_QUEUE->getRunEveryXMinutes();
        $limit = $runEveryXMinutes + min($runEveryXMinutes, 120);
        $cronGroupArr = $this->cronGroupRepository->getLastRunTimesByCronGroup();
        $cronGroupHealthStatus = [];

        foreach ($cronGroupArr as $cronGroup) {
            $lastRun = $cronGroup['lastRun'] ?? null;

            $cronGroup['isHealthy'] = $lastRun && CarbonImmutable::createFromTimeString($lastRun)->diffInMinutes($now) <= $limit;

            $cronGroupHealthStatus[$cronGroup['name']] = [
                'isHealthy' => $cronGroup['isHealthy'],
                'lastRun' => $lastRun,
            ];
        }

        return $cronGroupHealthStatus;
    }
}
