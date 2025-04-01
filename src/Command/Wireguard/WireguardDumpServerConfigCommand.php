<?php

declare(strict_types=1);

namespace App\Command\Wireguard;

use App\Repository\DockontrolNodeRepository;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsCommand(name: 'wireguard:dump-server-config', description: 'This command will dump server config.')]
class WireguardDumpServerConfigCommand extends Command
{
    public function __construct(
        private readonly DockontrolNodeRepository $dockontrolNodeRepository,
        private readonly Environment $twig,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct();
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dockontrolNodes = $this->dockontrolNodeRepository->findAll();
        $wireguardServerPrivateKey = $this->parameterBag->get('wg_server_private_key');
        $serverVpnIp = $this->parameterBag->get('wg_server_vpn_ip');
        $serverSubnet = $this->parameterBag->get('wg_server_vpn_subnet');
        $wgServerPort = $this->parameterBag->get('wg_server_port');

        $wgConfig = $this->twig->render('command/wg0.conf.twig', [
            'wireguardServerPrivateKey' => $wireguardServerPrivateKey,
            'wireguardServerVPNIP' => $serverVpnIp,
            'wireguardServerSubnet' => $serverSubnet,
            'wireguardServerPort' => $wgServerPort,
            'dockontrolNodes' => $dockontrolNodes,
        ]);

        $output->write($wgConfig);

        return Command::SUCCESS;
    }
}
