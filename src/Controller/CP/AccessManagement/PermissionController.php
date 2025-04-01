<?php

declare(strict_types=1);

namespace App\Controller\CP\AccessManagement;

use App\Controller\CP\AbstractCPController;
use App\Entity\Permission;
use App\Entity\User;
use App\Form\ManageBuildingType;
use App\Form\ManageGroupType;
use App\Form\PermissionType;
use App\Helper\BuildingHelper;
use App\Helper\GroupHelper;
use App\Helper\PermissionHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\PermissionRepository;
use App\Security\Voter\PermissionVoter;
use Knp\Component\Pager\PaginatorInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/access-management/permissions')]
class PermissionController extends AbstractCPController
{
    public function __construct(
        private readonly PermissionRepository $permissionsRepository,
        private readonly PermissionHelper $permissionHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
        private readonly GroupHelper $groupHelper,
        private readonly BuildingHelper $buildingHelper,
    ) {
    }

    #[Route('/', name: 'cp_access_management_permission')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);

        $queryBuilder = $this->permissionsRepository->getQueryBuilder();
        $permissions = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords);

        return $this->render('cp/access_management/permission/index.html.twig', [
            'permissions' => $permissions,
            'numberOfRecords' => $numberOfRecords,
        ]);
    }

    #[Route('/new', name: 'cp_access_management_permission_new')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(Request $request): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $permission = new Permission();
        $form = $this->createForm(PermissionType::class, $permission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$this->isGranted(PermissionVoter::CREATE, $permission)) {
                    throw new RuntimeException('You don\'t have permission to create permission.');
                }

                $this->permissionHelper->savePermission($permission);
                $this->userActionLogHelper->addUserActionLog('Created permission ' . $permission->getName(), $adminUser);
                $this->addFlash('success', 'Permission created successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to create permission ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_access_management_permission');
        }

        return $this->render('cp/access_management/permission/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{name}/edit', name: 'cp_access_management_permission_edit')]
    #[IsGranted(PermissionVoter::EDIT, 'permission')]
    public function edit(Request $request, #[MapEntity(id: 'name')] Permission $permission): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $form = $this->createForm(PermissionType::class, $permission);
        $form->handleRequest($request);

        $manageGroupForm = $this->createForm(ManageGroupType::class, null, [
            'groups' => $permission->getGroups()->getValues(),
        ]);
        $manageGroupForm->handleRequest($request);

        $manageBuildingForm = $this->createForm(ManageBuildingType::class, null, [
            'buildings' => $permission->getBuildings()->getValues(),
        ]);
        $manageBuildingForm->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$this->isGranted(PermissionVoter::EDIT, $permission)) {
                    throw new RuntimeException('You don\'t have permission to edit permission.');
                }

                $this->permissionHelper->savePermission($permission);
                $this->userActionLogHelper->addUserActionLog('Updated permission ' . $permission->getName(), $adminUser);
                $this->addFlash('success', 'Permission updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to updated permission ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_access_management_permission');
        }

        if ($manageGroupForm->isSubmitted() && $manageGroupForm->isValid()) {
            $groups = $manageGroupForm->get('groups')->getData();
            try {
                $this->groupHelper->updateGroupsForUserOrPermission($permission, $groups, $adminUser);
                $this->addFlash('success', 'Groups updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to update group, ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_access_management_permission_edit', ['name' => $permission->getName()]);
        }

        if ($manageBuildingForm->isSubmitted() && $manageBuildingForm->isValid()) {
            try {
                $buildings = $manageBuildingForm->get('buildings')->getData();
                $this->buildingHelper->updateBuildingsPermission($permission, $buildings, $adminUser);
                $this->addFlash('success', 'Building updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to update building, ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_access_management_permission_edit', ['name' => $permission->getName()]);
        }

        return $this->render('cp/access_management/permission/edit.html.twig', [
            'form' => $form->createView(),
            'permission' => $permission,
            'manageGroupForm' => $manageGroupForm->createView(),
            'manageBuildingForm' => $manageBuildingForm->createView(),
        ]);
    }

    #[Route('/{name}/delete', name: 'cp_access_management_permission_delete')]
    #[IsGranted(PermissionVoter::DELETE, 'permission')]
    public function delete(
        Request $request,
        #[MapEntity(id: 'name')] Permission $permission,
    ): JsonResponse {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $errorMessage = null;
        $status = false;
        if (!$this->isCsrfTokenValid('permissioncsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $this->userActionLogHelper->addUserActionLog('Deleted permission ' . $permission->getName(), $adminUser, false);
                $this->permissionHelper->deletePermission($permission);
                $status = true;
                $this->addFlash('danger', 'Permission deleted successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete permission ' . $throwable->getMessage();
            }
        }
        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }
}
