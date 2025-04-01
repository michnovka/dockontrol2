<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Log\ApiCallFailedLog\API2CallFailedLog;
use App\Entity\User;
use App\Helper\ApiActionHelper;
use Carbon\CarbonImmutable;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/2')]
class API2Controller extends AbstractAPIController
{
    public function __construct(private readonly ApiActionHelper $apiActionHelper)
    {
    }

    #[Route('/info', name: 'dockontrol_api2_info', methods: ['POST'])]
    #[IsGranted('ROLE_TENANT')]
    public function info(Request $request): JsonResponse
    {
        /** @var User $user*/
        $user = $this->getUser();
        $apiKey = $this->getAPIKeyFromRequest($request);

        $ipAddress = $request->getClientIp();

        if (empty($ipAddress) || empty($apiKey)) {
            throw  new RuntimeException('request not supported');
        }

        $this->apiActionHelper->logAPI2Call($apiKey, $user, 'api_v2_info', $ipAddress);

        return $this->json([
            'status' => 'ok',
            'time' => CarbonImmutable::now()->format('Y-m-d H:i:s'),
            'dockontrol_version' => $this->getParameter('dockontrol_version'),
        ], Response::HTTP_OK);
    }

    #[Route('/{anything}', name: 'dockontrol_api2_catch_invalid_endpoint', requirements: ['anything' => '.*'])]
    #[IsGranted('ROLE_TENANT')]
    public function catchAllInvalidEndpoint(Request $request): JsonResponse
    {
        $apiKey = $this->getAPIKeyFromRequest($request);
        $ipAddress = $request->getClientIp();

        if (empty($ipAddress)) {
            throw  new RuntimeException('Request not supported.');
        }

        $apiCallFailedLog = new API2CallFailedLog();
        $apiCallFailedLog->setApiKey($apiKey);

        $this->apiActionHelper->logAPICallFailed(
            $apiCallFailedLog,
            $ipAddress,
            $request->getPathInfo(),
            'Unknown API endpoint.',
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
