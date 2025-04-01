<?php

declare(strict_types=1);

namespace App\Command\Tool;

use App\Console\LoggableIO;
use App\Entity\Action;
use App\Helper\ActionHelper;
use App\Repository\ActionRepository;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'tool:node:perform-action', description: 'This command will be used to debug actions, so we can execute them and bypass the queue.')]
class DockontrolNodeActionCommand extends Command
{
    public function __construct(
        private readonly ActionRepository $actionRepository,
        private readonly ActionHelper $actionHelper,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addArgument('action_name', InputArgument::REQUIRED, 'Action Name');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new LoggableIO($input, $output);
        $actionName = $input->getArgument('action_name');
        $action = $this->actionRepository->find($actionName);

        if (!$action instanceof Action) {
            $io->error('Action does not exist: ' . $actionName);
            return Command::FAILURE;
        }

        $this->actionHelper->executeAction($action, $io);

        return Command::SUCCESS;
    }
}
