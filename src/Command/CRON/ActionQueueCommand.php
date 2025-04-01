<?php

declare(strict_types=1);

namespace App\Command\CRON;

use App\Command\AbstractEndlessCommand;
use App\Command\ShutdownEndlessCommandException;
use App\Console\LoggableIO;
use App\Entity\ActionQueue;
use App\Entity\ActionQueueCronGroup;
use App\Entity\Enum\ActionQueueStatus;
use App\Entity\Enum\CronType;
use App\Helper\ActionHelper;
use App\Helper\CronHelper;
use App\Helper\RedisHelper;
use App\Repository\ActionQueueCronGroupRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Exception as DoctrineException;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use RedisException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

#[AsCommand(
    name: 'cron:action-queue',
    description: 'This cron will execute all pending actions from the queue.',
)]
class ActionQueueCommand extends AbstractEndlessCommand
{
    private LockInterface $lock;

    private bool $isSuccess = false;

    private LoggableIO $io;

    public function __construct(
        private readonly ActionHelper $actionHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActionQueueCronGroupRepository $cronGroupRepository,
        private readonly RedisHelper $redisHelper,
        private readonly CronHelper $cronHelper,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    #[Override]
    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new LoggableIO($input, $output);
        $store = new SemaphoreStore();
        $factory = new LockFactory($store);
        $cronGroupName = $input->getArgument('cronGroupName');

        $this->io->title('Initiating Cron Group: ' . $cronGroupName);

        $this->lock = $factory->createLock('cron:action-queue:' . $cronGroupName);

        if (!$this->lock->acquire()) {
            // Set the return code tot non-zero if there are any errors
            $this->setReturnCode(1);

            // After this execute method returns we want the command exit
            $this->shutdown();

            // Tell the user we're done
            $this->io->error('Failed to acquire lock!');
        } else {
            $this->io->info('Lock acquired.');
        }
    }

    #[Override]
    protected function configure(): void
    {
        $this->addArgument('cronGroupName', InputArgument::REQUIRED, 'CRON Group Name');
        $this->setTimeout(1);
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cronGroupName = $input->getArgument('cronGroupName');
        $minuteStart = CarbonImmutable::now()->minute;
        $startTime = CarbonImmutable::now();
        $cronGroup = null;
        $skipDoctrineLogSave = false;

        // we stay in this function for 1 min. then EM data is cleared and it restarts
        try {
            $redis = $this->redisHelper->getRedisInstance();
            $cronGroup = $this->cronGroupRepository->findOneBy(['name' => $cronGroupName]);

            if (!$cronGroup instanceof ActionQueueCronGroup) {
                throw new RuntimeException('CronGroup not found.');
            }

            $redisKeyTimeQueue = ActionHelper::REDIS_ACTION_QUEUE_KEY_PREFIX . $cronGroupName;
            $redisKeyImmediateQueue = ActionHelper::REDIS_IMMEDIATE_ACTION_QUEUE_KEY_PREFIX . $cronGroupName;

            $this->io->text('starting minute loop');
            while ($minuteStart === CarbonImmutable::now()->minute) {
                $this->isSuccess = true;
                $now = (string) time();

                $immediateActionQueueId = $redis->blPop([$redisKeyImmediateQueue], 1);

                if (!empty($immediateActionQueueId)) {
                    // Process immediate action within 1s
                    $this->processActionQueue(intval($immediateActionQueueId[1]), true);
                }

                // Atomic operation
                $redis->multi();
                $redis->zRangeByScore($redisKeyTimeQueue, '-inf', $now);
                $redis->zRemRangeByScore($redisKeyTimeQueue, '-inf', $now);

                /** @var array $results */
                $results = $redis->exec();

                $actionQueueIds = $results[0];

                if (!empty($actionQueueIds)) {
                    foreach ($actionQueueIds as $actionQueueId) {
                        $this->processActionQueue(intval($actionQueueId), false);
                    }
                }

                // this will just break from the loop, but will be caught by our try/catch/finally. We need to dispatch exception again in finally
                $this->throwExceptionOnShutdown();
            }
        } catch (DoctrineException $e) {
            $this->io->error('Doctrine error #' . $e->getCode() . ': ' . $e->getMessage());
            $this->shutdown();
            $this->isSuccess = false;
            $skipDoctrineLogSave = true;
        } catch (RedisException $e) {
            $this->io->error('Redis error #' . $e->getCode() . ': ' . $e->getMessage());
            $this->shutdown();
            $this->isSuccess = false;
        } catch (ShutdownEndlessCommandException) {
            $this->io->error('Shutdown requested.');
            $this->isSuccess = false;
        } catch (Throwable $e) {
            $this->io->error('Failed to execute action queue. Exception Type: ' . $e::class . ', Message: ' . $e->getMessage());
            $this->isSuccess = false;
            // in case of random exceptions that we dont want to handle gracefully, we want to request shutdown
            $this->shutdown();
        } finally {
            if (!$skipDoctrineLogSave) {
                $this->cronHelper->addLog(CronType::ACTION_QUEUE, $startTime, CarbonImmutable::now(), $this->io->getOutput(), $cronGroup, $this->isSuccess);
            }
            $this->io->clear();
            // if shutdown was requested, throw again an exception
            $this->throwExceptionOnShutdown();
        }

        if (!$this->isSuccess) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Called after each iteration
     */
    #[Override]
    protected function finishIteration(InputInterface $input, OutputInterface $output): void
    {
        // Do some cleanup/memory management here, don't forget to call the parent implementation!
        parent::finishIteration($input, $output);

        $this->entityManager->clear();
        $this->redisHelper->clear();
    }

    // Called once on shutdown after the last iteration finished
    #[Override]
    protected function finalize(InputInterface $input, OutputInterface $output): void
    {
        // Keep it short! We may need to exit because the OS wants to shut down,
        // and we can get killed if it takes to long!
        $this->lock->release();
    }

    private function processActionQueue(int $actionQueueId, bool $isImmediate): void
    {
        $actionQueue = $this->entityManager
            ->getRepository(ActionQueue::class)
            ->find($actionQueueId);

        if ($actionQueue === null) {
            // ActionQueue not found, possibly log a warning
            return;
        }

        $action = $actionQueue->getAction();

        $this->io->text(($isImmediate ? 'immediate' : 'planned') . ' - ' . $action->getName());

        try {
            $result = $this->actionHelper->executeAction($action, $this->io, true);
            $this->markQueueAsExecuted($actionQueue, $result);
        } catch (Throwable $exception) {
            // Log exception and handle failure
            $this->io->error($exception->getMessage());
            // Optionally, re-add to Redis for retry logic
        }
    }

    private function markQueueAsExecuted(ActionQueue $actionQueue, bool $result): void
    {
        if ($result) {
            $actionQueue->setStatus(ActionQueueStatus::EXECUTED);
        } else {
            $actionQueue->setStatus(ActionQueueStatus::FAILED);
        }
        $actionQueue->setTimeExecuted(CarbonImmutable::now());
        $this->validateActionQueue($actionQueue);
        $this->entityManager->persist($actionQueue);
        $this->entityManager->flush();
    }

    private function validateActionQueue(ActionQueue $actionQueue): void
    {
        $violations = $this->validator->validate($actionQueue);

        if ($violations->count() > 0) {
            /** @var string $violationMessage*/
            $violationMessage = $violations[0]?->getMessage();
            throw new RuntimeException($violationMessage);
        }
    }
}
