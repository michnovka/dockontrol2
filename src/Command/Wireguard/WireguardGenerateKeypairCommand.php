<?php

declare(strict_types=1);

namespace App\Command\Wireguard;

use App\Helper\WireguardHelper;
use Override;
use SodiumException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'wireguard:generate-keypair', description: 'This command will generate key pair of wireguard.')]
class WireguardGenerateKeypairCommand extends Command
{
    public function __construct(private readonly WireguardHelper $wireguardHelper)
    {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $generatedKeypair = $this->wireguardHelper->generateKeypair();
            $io->success('Wireguard key pair generated successfully.');

            $io->text("========================= generated key pair ==========================");
            $io->newLine();
            $io->text('Public key: ' . $generatedKeypair['publicKey']);
            $io->text('Private key: ' . $generatedKeypair['privateKey']);
            $io->newLine();

            $io->text("=======================================================================");
            return Command::SUCCESS;
        } catch (SodiumException $exception) {
            $io->error('Failed to get wireguard key pair. ' . $exception->getMessage());
            return Command::FAILURE;
        }
    }
}
