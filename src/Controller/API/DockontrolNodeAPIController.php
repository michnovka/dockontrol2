<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\DockontrolNode;
use App\Entity\Log\ApiCallFailedLog\DockontrolNodeAPICallFailedLog;
use App\Helper\ApiActionHelper;
use Carbon\CarbonImmutable;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/node')]
class DockontrolNodeAPIController extends AbstractAPIController
{
    public function __construct(
        private readonly ApiActionHelper $apiActionHelper,
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    #[Route('/info', name: 'dockontrol_node_info_api', methods: ['POST'])]
    #[IsGranted('ROLE_DOCKONTROL_NODE')]
    public function index(Request $request): JsonResponse
    {
        /** @var DockontrolNode $dockontrolNode */
        $dockontrolNode = $this->getUser();

        $ipAddress = $request->getClientIp();

        if (empty($ipAddress)) {
            throw  new RuntimeException('Request not supported.');
        }

        $this->apiActionHelper->logDockontrolNodeAPICall($dockontrolNode, 'dockontrol_node_info', $ipAddress);

        return $this->json([
            'status' => 'ok',
            'time' => CarbonImmutable::now()->format('Y-m-d H:i:s'),
            'dockontrol_version' => $this->getParameter('dockontrol_version'),
        ], Response::HTTP_OK);
    }

    #[Route('/get-config', name: 'dockontrol_node_get_config_api', methods: ['POST'])]
    #[IsGranted('ROLE_DOCKONTROL_NODE')]
    public function getConfig(Request $request): JsonResponse
    {
        /** @var DockontrolNode $dockontrolNode*/
        $dockontrolNode = $this->getUser();

        $ipAddress = $request->getClientIp();

        if (empty($ipAddress)) {
            throw  new RuntimeException('Request not supported.');
        }
        /** @var string $serverPublicIp */
        $serverPublicIp = $this->parameterBag->get('wg_server_public_ip');
        /** @var string $serverPort */
        $serverPort = $this->parameterBag->get('wg_server_port');
        /** @var string $serverPublicKey */
        $serverPublicKey = $this->parameterBag->get('wg_server_public_key');
        /** @var string $serverVpnIp */
        $serverVpnIp = $this->parameterBag->get('wg_server_vpn_ip');
        /** @var string $serverSubnet */
        $serverSubnet = $this->parameterBag->get('wg_server_vpn_subnet');

        if (empty($serverSubnet) || empty($serverPublicKey) || empty($serverPublicIp) || empty($serverPort) || empty($serverVpnIp)) {
            throw new RuntimeException('Failed to generate Node config.');
        }

        $nodeConfig = $this->renderView('command/node.conf.twig', [
            'dockontrolNode' => $dockontrolNode,
            'serverPublicIp' => $serverPublicIp,
            'serverPort' => $serverPort,
            'serverPublicKey' => $serverPublicKey,
            'serverVpnIp' => $serverVpnIp,
            'serverSubnet' => $serverSubnet,
        ]);

        $this->apiActionHelper->logDockontrolNodeAPICall($dockontrolNode, 'dockontrol_node_get_config', $ipAddress);

        return $this->json([
            'wg_conf' => $nodeConfig,
        ], Response::HTTP_OK);
    }

    #[Route('/{anything}', name: 'dockontrol_node_catch_invalid_api', requirements: ['anything' => '.*'])]
    #[IsGranted('ROLE_DOCKONTROL_NODE')]
    public function catchAllInvalidEndpoint(Request $request): JsonResponse
    {
        $apiKey = $this->getAPIKeyFromRequest($request);
        $ipAddress = $request->getClientIp();

        if (empty($ipAddress)) {
            throw  new RuntimeException('Request not supported.');
        }

        $apiCallFailedLog = new DockontrolNodeAPICallFailedLog();
        $apiCallFailedLog->setDockontrolNodeAPIKey($apiKey);


        $this->apiActionHelper->logAPICallFailed(
            $apiCallFailedLog,
            $ipAddress,
            $request->getPathInfo(),
            'Unknown API endpoint.'
        );

        return $this->json([
            'status' => 'error',
            'message' => 'Invalid API endpoint',
        ], Response::HTTP_NOT_FOUND);
    }

    private function getAPIKeyFromRequest(Request $request): string
    {
        return $request->headers->get('X-API-Key') ?? '';
    }
}
