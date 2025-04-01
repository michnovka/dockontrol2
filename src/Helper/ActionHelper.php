<?php

declare(strict_types=1);

namespace App\Helper;

use App\Console\LoggableIO;
use App\Entity\Action;
use App\Entity\ActionBackupDockontrolNode;
use App\Entity\ActionQueue;
use App\Entity\DockontrolNode;
use App\Entity\Enum\ActionType;
use App\Entity\Enum\DockontrolNodeStatus;
use App\Entity\Guest;
use App\Entity\User;
use App\Repository\ActionBackupDockontrolNodeRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use RedisException;
use RuntimeException;
use Throwable;

readonly class ActionHelper
{
    public const string REDIS_ACTION_QUEUE_KEY_PREFIX = 'action_queue:';
    public const string REDIS_IMMEDIATE_ACTION_QUEUE_KEY_PREFIX = 'immediate_action_queue:';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DockontrolNodeHelper $dockontrolNodeHelper,
        private RedisHelper $redisHelper,
        private ActionBackupDockontrolNodeRepository $actionBackupDockontrolNodeRepository,
        private GuestHelper $guestHelper,
    ) {
    }

    public function executeAction(Action $action, ?LoggableIO $io = null, bool $fallbackToBackup = false): bool
    {

        $actionType = $action->getType();

        switch ($actionType) {
            case ActionType::DOCKONTROL_NODE_RELAY:
                $dockontrolNode = $action->getDockontrolNode();
                if ($dockontrolNode instanceof DockontrolNode) {
                    if ($dockontrolNode->getStatus() === DockontrolNodeStatus::ONLINE) {
                        try {
                            $io?->writeln('Calling DOCKontrol node API at ' . $dockontrolNode->getIp());
                            $response = $this->dockontrolNodeHelper->callDockontrolNodeAPIAction($action);
                            $io?->writeln('HTTP ' . $response['httpCode'] . ' - ' . $response['rawData']);
                        } catch (Throwable $exception) {
                            $io?->error($exception->getMessage());
                            return false;
                        }
                    } elseif ($fallbackToBackup) {
                        $io?->error($dockontrolNode->getName() . ' node is offline.');
                        $io?->writeln('Falling back to DOCKontrol backup node.');
                        try {
                            $actionBackupDockontrol = $this->actionBackupDockontrolNodeRepository->findRandomBackupActionByParent($action);
                            $backupDockontrolNode =  $actionBackupDockontrol?->getDockontrolNode();
                            if ($actionBackupDockontrol instanceof ActionBackupDockontrolNode && $backupDockontrolNode instanceof DockontrolNode) {
                                $io?->writeln('Calling DOCKontrol backup node API at ' . $backupDockontrolNode->getIp());
                                $response = $this->dockontrolNodeHelper->callDockontrolNodeAPIAction($actionBackupDockontrol);
                                $io?->writeln('HTTP ' . $response['httpCode'] . ' - ' . $response['rawData']);
                            } else {
                                throw new RuntimeException('Online action backup node not found.');
                            }
                        } catch (Throwable $exception) {
                            $io?->error($exception->getMessage());
                            return false;
                        }
                    } else {
                        $io?->error('DOCKontrol node is offline.');
                        return false;
                    }

                    $dockontrolNode->setLastCommandExecutedTime(new CarbonImmutable());
                    $this->dockontrolNodeHelper->saveDockontrolNode($dockontrolNode);
                }
                break;
            case ActionType::MULTI:
                $io?->write('MULTI');
                break;
        }

        return true;
    }


    /**
     * Adds an action to the queue.
     *
     * @throws RedisException
     * @throws RuntimeException
     */
    public function addActionToQueue(
        Action $action,
        User|Guest $user,
        ?CarbonImmutable $timeStart = null,
        bool $countIntoStats = true,
    ): ActionQueue {

        $guest = null;

        if ($user instanceof Guest) {
            $guest = $user;
            $user = $guest->getUser();
        }

        if ($guest instanceof Guest) {
            $this->entityManager->beginTransaction();
            try {
                $this->entityManager->lock($guest, LockMode::PESSIMISTIC_WRITE);
                if ($guest->isGuestPassValid()) {
                    throw new RuntimeException('Guest token expired.');
                }
            } catch (Throwable $throwable) {
                $this->entityManager->rollback();
                throw new RuntimeException('Failed to acquire guest lock: ' . $throwable->getMessage());
            }
            $this->entityManager->commit();
        }
        /** @var User $user */
        $now = CarbonImmutable::now();

        $actionQueue = new ActionQueue();
        $actionQueue->setAction($action);
        $actionQueue->setUser($user);
        $actionQueue->setGuest($guest);
        $actionQueue->setTimeStart($timeStart ?: $now);
        $actionQueue->setTimeCreated($now);
        $actionQueue->setCountIntoStats($countIntoStats);

        if ($countIntoStats) {
            $user->setTimeLastAction($now);
            $this->entityManager->persist($user);

            if ($guest instanceof Guest) {
                $guest->setTimeLastAction($now);
                $this->entityManager->persist($guest);
            }
        }

        $this->entityManager->persist($actionQueue);
        $this->entityManager->flush();

        // Insert into Redis
        try {
            $redis = $this->redisHelper->getRedisInstance();
            $cronGroupName = $action->getActionQueueCronGroup()->getName();

            if (is_null($timeStart) || $timeStart->isBefore(CarbonImmutable::now())) {
                $isImmediate = true;
                $redis->rPush(self::REDIS_IMMEDIATE_ACTION_QUEUE_KEY_PREFIX . $cronGroupName, (string)$actionQueue->getId());
            } else {
                $score = $timeStart->getTimestamp();
                $redis->zAdd(self::REDIS_ACTION_QUEUE_KEY_PREFIX . $cronGroupName, $score, (string)$actionQueue->getId());
                $isImmediate = false;
            }

            $actionQueue->setIsImmediate($isImmediate);
            $this->entityManager->persist($actionQueue);
            $this->entityManager->flush();

            if ($guest instanceof Guest) {
                $remainingActions = $guest->getRemainingActions();

                if ($remainingActions > 0) {
                    $guest->setRemainingActions($remainingActions - 1);
                    $this->entityManager->persist($guest);
                    $this->entityManager->flush();
                }
            }
        } catch (RedisException $e) {
            throw new RuntimeException('Failed to add action to redis queue: ' . $e->getMessage(), 0, $e);
        }

        return $actionQueue;
    }

    public function saveAction(Action $action): void
    {
        $this->entityManager->persist($action);
        $this->entityManager->flush();
    }

    public function removeAction(Action $action): void
    {
        $this->entityManager->remove($action);
        $this->entityManager->flush();
    }
}
