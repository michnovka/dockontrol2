<?php

declare(strict_types=1);

namespace App\Command\Maintenance;

use App\Repository\UserRepository;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'maintenance:reset-time-tos-accepted', description: 'Hello PhpStorm')]
class MaintenanceResetTimeTosAcceptedCommand extends Command
{
    public function __construct(private readonly UserRepository $userRepository)
    {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->userRepository->resetTimeTosAcceptedForAllUser();
        $io->success('All users have had their timeTosAccepted reset to NULL.');
        return Command::SUCCESS;
    }
}
