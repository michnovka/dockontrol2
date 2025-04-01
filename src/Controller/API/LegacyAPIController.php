<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Controller\Common\PZCommonActionsTrait;
use App\Entity\Button;
use App\Entity\Log\ApiCallFailedLog\LegacyAPICallFailedLog;
use App\Entity\User;
use App\Helper\ApiActionHelper;
use App\Helper\ButtonHelper;
use App\Helper\ConfigHelper;
use App\Repository\ButtonRepository;
use App\Repository\NukiRepository;
use App\Security\Voter\ButtonVoter;
use App\Security\Voter\UserCapabilityVoter;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/1')]
class LegacyAPIController extends AbstractAPIController
{
    use PZCommonActionsTrait;

    public function __construct(
        private readonly ApiActionHelper $apiActionHelper,
        private readonly ButtonRepository $buttonRepository,
        private readonly ButtonHelper $buttonHelper,
        private readonly NukiRepository $nukiRepository,
        private readonly ConfigHelper $configHelper,
        private readonly Security $security,
    ) {
    }

    #[Route('', name: 'dockontrol_main_api', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TENANT')]
    public function index(Request $request): JsonResponse
    {
        // at this point user will be authorized for api action - api_list & app_login
        /** @var User $user */
        $user = $this->getUser();

        $reply = [
            'status' => 'error',
        ];

        $apiAction = $request->query->has('action') ?
            $request->query->getString('action') :
            ($request->request->has('action') ? $request->request->getString('action') : null);

        if (empty($apiAction)) {
            $reply['message'] = 'No action specified';
        } else {
            switch ($apiAction) {
                case 'app_login':
                    try {
                        $reply['status'] = 'ok';
                        $reply['config'] = [
                            'timeout' => 10,
                        ];
                        $allowedActions = $this->apiActionHelper->getLegacyAPIAllowedActionsForUser($user);
                        $reply['allowed_actions'] = $allowedActions;
                    } catch (Throwable $throwable) {
                        $reply['message'] = 'Database error' . $throwable->getMessage();
                    }
                    break;
                default:
                    // find button
                    if ($apiAction == 'exit' || $apiAction == 'enter') {
                        if (!$this->security->isGranted(UserCapabilityVoter::PERMISSION_CAR_ENTER_EXIT)) {
                            $reply['message'] = 'You don\'t have permission to execute this action.';
                        } else {
                            $reply = $this->processCarEnterExit($apiAction);
                        }
                    } else {
                        $button = $this->buttonRepository->find($apiAction);
                        if ($button instanceof Button) {
                            // check permissions
                            if (!$this->security->isGranted(ButtonVoter::EXECUTE, $button)) {
                                $reply['message'] = 'You don\'t have permission to execute this action.';
                            } else {
                                $allow1min = $request->request->getBoolean('allow1min');

                                $reply = $this->processButtonClick($button, $allow1min);
                            }
                        } else {
                            $reply['message'] = 'Unknown action: ' . $apiAction;
                        }
                    }
            }
        }

        $ipAddress = $request->getClientIp() ?? '';
        if ($reply['status'] == 'error') {
            $apiCallFailedLog = new LegacyAPICallFailedLog();
            $apiCallFailedLog->setEmail($user->getEmail());
            $apiCallFailedLog->setApiAction($apiAction ?? '');

            $this->apiActionHelper->logAPICallFailed(
                $apiCallFailedLog,
                $ipAddress,
                $request->getPathInfo(),
                $reply['message'] ?? ''
            );
        } else {
            $this->apiActionHelper->logLegacyAPICall($user, $apiAction ?? '', $ipAddress);
        }
        return $this->json($reply);
    }

    #[Route('/{anything}', name: 'dockontrol_main_catch_invalid_api', requirements: ['anything' => '.*'])]
    #[IsGranted('ROLE_TENANT')]
    public function catchAllInvalidEndpoint(Request $request): JsonResponse
    {
        $action = $this->getRequestValue($request, 'action') ?? '';
        $email = $this->getRequestValue($request, 'email') ?? '';
        $ipAddress = $request->getClientIp();

        if (empty($ipAddress)) {
            throw  new RuntimeException('Request not supported.');
        }

        $apiCallFailedLog = new LegacyAPICallFailedLog();
        $apiCallFailedLog->setEmail($email);
        $apiCallFailedLog->setApiAction($action);

        $this->apiActionHelper->logAPICallFailed(
            $apiCallFailedLog,
            $ipAddress,
            $request->getPathInfo(),
            'unknown API endpoint'
        );

        return $this->json([
            'status' => 'error',
            'message' => 'Invalid API endpoint.',
        ], Response::HTTP_NOT_FOUND);
    }

    private function getRequestValue(Request $request, string $key): ?string
    {
        if ($request->query->has($key)) {
            return $request->query->getString($key);
        } elseif ($request->request->has($key)) {
            return $request->request->getString($key);
        }

        return null;
    }
}
