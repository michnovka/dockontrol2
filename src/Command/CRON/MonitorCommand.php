<?php

declare(strict_types=1);

namespace App\Command\CRON;

use App\Console\LoggableIO;
use App\Entity\Enum\CronType;
use App\Entity\Enum\DockontrolNodeStatus;
use App\Helper\CronHelper;
use App\Helper\DockontrolNodeHelper;
use App\Helper\MailerHelper;
use App\Repository\DockontrolNodeRepository;
use Carbon\CarbonImmutable;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Throwable;

#[AsCommand(
    name: 'cron:monitor',
    description: 'this command is for monitoring nodes.',
)]
class MonitorCommand extends Command
{
    public function __construct(
        private readonly DockontrolNodeRepository $dockontrolNodeRepository,
        private readonly DockontrolNodeHelper $dockontrolNodeHelper,
        private readonly CronHelper $cronHelper,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly MailerHelper $mailerHelper,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loggableIO = new LoggableIO($input, $output);
        $nodes = $this->dockontrolNodeRepository->getAllEnabledNodes();
        $cronStartTime = new CarbonImmutable();
        $success = true;
        $loggableIO->title('Monitoring Nodes');

        try {
            foreach ($nodes as $node) {
                $oldNodeStatus = $node->getStatus();
                $loggableIO->text('Checking ' . $node->getName() . ' (' . $node->getIp() . ') ');
                $ip = $node->getIp();
                $status = DockontrolNodeStatus::OFFLINE;
                $dockontrolNodeFailCount = $node->getFailCount();
                $dockontrolNodeLastNotifyStats = $node->getLastNotifyStatus();

                try {
                    $process = new Process(['ping', '-W', '1', '-c', '4', $ip], $this->projectDir);
                    $process->run();

                    // Check if ping command was successful and get ping value
                    if ($process->isSuccessful()) {
                        $processOutput = $process->getOutput();
                        $lines = explode("\n", trim($processOutput));
                        $lastLine = end($lines);

                        // Extract ping value using regex
                        if (preg_match('/(\d+(\.\d+)?)\/(\d+(\.\d+)?)\/(\d+(\.\d+)?)/', $lastLine, $matches)) {
                            $status = DockontrolNodeStatus::PINGABLE;
                            $node->setPing(floatval($matches[3]));
                            $node->setLastPingTime(new CarbonImmutable());
                        }
                    }
                } catch (Throwable $e) {
                    $loggableIO->error($e->getMessage());
                }

                $loggableIO->text('.');

                $reply = null;

                try {
                    $reply = $this->dockontrolNodeHelper->callDockontrolNodeAPIVersion($node);

                    if (!empty($reply['jsonData'])) {
                        if ($reply['jsonData']['status'] == 'ok') {
                            $status = DockontrolNodeStatus::ONLINE;
                            $node->setDockontrolNodeVersion($reply['jsonData']['version']);
                            $node->setOsVersion($reply['jsonData']['os_version']);
                            $node->setKernelVersion($reply['jsonData']['kernel_version']);
                            $node->setUptime(intval($reply['jsonData']['uptime']));
                            $node->setDevice($reply['jsonData']['device']);
                        } else {
                            if ($reply['httpCode'] == 403) {
                                $status = DockontrolNodeStatus::INVALID_API_SECRET;
                                $success = false;
                            }
                        }
                    }
                } catch (Throwable $e) {
                    $loggableIO->error($e->getMessage());
                }

                $loggableIO->text('.');

                if ($status === DockontrolNodeStatus::ONLINE) {
                    $node->setFailCount(max(0, $dockontrolNodeFailCount - 1));
                } else {
                    $node->setFailCount(min(5, $dockontrolNodeFailCount + 1));
                }

                $node->setStatus($status);
                $node->setLastMonitorCheckTime(new CarbonImmutable());

                $this->dockontrolNodeHelper->saveDockontrolNode($node);
                if (
                    ($node->getFailCount() == 5 || $node->getFailCount() == 0) &&
                    (is_null($node->getLastNotifyStatus()) || $dockontrolNodeLastNotifyStats !== $status)
                ) {
                    if ($node->isNotifyWhenStatusChange()) {
                        foreach ($node->getUsersToNotifyWhenStatusChanges() as $usersToNotifyWhenStatusChange) {
                            try {
                                $this->mailerHelper->sendDockontrolNodeStatusChangeMail($usersToNotifyWhenStatusChange, $node, $oldNodeStatus, $status);
                                $node->setLastNotifyStatus($status);
                                $this->dockontrolNodeHelper->saveDockontrolNode($node);
                            } catch (Throwable $exception) {
                                $loggableIO->error($exception->getMessage());
                            }
                        }
                    }
                }

                $loggableIO->text(' ' . $status->getReadable());
            }

            $loggableIO->success('Done');
        } catch (Throwable $exception) {
            $loggableIO->error($exception->getMessage());
        } finally {
            $this->cronHelper->addLog(CronType::MONITOR, $cronStartTime, CarbonImmutable::now(), $loggableIO->getOutput(), success: $success);
        }

        return Command::SUCCESS;
    }
}
