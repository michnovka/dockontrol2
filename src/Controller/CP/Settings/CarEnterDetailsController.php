<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use App\Entity\Building;
use App\Entity\CarEnterDetails;
use App\Entity\User;
use App\Helper\CarEnterDetailsHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\BuildingRepository;
use App\Repository\UserRepository;
use App\Security\Voter\CarEnterDetailsVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/settings/car-enter-details')]
class CarEnterDetailsController extends AbstractCPController
{
    public function __construct(
        private readonly CarEnterDetailsHelper $carEnterDetailsHelper,
        private readonly UserRepository $userRepository,
        private readonly BuildingRepository $buildingRepository,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/change-order', name: 'cp_settings_change_car_details_change_order')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function changeCarDetailsOrder(Request $request): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $csrfToken = (string) $request->request->get('_csrf');
        $ordersJSON = (string) $request->request->get('orders');
        $userId = $request->getPayload()->getInt('user');
        $buildingId = $request->getPayload()->getInt('building');
        $carEnterDetailsUser = null;
        $carEnterDetailsBuilding = null;

        if (!empty($userId)) {
            $carEnterDetailsUser = $this->userRepository->find($userId);
        } elseif (!empty($buildingId)) {
            $carEnterDetailsBuilding = $this->buildingRepository->find($buildingId);
        }

        $status = false;

        /** @var array<array-key, array{id: int, order: int, new_order: int}> $orders*/
        $orders = json_decode($ordersJSON, true);

        if (!$this->isCsrfTokenValid('carenterdetailscsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->carEnterDetailsHelper->changeCarDetailsOrder($orders);
                if ($carEnterDetailsUser instanceof User) {
                    $this->userActionLogHelper->addUserActionLog('Change order of car details for user #' . $carEnterDetailsUser->getId() . ' (' . $carEnterDetailsUser->getEmail() . ')', $adminUser);
                } elseif ($carEnterDetailsBuilding instanceof Building) {
                    $this->userActionLogHelper->addUserActionLog('Change order of car details for building #' . $carEnterDetailsBuilding->getId() . ' (' . $carEnterDetailsBuilding->getName() . ')', $adminUser);
                }
                $status = true;
                $errorMessage = 'Car details order changed successfully.';
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to change car details order: ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/{id}/remove', name: 'cp_settings_change_car_details_remove')]
    #[IsGranted(CarEnterDetailsVoter::DELETE, 'carEnterDetails')]
    public function remove(Request $request, #[MapEntity(id: 'id')] CarEnterDetails $carEnterDetails): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $csrfToken = $request->getPayload()->getString('_csrf');
        $userId = $request->getPayload()->getInt('user');
        $buildingId = $request->getPayload()->getInt('building');
        $carEnterDetailsUser = null;
        $carEnterDetailsBuilding = null;

        if (!empty($userId)) {
            $carEnterDetailsUser = $this->userRepository->find($userId);
        } elseif (!empty($buildingId)) {
            $carEnterDetailsBuilding = $this->buildingRepository->find($buildingId);
        }

        $errorMessage = null;
        $status = false;

        if (!$this->isCsrfTokenValid('carenterdetailscsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                if ($carEnterDetailsUser instanceof User) {
                    $this->userActionLogHelper->addUserActionLog('Removed car details from user #' . $carEnterDetailsUser->getId() . ' (' . $carEnterDetailsUser->getEmail() . ')', $adminUser, false);
                } elseif ($carEnterDetailsBuilding instanceof Building) {
                    $this->userActionLogHelper->addUserActionLog('Removed car details from building #' . $carEnterDetailsBuilding->getId() . ' (' . $carEnterDetailsBuilding->getName() . ')', $adminUser, false);
                }

                $this->carEnterDetailsHelper->removeCarEnterDetail($carEnterDetails);
                $status = true;
                $this->addFlash('danger', 'Car enter detail deleted successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete Button ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }
}
