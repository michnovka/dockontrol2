<?php

declare(strict_types=1);

namespace App\Controller\Guest;

use App\Controller\Common\PZCommonActionsTrait;
use App\Entity\Button;
use App\Helper\ButtonHelper;
use App\Helper\ConfigHelper;
use App\Helper\GuestHelper;
use App\Repository\DockontrolNodeRepository;
use App\Security\Voter\ButtonVoter;
use App\Security\Voter\UserCapabilityVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class GuestController extends AbstractGuestController
{
    use PZCommonActionsTrait;

    public function __construct(
        private readonly ButtonHelper $buttonHelper,
        private readonly DockontrolNodeRepository $dockontrolNodeRepository,
        private readonly ConfigHelper $configHelper,
        private readonly GuestHelper $guestHelper,
    ) {
    }

    #[Route('/guest-access/{hash}', name: 'dockontrol_guest_access')]
    #[IsGranted('ROLE_GUEST')]
    public function index(Request $request): Response
    {
        return $this->processMainPZView($request);
    }

    #[Route('/guest-access/{hash}/button-click/{id}', name: 'dockontrol_guest_button_execute', methods: ['POST'])]
    #[IsGranted(ButtonVoter::EXECUTE, 'button')]
    public function click(Request $request, Button $button): JsonResponse
    {
        return $this->processButtonClickAndReturnResponse($request, $button);
    }

    #[Route(
        '/guest-access/{hash}/car-enter-exit/{which}',
        name: 'dockontrol_guest_car_enter_exit',
        requirements: ['which' => 'enter|exit'],
        methods: ['POST']
    )]
    #[IsGranted(UserCapabilityVoter::PERMISSION_CAR_ENTER_EXIT)]
    public function carEnterExit(Request $request, string $which): JsonResponse
    {
        return $this->processCarEnterExitAndReturnResponse($request, $which);
    }

    #[Route('/guest-access/{hash}/accept-terms-of-service', name: 'dockontrol_guest_accept_terms_of_service')]
    #[IsGranted('ROLE_GUEST')]
    public function acceptTerms(Request $request): JsonResponse
    {
        return $this->acceptTermsOfService($request);
    }
}
