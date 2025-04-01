<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use App\Controller\CP\SearchAPIInterface;
use App\Entity\Action;
use App\Entity\Building;
use App\Entity\Group;
use App\Entity\User;
use App\Form\BuildingType;
use App\Form\Filter\BuildingFilterType;
use App\Helper\BuildingHelper;
use App\Helper\CarEnterDetailsHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\ActionRepository;
use App\Repository\BuildingRepository;
use App\Repository\CarEnterDetailsRepository;
use App\Security\Voter\BuildingVoter;
use Knp\Component\Pager\PaginatorInterface;
use Override;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * @psalm-import-type BuildingFilterArr from BuildingRepository
 */

#[Route('/settings/building')]
class BuildingController extends AbstractCPController implements SearchAPIInterface
{
    public function __construct(
        private readonly BuildingRepository $buildingRepository,
        private readonly BuildingHelper $buildingHelper,
        private readonly CarEnterDetailsRepository $carEnterDetailsRepository,
        private readonly ActionRepository $actionRepository,
        private readonly CarEnterDetailsHelper $carEnterDetailsHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/', name: 'cp_settings_building')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        $buildingFilterType = $this->createForm(BuildingFilterType::class);
        $buildingFilterType->handleRequest($request);
        $filter = [];

        if ($buildingFilterType->isSubmitted() && $buildingFilterType->isValid()) {
            $filter['name'] = $buildingFilterType->get('name')->getData();
            $filter['defaultGroup'] = $buildingFilterType->get('defaultGroup')->getData();
        }

        /**
         * @psalm-var BuildingFilterArr $filter
         */
        $queryBuilder = $this->buildingRepository->getQueryBuilder($filter);
        $buildings = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'b.name',
            'defaultSortDirection' => 'asc',
        ]);

        return $this->render('cp/settings/building/index.html.twig', [
            'buildings' => $buildings,
            'numberOfRecords' => $numberOfRecords,
            'buildingFilter' => $buildingFilterType->createView(),
        ]);
    }

    #[Route('/new', name: 'cp_settings_building_new')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(Request $request): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $building = new Building();
        $form = $this->createForm(BuildingType::class, $building);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$this->isGranted(BuildingVoter::CREATE, $building)) {
                    throw new RuntimeException('You don\'t have permission to create building.');
                }
                $this->buildingHelper->saveBuilding($building);
                $this->userActionLogHelper->addUserActionLog('Created building #' . $building->getId() . ' (' . $building->getName() . ')', $adminUser);
                $this->addFlash('success', 'Building created successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('error', 'Failed to created building: ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_settings_building');
        }

        return $this->render('cp/settings/building/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'cp_settings_building_edit')]
    #[IsGranted(BuildingVoter::EDIT, 'building')]
    public function edit(Request $request, #[MapEntity(id: 'id')] Building $building): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $form = $this->createForm(BuildingType::class, $building);
        $carEnterDetails = $this->carEnterDetailsRepository->findBy(['building' => $building], ['order' => 'ASC']);
        $allActions = $this->actionRepository->getActionsExceptCarEnterExit();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$this->isGranted(BuildingVoter::EDIT, $building)) {
                    throw new RuntimeException('You don\'t have permission to edit building.');
                }

                $this->buildingHelper->saveBuilding($building);
                $this->userActionLogHelper->addUserActionLog('Updated building #' . $building->getId() . ' (' . $building->getName() . ')', $adminUser);
                $this->addFlash('success', 'Building updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('error', 'Failed to update building: ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_settings_building');
        }

        return $this->render('cp/settings/building/edit.html.twig', [
            'form' => $form->createView(),
            'building' => $building,
            'carEnterDetails' => $carEnterDetails,
            'allActions' => $allActions,
        ]);
    }

    #[Route('/{id}/delete', name: 'cp_settings_building_delete')]
    #[IsGranted(BuildingVoter::DELETE, 'building')]
    public function delete(Request $request, #[MapEntity(id: 'id')] Building $building): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();

        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $status = false;
        $errorMessage = null;

        if (!$this->isCsrfTokenValid('buildingcsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Deleted building #' . $building->getId() . ' (' . $building->getName() . ')', $adminUser);
                $this->buildingHelper->removeBuilding($building);
                $status = true;
                $this->addFlash('danger', 'Building deleted successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete Building ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/{building}/{action}/add-car-enter-detail', name: 'cp_settings_building_add_car_enter_detail')]
    #[IsGranted(BuildingVoter::EDIT, 'building')]
    public function addCarEnterDetail(
        Request $request,
        #[MapEntity(id: 'building')] Building $building,
        #[MapEntity(id: 'action')] Action $action,
    ): JsonResponse {
        /** @var User $adminUser */
        $adminUser = $this->getUser();

        $status = false;
        $csrfToken = $request->request->getString('_csrf');
        $waitSecondsAfterEnter = $request->request->getInt('wait_seconds_after_enter');
        $waitSecondsAfterExit = $request->request->getInt('wait_seconds_after_exit');

        if (!$this->isCsrfTokenValid('carenterdetailscsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF Token';
        } else {
            try {
                if (!$this->isGranted(BuildingVoter::EDIT, $building)) {
                    throw new RuntimeException('You don\'t have permission to edit building.');
                }

                $this->carEnterDetailsHelper->saveCarEnterDetails($action, $waitSecondsAfterEnter, $waitSecondsAfterExit, building: $building);
                $this->userActionLogHelper->addUserActionLog('Added car details for building' . $building->getId() . ' (' . $building->getName() . ')', $adminUser);
                $status = true;
                $this->addFlash('success', 'Car enter detail created successfully.');
                $errorMessage = 'Car enter detail created successfully.';
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to add car enter detail, ' . $throwable->getMessage();
                $this->addFlash('danger', $errorMessage);
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/{id}/default-group', name: 'cp_settings_building_default_group')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function defaultGroup(#[MapEntity(id: 'id')] Building $building): JsonResponse
    {
        $defaultGroup = null;

        if ($building->getDefaultGroup() instanceof Group) {
            $defaultGroup = $building->getDefaultGroup();
        }

        return $this->json(['default_group_id' => $defaultGroup?->getId()]);
    }

    #[Route('/search-api', name: 'cp_settings_apartment_api_search')]
    #[IsGranted('ROLE_ADMIN')]
    #[Override]
    public function searchAPI(Request $request): JsonResponse
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $searchText = (string) $request->query->get('searchText');

        $buildings = $this->buildingRepository->searchBuilding($searchText, $adminUser);
        $buildingArr = [];

        if (!empty($buildings)) {
            foreach ($buildings as $building) {
                $buildingData = [];
                $buildingData['id'] = $building->getId();
                $buildingData['title'] = $building->getName();
                $buildingData['text'] = $building->getTwigDisplayValue();

                $buildingArr[] = $buildingData;
            }
        }

        return $this->json([
            'items' => $buildingArr,
            'totalCount' => count($buildingArr),
        ], Response::HTTP_OK);
    }
}
