<?php

declare(strict_types=1);

namespace App\Controller\Common;

use App\Entity\Button;
use App\Entity\CustomSorting;
use App\Entity\Enum\ConfigName;
use App\Entity\Enum\SpecialButtonType;
use App\Entity\Guest;
use App\Entity\User;
use App\Helper\ButtonHelper;
use App\Helper\ConfigHelper;
use App\Helper\GuestHelper;
use App\Helper\UserHelper;
use App\Repository\AnnouncementRepository;
use App\Repository\DockontrolNodeRepository;
use App\Repository\NukiRepository;
use App\Security\Voter\UserCapabilityVoter;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

trait PZCommonActionsTrait
{
    private readonly ButtonHelper $buttonHelper;
    private readonly NukiRepository $nukiRepository;
    private readonly DockontrolNodeRepository $dockontrolNodeRepository;
    private readonly ConfigHelper $configHelper;

    private readonly UserHelper $userHelper;

    private readonly GuestHelper $guestHelper;

    private readonly AnnouncementRepository $announcementRepository;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    private function processButtonClickAndReturnResponse(Request $request, Button $button): JsonResponse
    {
        $isCSRFValid = $this->validateAndRefreshCsrfCookie($request);
        if ($isCSRFValid !== true) {
            return $this->json($isCSRFValid);
        }

        $allow1min = $request->request->getBoolean('allow1min');
        $reply = $this->processButtonClick($button, $allow1min);

        return $this->returnButtonClickResponse($request, $reply);
    }

    private function processCarEnterExitAndReturnResponse(Request $request, string $which): JsonResponse
    {
        $isCSRFValid = $this->validateAndRefreshCsrfCookie($request);
        if ($isCSRFValid !== true) {
            return $this->json($isCSRFValid);
        }

        $reply = $this->processCarEnterExit($which);

        return $this->returnButtonClickResponse($request, $reply);
    }

    /**
     * @return array{status: 'ok'|'error', message: string, needsRefresh?: bool}
     */
    private function processButtonClick(Button $button, bool $allow1min): array
    {
        /** @var Guest|User $userOrGuest*/
        $userOrGuest = $this->getUser();

        try {
            $this->buttonHelper->processButtonClick($button, $userOrGuest, $allow1min);

            $message = $button->getName() . ' opened.';

            if ($allow1min) {
                $message = $button->getName() . ' opened for 1 min.';
            }

            return [
                'status' => 'ok',
                'message' => $message,
            ];
        } catch (Throwable $throwable) {
            return [
                'status' => 'error',
                'message' => 'Failed to execute button action, ' . $throwable->getMessage(),
            ];
        }
    }

    /**
     * @return array{status: 'ok'|'error', message: string, needsRefresh?: bool}
     */
    private function processCarEnterExit(string $which): array
    {
        /** @var User|Guest $userOrGuest*/
        $userOrGuest = $this->getUser();

        if (!($userOrGuest instanceof User ? $userOrGuest->isCarEnterExitAllowed() : $userOrGuest->getUser()->isCarEnterExitAllowed())) {
            return [
                'status' => 'error',
                'message' => $this->translator->trans('dockontrol.home.messages.process_car_enter_exit'),
            ];
        }

        $buttonType = match ($which) {
            'enter' => SpecialButtonType::CAR_ENTER,
            'exit' => SpecialButtonType::CAR_EXIT,
            default => throw new InvalidArgumentException($this->translator->trans('dockontrol.home.messages.unsupported_action') . $which),
        };

        try {
            $this->buttonHelper->processButtonClick($buttonType, $userOrGuest);

            return [
                'status' => 'ok',
                'message' => $which,
            ];
        } catch (Throwable $throwable) {
            return [
                'status' => 'error',
                'message' => $this->translator->trans('dockontrol.home.messages.failed_to_execute_button_action') . $throwable->getMessage(),
            ];
        }
    }

