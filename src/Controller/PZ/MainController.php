<?php

declare(strict_types=1);

namespace App\Controller\PZ;

use App\Controller\Common\PZCommonActionsTrait;
use App\Entity\ActionQueue;
use App\Entity\Button;
use App\Entity\Enum\UserRole;
use App\Entity\Log\CameraLog;
use App\Entity\Nuki;
use App\Entity\User;
use App\Entity\WebauthnRegistration;
use App\Exception\Nuki\APICallFailed;
use App\Exception\Nuki\LockNotAvailable;
use App\Exception\Nuki\NukiExceptionInterface;
use App\Exception\Nuki\PINMismatch;
use App\Exception\Nuki\PINRequiredException;
use App\Exception\Nuki\TooManyTries;
use App\Helper\ButtonHelper;
use App\Helper\ConfigHelper;
use App\Helper\NukiHelper;
use App\Helper\UserActionLogHelper;
use App\Helper\UserHelper;
use App\Helper\WebAuthnHelper;
use App\Repository\ActionQueueRepository;
use App\Repository\AnnouncementRepository;
use App\Repository\DockontrolNodeRepository;
use App\Repository\Log\CameraLogRepository;
use App\Repository\NukiRepository;
use App\Repository\WebauthnRegistrationRepository;
use App\Security\Expression\RoleRequired;
use App\Security\Voter\ButtonVoter;
use App\Security\Voter\NukiVoter;
use App\Security\Voter\UserCapabilityVoter;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class MainController extends AbstractPZController
{
    use PZCommonActionsTrait;

    public function __construct(
        private readonly ButtonHelper $buttonHelper,
        private readonly NukiRepository $nukiRepository,
        private readonly DockontrolNodeRepository $dockontrolNodeRepository,
        private readonly ConfigHelper $configHelper,
        private readonly UserHelper $userHelper,
        private readonly NukiHelper $nukiHelper,
        private readonly ActionQueueRepository $actionQueueRepository,
        private readonly CameraLogRepository $cameraLogRepository,
        private readonly UserActionLogHelper $userActionLogHelper,
        private readonly TranslatorInterface $translator,
        private readonly WebauthnRegistrationRepository $webauthnRegistrationRepository,
        private readonly WebauthnHelper $webAuthnHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly AnnouncementRepository $announcementRepository,
    ) {
    }

    #[Route('/', name: 'dockontrol_main')]
    #[IsGranted('ROLE_TENANT')]
    public function index(Request $request): Response
    {
        return $this->processMainPZView($request);
    }

    #[Route('/full-view', name: 'dockontrol_main_full_view')]
    #[IsGranted(new RoleRequired(UserRole::ADMIN))]
    public function fullView(Request $request): Response
    {
        return $this->processMainPZView($request, true);
    }

    #[Route('/button-click/{id}', name: 'dockontrol_button_execute')]
    #[IsGranted(ButtonVoter::EXECUTE, 'button')]
    public function click(Request $request, Button $button): JsonResponse
    {
        return $this->processButtonClickAndReturnResponse($request, $button);
    }

    #[Route(
        '/car-enter-exit/{which}',
        name: 'dockontrol_car_enter_exit',
        requirements: ['which' => 'enter|exit'],
        methods: ['POST']
    )]
    #[IsGranted(UserCapabilityVoter::PERMISSION_CAR_ENTER_EXIT)]
    public function carEnterExit(Request $request, string $which): JsonResponse
    {
        return $this->processCarEnterExitAndReturnResponse($request, $which);
    }

    #[Route('/accept-terms-of-service', name: 'dockontrol_accept_terms_of_service')]
    #[IsGranted('ROLE_TENANT')]
    public function acceptTerms(Request $request): JsonResponse
    {
        return $this->acceptTermsOfService($request);
    }

    #[Route('/nuki-engage/{id}', name: 'dockontrol_nuki_engage')]
    #[IsGranted(NukiVoter::ENGAGE, 'nuki')]
    public function engage(Request $request, #[MapEntity(id: 'id')] Nuki $nuki): JsonResponse
    {
        $csrfToken = $request->request->getString('_csrf');
        $totp2 = $request->request->getString('totp');
        $totpNonce = $request->request->getString('totp_nonce');
        $pin = null;
        $isLock = false;
        if ($request->request->has('pin')) {
            $pin = $request->request->getString('pin');
        }
        if ($request->request->has('isLock')) {
            $isLock = $request->request->getBoolean('isLock');
        }

        if (!$this->isCsrfTokenValid('nukicsrf', $csrfToken)) {
            $response = [
                'status' => 'error',
                'message' => $this->translator->trans('dockontrol.global.invalid_csrf_token'),
            ];
            $responseCode = Response::HTTP_FORBIDDEN;
        } else {
            $response = $this->processEngage($nuki, $isLock, $totp2, $totpNonce, $pin);
        }

        return $this->json($response);
    }

    #[Route('/nuki-engage/{id}/webauthn', name: 'dockontrol_webauthn_process_get_and_engage')]
    #[IsGranted(NukiVoter::ENGAGE, 'nuki')]
    public function processGet(Request $request, #[MapEntity(id: 'id')] Nuki $nuki): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        try {
            $bodyData = json_decode($request->getContent(), true);

            if (!isset($bodyData['clientDataJSON']) || !isset($bodyData['authenticatorData']) || !isset($bodyData['signature']) || !isset($bodyData['id'])) {
                throw new InvalidArgumentException("Missing WebAuthn credential data.");
            }

            $clientDataJSON = base64_decode($bodyData['clientDataJSON']);
            $authenticatorData = base64_decode($bodyData['authenticatorData']);
            $signature = base64_decode($bodyData['signature']);
            $id = base64_decode($bodyData['id']);

            $totp2 = $bodyData['totp'];
            $totpNonce = (string) $bodyData['totp_nonce'];
            $isLock = false;
            if (isset($bodyData['isLock'])) {
                $isLock = $bodyData['isLock'];
            }
            $credentialPublicKey = null;

            $webauthnRegistration = $this->webauthnRegistrationRepository->findOneBy(['user' => $currentUser, 'credentialId' => bin2hex($id)]);
            if (!$webauthnRegistration instanceof WebauthnRegistration) {
                throw new RuntimeException('Credential ID not found!');
            }

            $data = $webauthnRegistration->getData();

            if ($data) {
                $data = unserialize($data);
                $credentialPublicKey = $data->credentialPublicKey;
            }

            if ($credentialPublicKey === null) {
                throw new RuntimeException('Public Key for credential ID not found!');
            }

            $this->webAuthnHelper->processGetRequest($clientDataJSON, $authenticatorData, $signature, $credentialPublicKey, $webauthnRegistration);
            $response = $this->processEngage($nuki, $isLock, $totp2, $totpNonce, $nuki->getPin());
        } catch (Throwable $throwable) {
            $response = [
                'status' => 'error',
                'message' => $this->translator->trans('dockontrol.settings.nuki.messages.unknown_error')  . $throwable->getMessage(),
            ];
        }

        return $this->json($response);
    }

    #[Route('/download-data-export/{logType}', name: 'dockontrol_export_data', requirements: ['logType' => 'camera_logs|action_queue_logs'])]
    #[IsGranted('ROLE_TENANT')]
    public function downloadDataExport(string $logType): Response
    {
        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();

        if ($logType === 'camera_logs') {
            $cameraLogs = $this->cameraLogRepository->getCameraLogsForUser($currentUser);
            $response = $this->streamLogsAsCSV($logType, $cameraLogs);
            $description = 'Requested download logs for Camera';
        } elseif ($logType === 'action_queue_logs') {
            $actionQueueLogs = $this->actionQueueRepository->getActionQueuesForUser($currentUser);
            $response = $this->streamLogsAsCSV($logType, $actionQueueLogs);
            $description = 'Requested download logs for Action Queue';
        } else {
            throw $this->createNotFoundException('Invalid log type.');
        }

        $this->userActionLogHelper->addUserActionLog($description, $currentUser);
        $filename = $logType . '_' . $currentUser->getId() . '_' . time() . '.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    /**
     * @param CameraLog[]|ActionQueue[] $logs
     */
    private function streamLogsAsCSV(string $logType, iterable $logs): StreamedResponse
    {
        return new StreamedResponse(function () use ($logType, $logs): void {
            $batchSize = 500;
            static $count = 0;

            $handle = fopen('php://output', 'w');

            if (!$handle) {
                throw new RuntimeException('failed to open file');
            }

            if ($logType === 'action_queue_logs') {
                fputcsv($handle, ['Action', 'Status', 'Time Start', 'Time Executed']);
            } else {
                fputcsv($handle, ['Camera name', 'Time']);
            }

            foreach ($logs as $log) {
                if ($log instanceof CameraLog) {
                    fputcsv($handle, [
                        $log->getCamera()->getFriendlyName(),
                        $log->getTime()->format('Y-m-d H:i:s'),
                        $log->getCamera()->getDockontrolNode()->getBuilding()->getName(),
                    ]);
                } else {
                    fputcsv($handle, [
                        $log->getAction()->getFriendlyName(),
                        $log->getStatus()->getReadable(),
                        $log->getTimeStart()->format('Y-m-d H:i:s'),
                        $log->getTimeExecuted()?->format('Y-m-d H:i:s') ?? 'N/A',
                    ]);
                }

                $count++;

                if ($count % $batchSize === 0) {
                    $this->entityManager->clear();
                    $count = 0;
                }
            }
        });
    }


    /**
     * @return array{message?: null|string, needsRefresh?: true, status: 'error'|'success'}
     */
    private function processEngage(Nuki $nuki, bool $isLock, string $totp, string $totpNonce, ?string $pin): array
    {
        $response = ['status' => 'error'];
        try {
            $apiResponse = $this->nukiHelper->engage($nuki, $isLock, $totp, $totpNonce, $pin);
            if ($apiResponse['status'] == 'error') {
                $response['message'] = $apiResponse['message'];
            } else {
                $response['status'] = 'success';
            }
        } catch (PINRequiredException) {
            // this means we loaded page when nuki did not have PIN, but now it does. We need to reload
            $response['message'] = $this->translator->trans('dockontrol.settings.nuki.messages.pin_required');
            $response['needsRefresh'] = true;
        } catch (PINMismatch) {
            // PIN does not match
            $response['message'] = $this->translator->trans('dockontrol.settings.nuki.messages.incorrect_pin');
        } catch (TooManyTries) {
            // user tried too often with wrong PIN
            $response['message'] = $this->translator->trans('dockontrol.settings.nuki.messages.too_many_tries');
        } catch (LockNotAvailable) {
            // user tried to lock but Nuki does not allow it
            $response['message'] = $this->translator->trans('dockontrol.settings.nuki.messages.lock_not_available');
        } catch (APICallFailed) {
            // nuki API is not available
            $response['message'] = $this->translator->trans('dockontrol.settings.nuki.messages.api_call_failed');
        } catch (NukiExceptionInterface $nukiException) {
            $response['message'] = $this->translator->trans('dockontrol.settings.nuki.messages.nuki_error') . $nukiException->getMessage();
        } catch (Throwable $throwable) {
            $response['message'] = $this->translator->trans('dockontrol.settings.nuki.messages.unknown_error')  . $throwable->getMessage();
        }

        return $response;
    }
}
