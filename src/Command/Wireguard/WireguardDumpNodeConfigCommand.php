<?php

declare(strict_types=1);

namespace App\Command\Wireguard;

use App\Entity\DockontrolNode;
use App\Repository\DockontrolNodeRepository;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsCommand(
    name: 'wireguard:dump-node-config',
    description: 'This command will dump node configuration for provided node ID.',
)]
class WireguardDumpNodeConfigCommand extends Command
{
    public function __construct(
        private readonly DockontrolNodeRepository $dockontrolNodeRepository,
        private readonly ParameterBagInterface $parameterBag,
        private readonly Environment $twig,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->addArgument('nodeId', InputArgument::REQUIRED, 'DOCKontrol Node ID')
        ;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $nodeId = $input->getArgument('nodeId');

        $dockontrolNode = $this->dockontrolNodeRepository->find($nodeId);

        if ($dockontrolNode instanceof DockontrolNode) {
            $serverPublicIp = $this->parameterBag->get('wg_server_public_ip');
            $serverPort = $this->parameterBag->get('wg_server_port');
            $serverPublicKey = $this->parameterBag->get('wg_server_public_key');
            $serverVpnIp = $this->parameterBag->get('wg_server_vpn_ip');
            $serverSubnet = $this->parameterBag->get('wg_server_vpn_subnet');

            $nodeConfig = $this->twig->render('command/node.conf.twig', [
                'dockontrolNode' => $dockontrolNode,
                'serverPublicIp' => $serverPublicIp,
                'serverPort' => $serverPort,
                'serverPublicKey' => $serverPublicKey,
                'serverVpnIp' => $serverVpnIp,
                'serverSubnet' => $serverSubnet,
            ]);

            $output->writeln($nodeConfig);

            return Command::SUCCESS;
        }

        $io->error(sprintf('Could not find a dock control node with id: %s', $nodeId));

        return Command::FAILURE;
    }
}