    private function processMainPZView(Request $request, bool $adminFullView = false): Response
    {
        /** @var User|Guest $user*/
        $user = $this->getUser();
        $guest = null;

        $hasAdminBeenNotified = $this->configHelper->getConfigValue(ConfigName::DOCKONTROL_NODE_ISSUE_ADMIN_NOTIFIED);
        $parameters = [];

        if (!$request->cookies->has('X-CSRF-TOKEN')) {
            $buttonCsrfToken = Uuid::v7()->toString();
        } else {
            $buttonCsrfToken = (string) $request->cookies->get('X-CSRF-TOKEN');
        }

        $buttonCsrfCookie = $this->getCSRFCookie($buttonCsrfToken);

        $response = new Response();
        $response->headers->setCookie($buttonCsrfCookie);
        $response->sendHeaders();

        if ($user instanceof Guest) {
            $guest = $user;
            $user = $user->getUser();
        }

        $hasMissingButtons = false;

        if ($adminFullView) {
            if (!empty($guest)) {
                throw new RuntimeException($this->translator->trans('dockontrol.home.messages.cannot_request_full_admin_view_when_logged_in_as_guest'));
            }

            if (!$this->isGranted('ROLE_ADMIN')) {
                throw new RuntimeException($this->translator->trans('dockontrol.home.messages.cannot_request_full_admin_view_when_you_are_not_admin'));
            }
        }

        $buttonsSeparatedByTypes = $this->buttonHelper->getUserButtonsSeparatedByTypes($user, $adminFullView);
        $nameConflicts = $buttonsSeparatedByTypes['nameConflicts'];
        $parameters['nameConflicts'] = $nameConflicts;
        if (!$adminFullView && $user->isCustomSorting()) {
            $customSortingGroups = $user->getCustomSortingGroups()->getValues();

            // only show missing buttons message for User, not guests
            if (empty($guest)) {
                $totalUserButtonCount = $this->buttonHelper->getTotalUserButtonCount($user);
                $totalSortingElements = 0;
                foreach ($customSortingGroups as $customSortingGroup) {
                    if ($customSortingGroup->getIsGroupForModal() instanceof CustomSorting) {
                        continue;
                    }
                    $totalSortingElements += $customSortingGroup->getCustomSortingElements()->count();
                }
                $hasMissingButtons = $totalUserButtonCount !== $totalSortingElements;
            }

            $parameters = array_merge($parameters, [
                'customSortingGroups' => $customSortingGroups,
            ]);
        } else {
            $buttons = $buttonsSeparatedByTypes['buttons'];
            $parameters = array_merge($parameters, [
                'gateButtons' => $buttons['gateButtons'],
                'entranceButtons' => $buttons['entranceButtons'],
                'elevatorButtons' => $buttons['elevatorButtons'],
                'multiButtons' => $buttons['multiButtons'],
            ]);
        }

        $announcements = null;
        if (empty($guest)) {
            $nukis = $this->nukiRepository->findBy(['user' => $user]);

            $parameters['nukis'] = $nukis;
            $parameters['hasMissingButtons'] = $hasMissingButtons;
            $parameters['home'] = !$adminFullView;
            $announcements = $this->announcementRepository->getActiveAnnouncementsForUser($user);
        }
        $hasNodesWhichAreNotOnline = $this->dockontrolNodeRepository->hasNodesWhichAreNotOnline($buttonsSeparatedByTypes['userButtons']);

        $parameters['isGuest'] = !empty($guest);
        $parameters['carEnterExitButtons'] = $this->isGranted(UserCapabilityVoter::PERMISSION_CAR_ENTER_EXIT);
        $parameters['camerasShow'] = $this->isGranted(UserCapabilityVoter::PERMISSION_CAMERA_ACCESS);
        $parameters['hasNodesWhichAreNotOnline'] = $hasNodesWhichAreNotOnline;
        $parameters['hasAdminBeenNotified'] = $hasAdminBeenNotified;
        $parameters['announcements'] = $announcements;
        $parameters['buttonCSRF'] = $buttonCsrfToken;

        $response = $this->render('pz/main/index.html.twig', $parameters);
        $response->headers->setCookie($buttonCsrfCookie);

        return $response;
    }

    /**
     * @return true|array{status: 'error', message: string, needsRefresh?: bool} returns true if cookie is validated without issues. returns array with error in case of an issue
     */
    private function validateAndRefreshCsrfCookie(Request $request): true|array
    {
        if (!$request->headers->has('X-CSRF-TOKEN')) {
            return [
                'status' => 'error',
                'message' => 'Missing CSRF token in request headers.',
            ];
        }

        $requestCsrfToken = $request->headers->get('X-CSRF-TOKEN');
        $storedCsrfToken = $request->cookies->get('X-CSRF-TOKEN');

        if ($storedCsrfToken !== $requestCsrfToken) {
            return [
                'status' => 'error',
                'message' => 'Invalid CSRF token.',
                'needsRefresh' => true,
            ];
        }

        return true;
    }

    private function getCSRFCookie(string $csrfToken): Cookie
    {
        return new Cookie('X-CSRF-TOKEN', $csrfToken, time() + 365 * 24 * 3600);
    }

    /**
     * @param array{status: 'ok'|'error', message: string, needsRefresh?: bool} $reply
     */
    private function returnButtonClickResponse(Request $request, array $reply): JsonResponse
    {
        $response = $this->json($reply, $reply['status'] == 'error' ? Response::HTTP_INTERNAL_SERVER_ERROR : Response::HTTP_OK);
        $response->headers->setCookie($this->getCSRFCookie((string) $request->cookies->get('X-CSRF-TOKEN')));
        return $response;
    }

    private function acceptTermsOfService(Request $request): JsonResponse
    {
        /** @var Guest|User $user*/
        $user = $this->getUser();
        $csrfToken = $request->request->getString('_csrf');

        if (!$this->isCsrfTokenValid('toscsrf', $csrfToken)) {
            return $this->json([
                'success' => false,
            ], Response::HTTP_BAD_REQUEST);
        } else {
            $user->setTimeTosAccepted(CarbonImmutable::now());

            if ($user instanceof User) {
                $this->userHelper->saveUser($user);
            } else {
                $this->guestHelper->saveGuest($user);
            }

            return $this->json([
                'success' => true,
            ]);
        }
    }
}
